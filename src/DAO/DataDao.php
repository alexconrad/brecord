<?php

declare(strict_types=1);

namespace Bilo\DAO;

use Bilo\Database\Connection;
use Bilo\Database\Exception\DuplicateException;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Entity\SearchInterval;
use Bilo\Enum\Sign;
use Generator;

class DataDao
{
    private const string DATE_FIELD = 'dated';

    public function __construct(
        private readonly Connection $connection
    )
    {
    }

    /**
     * @throws MySqlQueryException
     */
    public function startTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * @throws MySqlQueryException
     */
    public function commitTransaction(): void
    {
        $this->connection->commit();
    }

    public function rollbackTransaction(): void {
        $this->connection->rollback();
    }

    /**
     * @throws DuplicateException|MySqlQueryException
     */
    public function insertRecord(
        int $id_record,
        string $dated,
        int $id_source,
        int $id_destination,
        int $record_type,
        float $val,
        int $unit_id,
        int $id_reference,
    ): void {
        $sql = '
            INSERT INTO `data` 
            SET
                id_record = ?,
                dated = ?,
                id_source = ?,
                id_destination = ?,
                record_type = ?,
                val = ?,
                id_unit = ?,
                id_reference = ?
        ';
        $this->connection->executeDuplicateAware($sql, [$id_record, $dated, $id_source, $id_destination, $record_type, $val, $unit_id, $id_reference]);
    }


    public function searchDestinationReference(int $idDestination, int $idReference, array $conditions)
    {
        [$where, $binds] = $this->makeWhere($conditions);
        $query = "SELECT * FROM `data` WHERE id_destination = ? AND id_reference = ? AND (".$where.')';
        $binds = [$idDestination, $idReference, ...$binds];

        return $this->connection->getRowYielder($query, $binds);

    }

    /**
     * @throws MySqlQueryException
     */
    public function firstDate(): ?string
    {
        return $this->connection->getOne("SELECT dated FROM `data` ORDER BY dated LIMIT 1");
    }

    /**
     * @throws MySqlQueryException
     */
    public function search(string $startDate, string $endDate, ?bool $positive): Generator
    {
        $sql = "
            SELECT
                records.recordId,
                `data`.dated AS `time`,
                sources.sourceId,
                destinations.destinationId,
                data.record_type AS `type`,
                data.val AS `value`,
                units.unit,
                refs.ref AS `reference`
            FROM 
                `data`
                INNER JOIN records ON records.id_record = `data`.id_record
                INNER JOIN destinations ON destinations.id_destination = `data`.id_destination
                INNER JOIN sources ON sources.id_source = `data`.id_source
                INNER JOIN refs ON refs.id_reference = `data`.id_reference
                INNER JOIN units ON units.id_unit = `data`.id_unit
            WHERE 
                `data`.dated BETWEEN ? AND ?
    ";

        if ($positive !== null) {
            $sql .= " AND `data`.record_type = ".($positive ? 1 : 0);
        }

        return $this->connection->getRowYielder($sql, [$startDate, $endDate]);
    }

    public function searchRaw(array $conditions): Generator
    {
        [$where, $binds] = $this->makeWhere($conditions);
        $query = "SELECT * FROM `data` WHERE ".$where.'';
        return $this->connection->getRowYielder($query, [... $binds]);
    }

    /**
     * @param  SearchInterval[]  $conditions
     * @return array{string, string[]}
     */
    private function makeWhere(array $conditions): array
    {
        $where = [];
        $binds = [];

        foreach ($conditions as $searchInterval) {
            if ($searchInterval->startDate === Sign::LESS_THAN) {
                $where[] = self::DATE_FIELD ." < ?";
                $binds[] = $searchInterval->endDate;
            }elseif ($searchInterval->startDate === Sign::GREATER_OR_EQUAL_THAN) {
                $where[] = self::DATE_FIELD ." >= ?";
                $binds[] = $searchInterval->endDate;
            }else{
                $where[] = self::DATE_FIELD ." BETWEEN ? AND ?";
                $binds[] = $searchInterval->startDate;
                $binds[] = $searchInterval->endDate;
            }
        }
        return [ implode(' OR ', $where) , $binds ];
    }



}