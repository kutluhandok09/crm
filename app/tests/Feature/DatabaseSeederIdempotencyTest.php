<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_can_run_multiple_times_without_unique_constraint_errors(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::query()->where('username', 'superadmin')->count());
        $this->assertTrue(
            User::query()->where('username', 'superadmin')->first()->hasRole('super-admin')
        );
    }
}
