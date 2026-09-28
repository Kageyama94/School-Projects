<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class DevCommandsTest extends TestCase
{
    public function test_dev_only_starts_the_web_server_because_the_queue_is_synchronous(): void
    {
        $this->assertSame('sync', config('queue.default'));

        $this->artisan('dev:list')
            ->expectsOutputToContain('php artisan serve')
            ->doesntExpectOutputToContain('queue:listen')
            ->assertSuccessful();
    }
}
