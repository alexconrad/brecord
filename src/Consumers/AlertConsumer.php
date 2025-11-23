<?php

declare(strict_types=1);

namespace Bilo\Consumers;

use Bilo\Consumers\Exception\DelayMessageException;
use Bilo\Enum\EnvVar;
use Bilo\Enum\Queue;
use Bilo\Service\ConfigService;
use Bilo\Service\EntityBuilder;
use Bilo\Service\Exception\ConfigurationNotFound;
use Bilo\Service\HttpService;
use InvalidArgumentException;

class AlertConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly HttpService $httpService,
        private readonly ConfigService $configService,
        private readonly EntityBuilder $entityBuilder,
    ) {
    }

    /**
     * @throws ConfigurationNotFound
     * @throws DelayMessageException
     */
    public function consume(string $message): void
    {
        try {
            $data = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException("Invalid message data");
        }

        if (!$this->httpService->post(
            $this->configService->getRequired(EnvVar::ALERT_NOTIFICATION_URL),
            [
                'threshold' => $data[0],
                'record' => $data[1],
            ])) {
            throw new DelayMessageException(10, Queue::delayed_alert);
        }
    }

}