#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Bilo\Command\AggregateCommand;
use Bilo\Command\QueueCommand;
use Bilo\Command\TestCommand;
use Symfony\Component\Console\Application;

// Load the container
$container = require __DIR__ . '/../config/container.php';


$app = new Application();
$app->add($container->get(QueueCommand::class));
$app->add($container->get(AggregateCommand::class));
$app->add($container->get(TestCommand::class));
$app->run();
