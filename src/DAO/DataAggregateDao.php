<?php

declare(strict_types=1);

namespace Bilo\DAO;

use Bilo\Database\Connection;
use Bilo\Entity\SearchInterval;
use Bilo\Enum\Sign;

class DataAggregateDao
{
    private const string DATED_FIELD = 'dated';

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @param  int  $idDestination
     * @param  int  $idReference
     * @param  SearchInterval[]  $searchIntervals
     * @return \Generator
     */
    public function searchDestinationReference(int $idDestination, int $idReference, array $searchIntervals): \Generator
    {
        [$where, $binds] = $this->makeWhere($searchIntervals);
        $query = "SELECT * FROM `data_agg` WHERE id_destination = ? AND id_reference = ? AND (".$where.')';
        return $this->connection->getRowYielder($query, [$idDestination, $idReference, ...$binds]);
    }

    public function searchDestination(int $idDestination, array $searchIntervals): \Generator
    {
        [$where, $binds] = $this->makeWhere($searchIntervals);
        $query = "SELECT * FROM `data_agg` WHERE id_destination = ? AND (".$where.')';
        return $this->connection->getRowYielder($query, [$idDestination, ...$binds]);
    }

    public function searchRaw(array $searchIntervals): \Generator
    {
        [$where, $binds] = $this->makeWhere($searchIntervals);
        $query = "SELECT id_destination, count_positive, count_negative, sum_positive, sum_negative FROM `data_agg` WHERE ".$where;
        return $this->connection->getRowYielder($query, [...$binds]);
    }

    /**
     * @param  SearchInterval[]  $conditions
     * @return array{string, string[]}
     */
    public function makeWhere(array $conditions): array
    {
        $where = [];
        $binds = [];

        foreach ($conditions as $searchInterval) {
            if ($searchInterval->startDate === Sign::LESS_THAN) {
                $where[] = self::DATED_FIELD." < ?";
                $binds[] = $searchInterval->endDate;
            } elseif ($searchInterval->startDate === Sign::GREATER_OR_EQUAL_THAN) {
                $where[] = self::DATED_FIELD." >= ?";
                $binds[] = $searchInterval->endDate;
            } else {
                $where[] = self::DATED_FIELD." BETWEEN ? AND ?";
                $binds[] = $searchInterval->startDate;
                $binds[] = $searchInterval->endDate;
            }
        }
        return [implode(' OR ', $where), $binds];
    }

}