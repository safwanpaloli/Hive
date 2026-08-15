<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use App\Notifications\DailyPostReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPostReminders extends Command
{
    protected $signature = 'app:send-post-reminders';

    protected $description = 'Aggregate posts due today and notify each user with a daily reminder.';

    public function handle(): int
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $postsByUser = Post::query()
            ->with('user:id,name,email')
            ->whereBetween('scheduled_at', [$start, $end])
            ->where('status', Post::STATUS_SCHEDULED)
            ->get()
            ->groupBy('user_id');

        if ($postsByUser->isEmpty()) {
            $this->info('No posts scheduled for today.');

            return self::SUCCESS;
        }

        $users = User::whereKey($postsByUser->keys())->get();

        foreach ($users as $user) {
            $posts = $postsByUser->get($user->id);

            $user->notify(new DailyPostReminder($posts));

            $this->line("Reminder queued for {$user->email} ({$posts->count()} posts).");
            Log::info('Post reminder queued', [
                'user_id' => $user->id,
                'email' => $user->email,
                'post_count' => $posts->count(),
            ]);
        }

        $this->info("Reminders queued for {$users->count()} user(s).");

        return self::SUCCESS;
    }
}
