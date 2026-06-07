<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCode extends Notification
{
    public function __construct(
        protected string $code
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

        return (new MailMessage)
            ->subject('Your AfriChart verification code')
            ->greeting('Verify your email')
            ->line('Use the code below to finish setting up your AfriChart EMR account.')
            ->line('**'.$this->code.'**')
            ->line("This code expires in {$ttl} minutes.")
            ->line('If you did not create an account, no action is needed.');
    }
}
