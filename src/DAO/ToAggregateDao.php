<?php

declare(strict_types=1);

namespace Bilo\DAO;

use Bilo\Database\Connection;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Service\ConfigService;
use Psr\Log\LoggerInterface;
use Throwable;

class ToAggregateDao
{
    private const int CHUNK = 25;

    private int $lines_nr = 0;
    private string $lines = '';

    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws MySqlQueryException
     */
    public function insert(string $dated, int $id_destination, int $id_reference, float $val, int $positive): void
    {
        $sql = 'INSERT INTO to_aggregate (dated, id_destination, id_reference, val, positive) VALUES (?, ?, ?, ?, ?)';
        $this->connection->execute($sql, [$dated, $id_destination, $id_reference, $val, $positive]);
    }

    public function aggregate(): int
    {
        $sql = 'CREATE TABLE to_aggregate_processing LIKE to_aggregate;';
        $this->connection->execute($sql);

        $sql = 'RENAME TABLE 
            to_aggregate_processing TO to_aggregate_empty, 
            to_aggregate TO to_aggregate_processing,
            to_aggregate_empty TO to_aggregate
        ';
        $this->connection->execute($sql);

        $start = 0;
        $lines = [];
        $this->lines_nr = 0;
        $this->lines = '';

        $cnt = 0;
        do {
            /** @noinspection SqlResolve */
            $rows = $this->connection->getAll('SELECT * FROM to_aggregate_processing LIMIT ?, ?', [$start, self::CHUNK]);
            foreach ($rows as $row) {
                $cnt++;
                $hourDate = date('Y-m-d H:00:00', strtotime($row['dated']));

                if (!isset($lines[$hourDate][$row['id_destination']][$row['id_reference']])) {
                    $lines[$hourDate][$row['id_destination']][$row['id_reference']] = [
                        'count_positive' => 0,
                        'count_negative' => 0,
                        'sum_positive' => 0,
                        'sum_negative' => 0,
                    ];
                }

                $reportType = ($row['positive']) ? 'positive' : 'negative';
                $lines[$hourDate][$row['id_destination']][$row['id_reference']]['count_'.$reportType]++;
                $lines[$hourDate][$row['id_destination']][$row['id_reference']]['sum_'.$reportType] = bcadd(
                    (string) $lines[$hourDate][$row['id_destination']][$row['id_reference']]['sum_'.$reportType],
                    (string) $row['val'],
                    ConfigService::DECIMAL_PLACES
                );

            }
            $start += self::CHUNK;
        } while (!empty($rows) && count($rows) === self::CHUNK);

        $this->connection->beginTransaction();
        try {
            foreach ($lines as $hourDate => $destinations) {
                foreach ($destinations as $idDestination => $references) {
                    foreach ($references as $idReference => $report) {
                        $this->processLine(false, $hourDate, $idDestination, $idReference, $report);
                    }
                }
            }

            $this->processLine(true, null, null, null, null);
            $this->connection->commit();

        } catch (Throwable $e) {
            $this->logger->error(get_class($e).':'.$e->getMessage());
            $this->connection->rollback();
            throw $e;
        }

        /** @noinspection SqlResolve */
        $this->connection->execute('DROP TABLE to_aggregate_processing;');

        return $cnt;
    }

    private function processLine(bool $flush, ?string $hourDate, ?int $idDestination, ?int $idReference, ?array $report): void
    {
        if (!$flush) {
            $this->lines_nr++;
            $this->lines .= "('$hourDate', $idDestination, $idReference, {$report['count_positive']}, {$report['count_negative']}, {$report['sum_positive']}, {$report['sum_negative']}),";
        }

        if ($this->lines_nr > self::CHUNK || ($this->lines_nr !== 0 && $flush)) {
            $sql = 'INSERT INTO data_agg (dated, id_destination, id_reference, count_positive, count_negative, sum_positive, sum_negative) VALUES ';
            $sql .= substr($this->lines, 0, -1);
            $sql .= ' ON DUPLICATE KEY UPDATE 
                    count_positive = count_positive + VALUES(count_positive), 
                    count_negative = count_negative + VALUES(count_negative), 
                    sum_positive = sum_positive + VALUES(sum_positive), 
                    sum_negative = sum_negative + VALUES(sum_negative);
                ';
            $this->connection->execute($sql);

            $this->lines_nr = 0;
            $this->lines = '';
        }

    }

}