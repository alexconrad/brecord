<?php

namespace Bilo\Service;

use Bilo\DAO\IdTableDao;
use Bilo\Database\Exception\DuplicateException;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Enum\IdTable;
use InvalidArgumentException;
use RuntimeException;

class TableIdService
{
    public const int MAX_DEPTH = 10;

    public function __construct(private readonly IdTableDao $idTable)
    {

    }

    /**
     * @throws MySqlQueryException
     */
    public function getOrCreateId(IdTable $idTable, string $value, int $depth = 0): int
    {
        //TODO cache latest

        if ($depth > self::MAX_DEPTH) {
            throw new RuntimeException("Too many recursions");
        }

        if ($id = $this->getId($idTable, $value)) {
            return $id;
        }

        try {
            return $this->insertId($idTable, $value);
        } catch (DuplicateException) {
            usleep(10000);
            return $this->getOrCreateId($idTable, $value, ++$depth);
        }
    }

    /**
     * @throws MySqlQueryException
     */
    public function getId(IdTable $idTable, string $value): ?int
    {
        return $this->idTable->getId($idTable, $value);
    }

    /**
     * @throws MySqlQueryException
     */
    public function getRequiredFieldValue(IdTable $idTable, int $id): string
    {
        $fieldValue = $this->idTable->getField($idTable, $id);
        if ($fieldValue === null) {
            throw new InvalidArgumentException('No filename found for idFile');
        }
        return $fieldValue;
    }

    /**
     * @throws MySqlQueryException
     */
    public function getFieldValue(IdTable $idTable, int $id): ?string
    {
        return $this->idTable->getField($idTable, $id);
    }

    /**
     * @throws MySqlQueryException|DuplicateException
     */
    public function insertId(IdTable $idTable, string $value): int
    {
        return $this->idTable->insert($idTable, $value);
    }
}