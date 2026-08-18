<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class HealthcheckTest extends TestCase
{
    public function test_health_returns_ok_without_database(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }
}
