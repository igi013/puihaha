<?php

namespace App\Services;

use App\Jobs\SendMembershipApplicationEmail;
use App\Models\MembershipTransaction;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApplicationEmail
{
    public function configured(): bool
    {
        return (bool) config('services.membership_mail.enabled')
            && (app()->environment('testing') || (
                config('mail.default') === 'smtp'
                && filled(config('mail.mailers.smtp.host'))
                && filled(config('mail.mailers.smtp.username'))
                && filled(config('mail.mailers.smtp.password'))
                && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)
            ));
    }

    public function dispatch(int $transactionId): void
    {
        $dispatch = DB::transaction(function () use ($transactionId): bool {
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transactionId);
            if ($transaction->type !== 'Application' || ! $transaction->application_email_status || in_array($transaction->application_email_status, ['queued', 'sent'], true)) {
                return false;
            }
            if (! $this->configured()) {
                $transaction->update(['application_email_status' => 'not_configured']);

                return false;
            }
            $transaction->update(['application_email_status' => 'queued', 'application_email_queued_at' => now()]);

            return true;
        });
        if (! $dispatch) {
            return;
        }
        DB::afterCommit(function () use ($transactionId): void {
            try {
                Bus::dispatch((new SendMembershipApplicationEmail($transactionId))->onConnection(config('services.membership_mail.connection', 'sync')));
            } catch (Throwable $exception) {
                MembershipTransaction::whereKey($transactionId)->whereNull('application_email_sent_at')->update(['application_email_status' => 'failed']);
                Log::warning('Application email could not be sent. The membership transaction was retained.', ['transaction_id' => $transactionId, 'exception_type' => $exception::class]);
            }
        });
    }
}
