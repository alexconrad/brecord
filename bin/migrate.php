#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Bilo\Service\MigrationService;

// Load the container
$container = require __DIR__ . '/../config/container.php';

// Get MigrationService from container
$migrationService = $container->get(MigrationService::class);

echo "Running migrations...\n\n";

try {
    $results = $migrationService->run();
    
    foreach ($results as $result) {
        echo $result . "\n";
    }
    
    echo "\nMigrations completed.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
