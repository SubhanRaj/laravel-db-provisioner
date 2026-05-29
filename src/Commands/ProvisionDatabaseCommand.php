<?php

namespace Subhanraj\LaravelDbProvisioner\Commands;

use Exception;
use Illuminate\Console\Command;
use PDO;
use PDOException;

class ProvisionDatabaseCommand extends Command
{
    protected $signature = 'db:provision';

    protected $description = 'Provision local database and user for development';

    public function handle(): int
    {
        try {
            $this->info('🔧 Starting database provisioning...');

            // Step 1: Check and create .env if needed
            $this->ensureEnvFile();

            // Step 2: Generate database credentials
            $credentials = $this->generateCredentials();
            $dbName = $credentials['database'];
            $dbUser = $credentials['username'];
            $dbPassword = $credentials['password'];

            // Step 3: Update .env file
            $this->updateEnvFile($dbName, $dbUser, $dbPassword);

            // Step 4: Refresh environment
            $this->refreshEnvironment();

            // Step 5: Get admin credentials
            $adminUsername = $this->ask('Enter database admin username', 'root');
            $adminPassword = $this->secret('Enter database admin password (leave blank if none)');

            // Step 6: Create database and user via PDO
            $this->provisionDatabase($dbName, $dbUser, $dbPassword, $adminUsername, $adminPassword);

            // Step 7: Output success
            $this->outputSuccess($dbName, $dbUser);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Ensure .env file exists, copy from .env.example if needed
     */
    private function ensureEnvFile(): void
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (! file_exists($envPath)) {
            if (! file_exists($envExamplePath)) {
                throw new Exception('.env.example file not found. Please create it first.');
            }

            if (! copy($envExamplePath, $envPath)) {
                throw new Exception('Failed to copy .env.example to .env');
            }

            $this->info('✓ Created .env from .env.example');
        } else {
            $this->info('✓ .env file already exists');
        }
    }

    /**
     * Generate database name and credentials
     */
    private function generateCredentials(): array
    {
        // Generate database name from APP_NAME or current directory
        $appName = env('APP_NAME', basename(base_path()));
        $dbName = $this->slugify($appName) . '_local';

        // Generate secure 16-character password
        $dbPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%'), 0, 16);

        return [
            'database' => $dbName,
            'username' => $dbName,
            'password' => $dbPassword,
        ];
    }

    /**
     * Convert a string to a slug
     */
    private function slugify(string $text): string
    {
        // Remove special characters and spaces
        $text = preg_replace('/[^a-z0-9]+/i', '_', $text);
        // Convert to lowercase
        $text = strtolower($text);
        // Remove leading/trailing underscores
        $text = trim($text, '_');

        return $text;
    }

    /**
     * Update .env file with new credentials
     */
    private function updateEnvFile(string $dbName, string $dbUser, string $dbPassword): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        if ($envContent === false) {
            throw new Exception('Failed to read .env file');
        }

        // Update DB_DATABASE
        $envContent = preg_replace(
            '/^DB_DATABASE=.*$/m',
            "DB_DATABASE={$dbName}",
            $envContent
        );

        // Update DB_USERNAME
        $envContent = preg_replace(
            '/^DB_USERNAME=.*$/m',
            "DB_USERNAME={$dbUser}",
            $envContent
        );

        // Update DB_PASSWORD
        $envContent = preg_replace(
            '/^DB_PASSWORD=.*$/m',
            "DB_PASSWORD={$dbPassword}",
            $envContent
        );

        // Ensure DB_HOST and DB_PORT are set for local development
        if (! preg_match('/^DB_HOST=/m', $envContent)) {
            $envContent .= "\nDB_HOST=127.0.0.1";
        }
        if (! preg_match('/^DB_PORT=/m', $envContent)) {
            $envContent .= "\nDB_PORT=3306";
        }

        if (file_put_contents($envPath, $envContent) === false) {
            throw new Exception('Failed to write to .env file');
        }

        $this->info('✓ Updated .env with database credentials');
    }

    /**
     * Refresh environment variables in memory
     */
    private function refreshEnvironment(): void
    {
        try {
            if (class_exists(\Dotenv\Dotenv::class)) {
                $dotenv = \Dotenv\Dotenv::createMutable(base_path());
                $dotenv->safeLoad();
            }
        } catch (\Throwable $e) {
            // Continue anyway - it's just a fallback
        }

        $this->info('✓ Environment variables refreshed');
    }

    /**
     * Provision database using raw PDO
     */
    private function provisionDatabase(string $dbName, string $dbUser, string $dbPassword, string $adminUsername, ?string $adminPassword): void
    {
        try {
            // Get database host and port from environment
            $dbHost = env('DB_HOST', '127.0.0.1');
            $dbPort = env('DB_PORT', '3306');

            // Build PDO DSN
            $dsn = "mysql:host={$dbHost};port={$dbPort}";

            // Create PDO connection as admin
            $pdo = new PDO(
                $dsn,
                $adminUsername,
                $adminPassword ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                ]
            );

            $this->info("✓ Connected to database as {$adminUsername}");

            // Create database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
            $this->info("✓ Created database: {$dbName}");

            // Create users for both localhost and 127.0.0.1
            $pdo->exec("CREATE USER IF NOT EXISTS '{$dbUser}'@'127.0.0.1' IDENTIFIED BY '{$dbPassword}'");
            $pdo->exec("CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPassword}'");
            $this->info("✓ Created database users for {$dbUser}");

            // Grant privileges
            $pdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'127.0.0.1'");
            $pdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost'");
            $this->info("✓ Granted privileges to {$dbUser}");

            // Flush privileges
            $pdo->exec("FLUSH PRIVILEGES");
            $this->info("✓ Flushed privileges");

            $pdo = null;
        } catch (PDOException $e) {
            throw new Exception('Database provisioning failed: ' . $e->getMessage());
        }
    }

    /**
     * Output success message
     */
    private function outputSuccess(string $dbName, string $dbUser): void
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║  ✓ Database Provisioning Complete!    ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();
        $this->line("  Database Name: <fg=green>{$dbName}</>");
        $this->line("  Database User: <fg=green>{$dbUser}</>");
        $this->newLine();
        $this->warn('  Next steps:');
        $this->line('  1. Run: php artisan key:generate');
        $this->line('  2. Run: php artisan migrate');
        $this->newLine();
    }
}
