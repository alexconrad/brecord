<?php

declare(strict_types=1);

namespace Bilo\Consumers;

use Bilo\Consumers\Exception\DelayMessageException;
use Bilo\Enum\Queue;
use Bilo\Service\EntityBuilder;
use Bilo\Service\NotificationService;
use Bilo\Service\QueueService;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use Throwable;

class NotificationConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly EntityBuilder $entityBuilder,
        private readonly NotificationService $notificationService,
        private readonly QueueService $queueService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws DelayMessageException
     */
    public function consume(string $message): void
    {
        try {
            $record = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException("Invalid message data");
        }

        try {
            $notification = $this->notificationService->getNotification(
                $this->entityBuilder->buildRecord($record[0])
            );

            $this->queueService->publishMessage(Queue::notification_http, 0, $notification);
        } catch (Throwable $e) {
            $this->logger->error('NOTIFICATION_CONSUMER:'.get_class($e).': ' .$e->getMessage());
            throw new DelayMessageException(5, Queue::delayed_notification);
        }

    }

}