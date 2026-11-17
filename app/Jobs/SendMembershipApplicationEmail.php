<?php

namespace App\Jobs;

use App\Models\MembershipTransaction;
use App\Notifications\MembershipApplicationSubmitted;
use App\Services\ApplicationEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendMembershipApplicationEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $transactionId)
    {
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(ApplicationEmail $email): void
    {
        $failure = DB::transaction(function () use ($email): ?Throwable {
            $transaction = MembershipTransaction::lockForUpdate()->find($this->transactionId);
            if (! $transaction || $transaction->type !== 'Application' || $transaction->application_email_sent_at || ! $transaction->application_email_to) {
                return null;
            }
            if (! $email->configured()) {
                $transaction->update(['application_email_status' => 'not_configured']);

                return null;
            }
            $transaction->increment('application_email_attempts');
            try {
                Notification::route('mail', $transaction->application_email_to)->notifyNow(new MembershipApplicationSubmitted($transaction));
                $transaction->update(['application_email_status' => 'sent', 'application_email_sent_at' => now()]);
            } catch (Throwable $exception) {
                $transaction->update(['application_email_status' => 'failed']);

                return $exception;
            }

            return null;
        });
        if ($failure) {
            throw $failure;
        }
    }

    public function failed(?Throwable $exception): void
    {
        MembershipTransaction::whereKey($this->transactionId)->whereNull('application_email_sent_at')->update(['application_email_status' => 'failed']);
    }
}
