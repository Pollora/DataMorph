<?php

namespace Pollora\Datamorph\Tests\Integration;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Pollora\Datamorph\Providers\DatamorphServiceProvider;

class MigrationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [DatamorphServiceProvider::class];
    }

    /** @test */
    public function it_creates_datamorph_tables()
    {
        // Exécute les migrations
        $this->artisan('migrate');

        // Vérifie que les tables ont été créées
        $this->assertTrue(Schema::hasTable('datamorph_logs'));
        $this->assertTrue(Schema::hasTable('datamorph_errors'));

        // Vérifie la structure de la table datamorph_logs
        $this->assertTrue(Schema::hasColumns('datamorph_logs', [
            'id',
            'pipeline',
            'processed_rows',
            'execution_time',
            'status',
            'metadata',
            'created_at',
            'updated_at'
        ]));

        // Vérifie la structure de la table datamorph_errors
        $this->assertTrue(Schema::hasColumns('datamorph_errors', [
            'id',
            'pipeline',
            'error_message',
            'failed_data',
            'stage',
            'batch_number',
            'created_at',
            'updated_at'
        ]));
    }

    /** @test */
    public function it_can_rollback_migrations()
    {
        // Exécute puis annule les migrations
        $this->artisan('migrate');
        $this->artisan('migrate:rollback');

        // Vérifie que les tables ont été supprimées
        $this->assertFalse(Schema::hasTable('datamorph_logs'));
        $this->assertFalse(Schema::hasTable('datamorph_errors'));
    }
} 