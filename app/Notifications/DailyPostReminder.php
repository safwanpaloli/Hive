<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DailyPostReminder extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, mixed>  $posts
     */
    public function __construct(public Collection $posts) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = $this->posts->count();
        $posted = $this->posts->where('status', 'posted')->count();

        $mail = (new MailMessage)
            ->subject("📬 Today's Content Queue — {$total} post(s) awaiting you")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have **{$total} post(s)** scheduled for today.")
            ->line($posted > 0 ? "{$posted} already marked as posted. Nice momentum!" : 'Take a moment to review your queue and mark posts as posted once they go live.')
            ->line('Here is what is on your plate today:');

        foreach ($this->posts->take(10) as $post) {
            $mail->line('- '.($post->title ?? 'Untitled').' — '.optional($post->scheduled_at)->format('H:i'));
        }

        if ($this->posts->count() > 10) {
            $mail->line('…and '.($this->posts->count() - 10).' more.');
        }

        return $mail->action('Open Dashboard', url('/'))
            ->line('Stay consistent. Post something great today!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "You have {$this->posts->count()} post(s) scheduled for today.",
            'posts' => $this->posts->map(fn ($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'scheduled_at' => $post->scheduled_at?->toDateTimeString(),
            ])->values(),
        ];
    }
}
