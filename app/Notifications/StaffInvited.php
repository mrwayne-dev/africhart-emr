<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Carries an invitation link to someone who is not yet a staff member.
 *
 * The accept URL is passed in already built rather than generated here. Tenant
 * routes carry a {clinic} domain parameter which the identification middleware
 * registers as a URL default PER REQUEST; a queued notification renders in a
 * worker where that default was never set, so route() would fail or, worse,
 * produce a link to the wrong clinic. Building it in the request and passing
 * the string removes the question.
 */
class StaffInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        protected string $clinicName,
        protected string $roleLabel,
        protected string $acceptUrl,
        protected string $invitedByName,
        protected int $expiresInDays,
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
        return (new MailMessage)
            ->subject("You've been invited to join {$this->clinicName} on AfriChart")
            ->greeting('You have been invited')
            ->line("{$this->invitedByName} has invited you to join **{$this->clinicName}** on AfriChart EMR as a **{$this->roleLabel}**.")
            ->line('Use the button below to set your password and activate your account.')
            ->action('Accept invitation', $this->acceptUrl)
            ->line("This invitation expires in {$this->expiresInDays} days and can only be used once.")
            ->line('If you were not expecting this, you can ignore this email — nothing is created until you accept.');
    }
}
