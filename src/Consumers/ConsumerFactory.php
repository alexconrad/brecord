<?php

declare(strict_types=1);

namespace Bilo\Consumers;

use Bilo\Enum\Queue;
use Psr\Container\ContainerInterface;

class ConsumerFactory
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function create(Queue $queue): ConsumerInterface
    {
        if ($queue === Queue::alert) {
            return $this->container->get(AlertConsumer::class);
        }
        if ($queue === Queue::notification) {
            return $this->container->get(NotificationConsumer::class);
        }
        if ($queue === Queue::notification_http) {
            return $this->container->get(NotificationHttpConsumer::class);
        }
        if ($queue === Queue::search_request) {
            return $this->container->get(SearchRequestConsumer::class);
        }
        if ($queue === Queue::search_aggregate) {
            return $this->container->get(SearchAggregateConsumer::class);
        }

        throw new \InvalidArgumentException("Unknown queue: $queue->name");

    }
}