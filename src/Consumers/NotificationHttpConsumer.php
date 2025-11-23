<?php

declare(strict_types=1);

namespace Bilo\Consumers;

use Bilo\Consumers\Exception\DelayMessageException;
use Bilo\Enum\EnvVar;
use Bilo\Enum\Queue;
use Bilo\Service\ConfigService;
use Bilo\Service\Exception\ConfigurationNotFound;
use Bilo\Service\HttpService;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;


class NotificationHttpConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly HttpService $httpService,
        private readonly ConfigService $configService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws DelayMessageException
     */
    public function consume(string $message): void
    {
        try {
            $msg = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException("Invalid message data");
        }

        try {
            if (!$this->httpService->post(
                $this->configService->getRequired(EnvVar::NOTIFICATION_URL),
                $msg[0]
            )) {
                throw new DelayMessageException(10, Queue::delayed_notification);
            }
        } catch (ConfigurationNotFound $e) {
            $this->logger->error(basename(__CLASS__).':'.get_class($e).':'.$e->getMessage());
            throw new DelayMessageException(10, Queue::delayed_notification);
        }
    }

}