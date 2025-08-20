<?php

namespace App\Database;

use App\Database\Migrations\CreateProjectsTable;
use App\Database\Migrations\CreateMockEndpointsTable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Log\LoggerInterface;

class MigrationRunner
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Capsule
     */
    private $capsule;

    /**
     * MigrationRunner constructor.
     *
     * @param LoggerInterface $logger
     * @param Capsule $capsule
     */
    public function __construct(LoggerInterface $logger, Capsule $capsule)
    {
        $this->logger = $logger;
        $this->capsule = $capsule;
    }

    /**
     * Run all migrations
     *
     * @return void
     */
    public function runMigrations()
    {
        $this->logger->info('Running database migrations...');

        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();

        // Get list of migrations that have already been run
        $ranMigrations = $this->getRanMigrations();

        // Define migrations in order
        $migrations = [
            '001_create_projects_table' => CreateProjectsTable::class,
            '002_create_mock_endpoints_table' => CreateMockEndpointsTable::class,
        ];

        // Run each migration that hasn't been run yet
        foreach ($migrations as $name => $class) {
            if (!in_array($name, $ranMigrations)) {
                $this->logger->info("Running migration: {$name}");
                $migration = new $class();
                $migration->up();
                $this->logMigration($name);
            }
        }

        $this->logger->info('Database migrations completed.');
    }

    /**
     * Create migrations table if it doesn't exist
     *
     * @return void
     */
    private function createMigrationsTable()
    {
        if (!$this->capsule::schema()->hasTable('migrations')) {
            $this->capsule::schema()->create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Get list of migrations that have already been run
     *
     * @return array
     */
    private function getRanMigrations()
    {
        return $this->capsule::table('migrations')
            ->pluck('migration')
            ->toArray();
    }

    /**
     * Log that a migration has been run
     *
     * @param string $name
     * @return void
     */
    private function logMigration($name)
    {
        $this->capsule::table('migrations')->insert([
            'migration' => $name
        ]);
    }
}