<?php

declare(strict_types=1);

namespace Bilo\Database;

use Bilo\Database\Exception\DuplicateException;
use Bilo\Database\Exception\MySqlQueryException;
use Generator;
use PDO;
use PDOException;
use RuntimeException;

class Connection
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @throws MySqlQueryException
     */
    public function getRow(string $query, array $bindings = []): ?array
    {
        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute($bindings);

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            throw new MySqlQueryException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @throws MySqlQueryException
     */
    public function getAll(string $query, array $bindings = []): array
    {
        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute($bindings);

            $rows = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $rows[] = $row;
            }

            return $rows;
        } catch (PDOException $e) {
            throw new MySqlQueryException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function getRowYielder(string $query, array $bindings = []): Generator
    {
        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute($bindings);

            $rows = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                yield $row;
            }

        } catch (PDOException $e) {
            throw new MySqlQueryException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }


    public function getOne(string $query, array $bindings = [], int $index = 0): ?string
    {
        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute($bindings);

            $row = $statement->fetch(PDO::FETCH_NUM);
            if ($row === false) {
                return null;
            }
            if (!isset($row[$index])) {
                throw new \UnexpectedValueException('Invalid index: ' . $index);
            }

            return (string)$row[$index];

        } catch (PDOException $e) {

            throw new MySqlQueryException($e->getMessage()."||||".$query."||||".implode(',',$bindings), (int)$e->getCode(), $e);
        }
    }

    /**
     * @throws MySqlQueryException
     */
    public function execute(string $query, array $bindings = []): void
    {
        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute($bindings);
        } catch (PDOException $e) {
            throw new MySqlQueryException($e->getMessage().'|||'.$query, (int) $e->getCode(), $e);
        }
    }

    /**
     * @throws MySqlQueryException
     * @throws DuplicateException
     */
    public function executeDuplicateAware(string $query, array $bindings = []): void
    {
        try {
            $this->execute($query, $bindings);
        } catch (MySqlQueryException $e) {
            if ($e->getPrevious()?->getCode() === '23000') {
                throw new DuplicateException($e->getMessage().'|||'.$query, (int) $e->getCode(), $e);
            }
            throw $e;
        }
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * @throws MySqlQueryException
     */
    public function beginTransaction(): void
    {
        $started = $this->pdo->beginTransaction();
        if ($started === false) {
            throw new MySqlQueryException('Failed to start transaction');
        }
    }

    /**
     * @throws MySqlQueryException
     */
    public function commit(): void
    {
        $commit = $this->pdo->commit();
        if ($commit === false) {
            throw new MySqlQueryException('Failed to commit transaction');
        }
    }

    public function rollback(): void
    {
        $rollback = $this->pdo->rollBack();
        if ($rollback === false) {
            throw new RuntimeException('Failed to rollback transaction');
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}
