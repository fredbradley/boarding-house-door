<?php

namespace App\Notifications;

use App\Models\Screen;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoverageGapAlert extends Notification
{
    use Queueable;

    public function __construct(
        public Screen $screen,
        public Carbon $gapAt,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $adminUrl = url('/admin');
        $formattedTime = $this->gapAt->format('H:i \o\n l j F');

        return (new MailMessage)
            ->subject("Door display gap coming up – {$this->screen->name}")
            ->greeting('Heads up!')
            ->line("Your door display ({$this->screen->name}) will have nothing scheduled at {$formattedTime}.")
            ->line('The screen will fall back to showing "'.$this->screen->default_heading.'". If you\'ll be away, log in to add an entry.')
            ->action('Manage display', $adminUrl);
    }
}
