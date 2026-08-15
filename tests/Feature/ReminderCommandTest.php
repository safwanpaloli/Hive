<?php

namespace Tests\Feature;

use App\Console\Commands\SendPostReminders;
use App\Models\Post;
use App\Models\User;
use App\Notifications\DailyPostReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_queues_reminders_for_todays_scheduled_posts(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->setTime(10, 0),
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->setTime(15, 0),
        ]);

        $this->artisan(SendPostReminders::class)
            ->expectsOutputToContain("Reminder queued for {$user->email}")
            ->assertExitCode(0);

        Notification::assertSentTo($user, DailyPostReminder::class);
    }

    public function test_command_is_silent_when_nothing_is_scheduled(): void
    {
        Notification::fake();

        User::factory()->create();

        $this->artisan(SendPostReminders::class)
            ->expectsOutputToContain('No posts scheduled for today.')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }
}
