<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminActivity extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * @param  array<int, string>  $lines
     */
    /**
     * $clinicName is passed in rather than read from tenant() in toMail(),
     * which runs in a queue worker. See EmailVerificationCode.
     */
    public function __construct(
        public string $subject,
        public string $heading,
        public array $lines,
        public ?string $actionText = null,
        public ?string $actionUrl = null,
        public ?string $clinicName = null,
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
        /*
         * An admin at a multi-site group receives these from more than one
         * clinic. Without the clinic in the subject line, "New patient
         * registered" gives them no way to tell which practice it happened at.
         */
        $mail = (new MailMessage)
            ->subject($this->clinicName ? "[{$this->clinicName}] {$this->subject}" : $this->subject)
            ->greeting($this->heading);

        foreach ($this->lines as $line) {
            $mail->line($line);
        }

        if ($this->actionText && $this->actionUrl) {
            $mail->action($this->actionText, $this->actionUrl);
        }

        return $mail->line($this->clinicName
            ? "— {$this->clinicName}, via AfriChart EMR"
            : '— AfriChart EMR');
    }
}
