<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Bilo\Service\ConfigService;
use Bilo\Enum\EnvVar;
use Bilo\Middleware\OpenApiValidationMiddleware;
use GuzzleHttp\Client;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Build the PHP-DI container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    ConfigService::class => \DI\autowire(ConfigService::class),

    PDO::class => function(ConfigService $config) {
        $host = $config->getRequired(EnvVar::MYSQL_HOST);
        $port = $config->getRequired(EnvVar::MYSQL_PORT);
        $database = $config->getRequired(EnvVar::MYSQL_DATABASE);
        $user = $config->getRequired(EnvVar::MYSQL_USER);
        $password = $config->getRequired(EnvVar::MYSQL_PASSWORD);

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    },

    OpenApiValidationMiddleware::class => function(ConfigService $configService, LoggerInterface $logger) {
        return new OpenApiValidationMiddleware(
            $configService,
            $logger,
            __DIR__ . '/../public/docs.yml'
        );
    },

    LoggerInterface::class => function() {
        $logger = new MonologLogger('bilog');

        $logDir = __DIR__ . '/../logs';

        $handler = new RotatingFileHandler(
            $logDir . '/app.log',
            14,
            Level::Debug,
            true, // bubble
            0644, // file permissions
            false // use locking
        );

        $handler->setFilenameFormat('{filename}-{date}', 'Ymd');

        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        );
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);

        return $logger;
    },

        Client::class => function() {
            return new Client([
                'timeout' => 30.0,
                'connect_timeout' => 10.0,
                'http_errors' => true,
                'verify' => true,
            ]);
        },
]);

return $containerBuilder->build();
