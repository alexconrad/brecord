<?php

declare(strict_types=1);

namespace Bilo\Service;

use PDO;
use PDOException;

class MigrationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): array
    {
        $results = [];
        $migrationsPath = __DIR__ . '/../../migrations';

        // Get all SQL files sorted by name
        $files = glob($migrationsPath . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            $filename = basename($file);
            $version = pathinfo($filename, PATHINFO_FILENAME);

            // Check if migration already executed
            if ($this->isExecuted($version)) {
                $message = "SKIP: {$filename} (already executed)";
                $results[] = $message;
                echo $message . "\n";
                continue;
            }

            try {
                // Read and execute SQL
                $sql = file_get_contents($file);
                $sql = trim($sql);
                if (empty($sql)) {
                    echo "EMPTY MIGRATION $file\n";
                    continue;
                }
                $this->pdo->exec($sql);

                // Record migration
                $description = $this->extractDescription($sql);
                $this->recordMigration($version, $description);

                $message = "✓ SUCCESS: {$filename}" . ($description ? " - {$description}" : "");
                $results[] = $message;
                echo $message . "\n";
            } catch (PDOException $e) {
                $message = "✗ ERROR: {$filename} - " . $e->getMessage();
                $results[] = $message;
                echo $message . "\n";
                break; // Stop on first error
            }
        }

        return $results;
    }

    private function isExecuted(string $version): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM migrations WHERE version = ?");
            $stmt->execute([$version]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // If migrations table doesn't exist yet, migration hasn't been executed
            return false;
        }
    }

    private function recordMigration(string $version, string $description): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (version, description) VALUES (?, ?)");
        $stmt->execute([$version, $description]);
    }

    private function extractDescription(string $sql): string
    {
        // Extract description from SQL comment
        if (preg_match('/-- Description: (.+)$/m', $sql, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
}
