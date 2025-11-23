<?php

declare(strict_types=1);

namespace Bilo\Service;

use Bilo\Consumers\ConsumerFactory;
use Bilo\Consumers\Exception\DelayMessageException;
use Bilo\DAO\QueueDao;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Enum\Queue;
use JsonSerializable;

class QueueService
{
    public function __construct(
        private readonly QueueDao $queueDao,
        private readonly ConsumerFactory $consumerFactory,
    )
    {
    }

    /**
     * @param  Queue  $queue
     * @return array<int, JsonSerializable>|null
     * @throws MySqlQueryException
     * @throws \JsonException
     */
    public function getMessage(Queue $queue): ?array
    {
        if ($id = $this->queueDao->claimMessage($queue)) {
            return [$id, $this->queueDao->getData($id)];
        }
        return null;

    }

    public function publishMessage(Queue $queue, int $delaySeconds, mixed ... $data): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->queueDao->insertMessage($queue, json_encode($data, JSON_THROW_ON_ERROR), $delaySeconds);
    }

    public function releaseMessage(int $id, int $backOffSeconds, ?Queue $moveToQueue): void
    {
        if ($moveToQueue !== null) {
            $this->queueDao->releaseMessageAndMoveToQueue($id, $moveToQueue, $backOffSeconds);
        } else {
            $this->queueDao->releaseMessage($id, $backOffSeconds);
        }
    }

    public function deleteMessage(int $id): void
    {
        $this->queueDao->deleteMessage($id);
    }

    /**
     * @throws DelayMessageException
     */
    public function consume(Queue $queue, string $message): void
    {
        $this->consumerFactory
            ->create($queue)
            ->consume($message);

    }

}