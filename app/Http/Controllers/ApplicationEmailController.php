<?php

namespace App\Http\Controllers;

use App\Models\MembershipTransaction;
use App\Services\ApplicationEmail;
use Illuminate\Http\RedirectResponse;

class ApplicationEmailController extends Controller
{
    public function store(MembershipTransaction $transaction, ApplicationEmail $email): RedirectResponse
    {
        abort_unless($transaction->type === 'Application' && $transaction->application_email_status, 404);
        $email->dispatch($transaction->id);

        return back()->with('success', match ($transaction->fresh()->application_email_status) {
            'sent' => 'The application acknowledgment was sent to the mail provider.',
            'queued' => 'The application acknowledgment is queued for sending.',
            'not_configured' => 'Email sending has not been configured yet. The application is saved.',
            default => 'The email could not be sent. The application is saved; you can retry later.',
        });
    }
}
