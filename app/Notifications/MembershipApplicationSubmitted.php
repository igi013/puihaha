<?php

namespace App\Notifications;

use App\Models\MembershipTransaction;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipApplicationSubmitted extends Notification
{
    public function __construct(public MembershipTransaction $transaction) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Membership application received')
            ->greeting('Hello '.$this->transaction->application_email_name.',')
            ->line('Your membership application has been recorded.')
            ->line('Reference number: '.$this->transaction->reference_number)
            ->line('Category: '.$this->transaction->category_name.' · Level: '.$this->transaction->level_name)
            ->line('Membership fee: PHP '.number_format((float) $this->transaction->amount_due, 2))
            ->line('This acknowledgment does not confirm payment, approve membership, or verify ownership of this email address.')
            ->line('Keep your reference number for your records.')
            ->salutation(config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
