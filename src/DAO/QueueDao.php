<?php

declare(strict_types=1);


namespace Bilo\DAO;

use Bilo\Database\Connection;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Enum\Queue;
use JsonException;

class QueueDao
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * @throws MySqlQueryException|JsonException
     */
    public function claimMessage(Queue $queue): ?int
    {
        $order = (rand(1, 10) <= 5 ? '' : 'DESC');
        try {
            $this->connection->beginTransaction();
            if ($id = $this->connection->getOne(
                "SELECT id FROM queues WHERE queues.queue_id = ? AND deleted = 0 AND claimed = 0 AND process_after <= NOW() ORDER BY id {$order} LIMIT 1 FOR UPDATE",
                [$queue->value]
            )) {
                $this->connection->execute("UPDATE queues SET claimed = 1 WHERE id = ?", [$id]);
                $this->connection->commit();
                return (int) $id;
            }

            $this->connection->commit();
            return null;

        } catch (MySqlQueryException $e) {
            // Rollback on error
            if ($this->connection->inTransaction()) {
                $this->connection->rollback();
            }
            throw $e;
        }
    }

    public function getData(int $id): string
    {
        return $this->connection->getOne("SELECT msg FROM queue_data WHERE id = ?", [$id]);
    }

    /**
     * @throws MySqlQueryException
     */
    public function releaseMessage(int $id, int $backOffSeconds): void
    {
        if ($backOffSeconds <= 0) {
            $this->connection->execute("UPDATE queues SET claimed = 0 WHERE id = ?", [$id]);
        } else {
            $this->connection->execute("UPDATE queues SET claimed = 0, process_after = DATE_ADD(NOW(), INTERVAL ? SECOND ) WHERE id = ?", [$backOffSeconds, $id]);
        }
    }

    /**
     * @throws MySqlQueryException
     */
    public function releaseMessageAndMoveToQueue(int $id, Queue $moveToQueue, int $backOffSeconds): void
    {
        if ($backOffSeconds <= 0) {
            $this->connection->execute("UPDATE queues SET claimed = 0, queue_id = ? WHERE id = ?", [$moveToQueue->value, $id]);
        } else {
            $this->connection->execute(
                "UPDATE queues SET claimed = 0, process_after = DATE_ADD(NOW(), INTERVAL ? SECOND ), queue_id = ? WHERE id = ?",
                [$backOffSeconds, $moveToQueue->value, $id]
            );
        }
    }

    /**
     * @throws MySqlQueryException
     */
    public function deleteMessage(int $id): void
    {
        $this->connection->execute("UPDATE queues SET deleted = 1 WHERE id = ?", [$id]);
    }

    /**
     * @throws MySqlQueryException
     */
    public function insertMessage(Queue $queue, string $data, int $delaySeconds = 0): int
    {
        $this->connection->execute(
            "INSERT INTO queues SET queue_id = ? , claimed = 0, deleted = 0, process_after = NOW() + INTERVAL ? SECOND",
            [$queue->value, $delaySeconds]
        );
        $id = (int) $this->connection->lastInsertId();
        $this->connection->execute("INSERT INTO queue_data SET id = ? , msg = ? ", [$id, $data]);
        return $id;
    }

}