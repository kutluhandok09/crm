<?php

namespace Tests\Unit;

use Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager;
use Tests\TestCase;

class TenancyDatabaseConfigTest extends TestCase
{
    public function test_mariadb_manager_is_registered_for_tenancy_database_creation(): void
    {
        $this->assertSame(
            MySQLDatabaseManager::class,
            config('tenancy.database.managers.mariadb')
        );
    }
}
