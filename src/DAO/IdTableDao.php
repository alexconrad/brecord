<?php

declare(strict_types=1);

namespace Bilo\DAO;

use Bilo\Database\Connection;
use Bilo\Database\Exception\DuplicateException;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Enum\IdTable;

class IdTableDao
{

    public function __construct(private readonly Connection $connection)
    {

    }

    public function getField(IdTable $table, int $id): ?string
    {
        $query = "SELECT * FROM {$table->name} WHERE {$table->value} = ?";
        return $this->connection->getOne($query, [$id], 1);
    }

    /**
     * @throws MySqlQueryException|DuplicateException
     */
    public function insert(IdTable $table, string $value): int
    {
        $sql = "INSERT INTO {$table->name} ({$table->getField()}) VALUES (?)";
        $this->connection->executeDuplicateAware($sql, [$value]);
        return (int)$this->connection->lastInsertId();
    }

    /**
     * @throws MySqlQueryException
     */
    public function getId(IdTable $table, string $fieldValue): ?int
    {
        $one = $this->connection->getOne("SELECT {$table->value} FROM {$table->name} WHERE ".$table->getField()." = ?", [$fieldValue]);
        if ($one === null) {
            return null;
        }
        return (int) $one;
    }

}