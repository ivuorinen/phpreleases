<?php

namespace Tests\Feature;

use App\Models\Hit;
use App\Notifications\WeeklyStats;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_stores_the_hit()
    {
        $this->getJson('api/releases/8.0.0', [
            'User-Agent' => 'My Test Agent',
        ]);

        $this->assertDatabaseHas('hits', [
            'endpoint' => '/api/releases/8.0.0',
            'user_agent' => 'My Test Agent',
        ]);
    }

    /** @test */
    public function it_ignores_bot_user_agents()
    {
        $this->getJson('/', [
            'User-Agent' => 'Mozilla/5.0 (compatible; DuckDuckGo-Favicons-Bot/1.0; +http://duckduckgo.com)',
        ]);

        $this->assertDatabaseMissing('hits', [
            'endpoint' => '/',
            'user_agent' => 'Mozilla/5.0 (compatible; DuckDuckGo-Favicons-Bot/1.0; +http://duckduckgo.com)',
        ]);
    }

    /** @test */
    public function it_calculates_percent_increase()
    {
        Hit::factory()
            ->count(2)
            ->create([
                'created_at' => CarbonImmutable::now()->subWeek()->subDay(),
            ]);

        Hit::factory()
            ->count(6)
            ->create([
                'created_at' => CarbonImmutable::now(),
            ]);

        $hits = Hit::forTimePeriod('week');

        $this->assertSame([
            'current' => 6,
            'previous' => 2,
            'changePercent' => 200,
        ], $hits);
    }

    /** @test */
    public function it_handles_a_percent_decrease()
    {
        Hit::factory()
            ->count(6)
            ->create([
                'created_at' => CarbonImmutable::now()->subMonth()->subDay(),
            ]);

        Hit::factory()
            ->count(2)
            ->create([
                'created_at' => CarbonImmutable::now(),
            ]);

        $hits = Hit::forTimePeriod('month');

        $this->assertSame([
            'current' => 2,
            'previous' => 6,
            'changePercent' => -66,
        ], $hits);
    }

    /** @test */
    public function it_handles_first_period_values()
    {
        Hit::factory()
            ->count(2)
            ->create([
                'created_at' => CarbonImmutable::now(),
            ]);

        $hits = Hit::forTimePeriod('year');

        $this->assertSame([
            'current' => 2,
            'previous' => 0,
            'changePercent' => 100,
        ], $hits);
    }

    /** @test */
    public function the_slack_notification_is_sent()
    {
        Notification::fake();

        $this->artisan('stats:send')
            ->assertExitCode(0);

        Notification::assertSentTo(
            new AnonymousNotifiable(),
            WeeklyStats::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['slack'] == 'http://localhost';
            }
        );
    }
}
