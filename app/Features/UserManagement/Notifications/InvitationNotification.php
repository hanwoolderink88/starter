<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class InvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

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
        $url = URL::temporarySignedRoute('invitation.accept', now()->addHours(48), ['user' => $this->user->id]);

        return (new MailMessage)
            ->subject(__('users.invitation.mail.subject'))
            ->line(__('users.invitation.mail.intro', ['app' => config('app.name')]))
            ->line(__('users.invitation.mail.instruction'))
            ->action(__('users.invitation.mail.action'), $url)
            ->line(__('users.invitation.mail.expiry'));
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
