<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';


use Bilo\Controller\MonitorController;
use Bilo\Controller\SearchController;
use Bilo\Service\ConfigService;
use Slim\Factory\AppFactory;
use Bilo\Controller\RecordController;
use Bilo\Middleware\OpenApiValidationMiddleware;

ini_set('display_errors', 1);
error_reporting(E_ALL);

$container = require __DIR__ . '/../config/container.php';

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->add($app->getContainer()->get(OpenApiValidationMiddleware::class));

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Define routes
$app->post('/api/v1/record', [RecordController::class, 'createRecord']);
$app->post('/api/v1/search/request', [SearchController::class, 'searchRecords']);
$app->get('/api/v1/search/status/{id}', [SearchController::class, 'searchStatus']);
$app->post('/api/v1/search/aggregate', [SearchController::class, 'aggregateRecords']);

$app->get('/index.php/monitor', [MonitorController::class, 'index']);
$app->get('/index.php/monitor/add/{queue}', [MonitorController::class, 'add']);
$app->get('/index.php/monitor/stop/{pid}', [MonitorController::class, 'stop']);


// Run the app
$app->run();
