<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCode extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * The clinic name is passed IN, not read from tenant() inside toMail().
     *
     * This notification is queued, so toMail() runs in a worker. Reading the
     * tenant there means trusting that whatever restored tenancy for this job
     * restored the right one — and if it restored nothing, the mail names no
     * clinic while still looking fine. Same reasoning as StaffInvited.
     */
    public function __construct(
        protected string $code,
        protected ?string $clinicName = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ttl = config('registration.verification_code_ttl', 10);

        $clinic = $this->clinicName ?: 'your clinic';

        return (new MailMessage)
            ->subject("Your verification code for {$clinic}")
            ->greeting('Verify your email')
            ->line("Use the code below to finish setting up your account at **{$clinic}** on AfriChart EMR.")
            ->line('**'.$this->code.'**')
            ->line("This code expires in {$ttl} minutes.")
            ->line('If you did not create an account, no action is needed.');
    }
}
