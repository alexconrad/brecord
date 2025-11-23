<?php

declare(strict_types=1);

namespace Bilo\Consumers;

use Bilo\Consumers\Exception\DelayMessageException;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Enum\EnvVar;
use Bilo\Enum\SearchRequestStatus;
use Bilo\Service\AggregationService;
use Bilo\Service\ConfigService;
use Bilo\Service\EntityBuilder;
use Bilo\Service\RecordService;
use Bilo\Service\SearchService;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use Throwable;

class SearchAggregateConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly AggregationService $aggregationService,
        private readonly EntityBuilder $entityBuilder,
        private readonly LoggerInterface $logger,
        private readonly ConfigService $configService,
        private readonly RecordService $recordService,
        private readonly SearchService $searchService,
    ) {
    }

    /**
     * @throws DelayMessageException
     */
    public function consume(string $message): void
    {
        try {
            $record = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException("Invalid message data");
        }

        $uuid = $record[0];
        $startDate = $record[1];
        $endDate = $record[2];

        try {
            $this->searchService->changeStatus($uuid, SearchRequestStatus::IN_PROGRESS);

            if (([$start, $end] = $this->getStartEndDate($startDate, $endDate)) === null) {
                $this->searchService->changeStatus($uuid, SearchRequestStatus::EMPTY);
                return;
            }

            $destinationGroup = $this->buildDestinationGroup($start, $end);

            $filename = $uuid.'.json';
            $reportDir = $this->configService->getRequired(EnvVar::REPORT_DIR);
            $fp = fopen($reportDir.$filename, 'wb+');
            if (!$fp) {
                throw new Exception("Can't open file $filename");
            }

            if (($bytes = fwrite($fp, json_encode($destinationGroup, JSON_THROW_ON_ERROR)))===false) {
                throw new Exception("Can't write file $filename");
            }

            fclose($fp);

            $this->searchService->changeStatus($uuid, SearchRequestStatus::DONE);

        } catch (Throwable $e) {
            $this->logger->error(basename(__CLASS__).':'.get_class($e).':'.$e->getMessage());
            $this->searchService->changeStatus($uuid, SearchRequestStatus::FAILED);
        }

    }

    /**
     * @return list<\DateTimeImmutable>
     * @throws \Bilo\Database\Exception\MySqlQueryException
     */
    private function getStartEndDate(?string $startDate, ?string $endDate): ?array
    {
        if ($startDate === null || $endDate === null) {
            //full table scan
            $firstDate = $this->recordService->firstDate();
            if ($firstDate === null) {
                //no data
                return null;
            }
            $start = $this->entityBuilder->buildDateTime($firstDate, EntityBuilder::MYSQL_DATE_FORMAT);
            $end = $this->entityBuilder->buildDateTime(EntityBuilder::NOW);

        } else {
            $start = $this->entityBuilder->buildDateTime($startDate);
            $end = $this->entityBuilder->buildDateTime($endDate);
        }

        return [$start, $end];
    }

    /**
     * @throws MySqlQueryException
     */
    private function buildDestinationGroup(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $conditions = $this->aggregationService->getAggregationRanges($start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'));

        $destinationGroup = [];

        foreach ($this->recordService->searchForAggregation($conditions->direct) as $record)
        {
            $dbRecordType = $record['record_type'] ? 'positive' : 'negative';

            if (!isset($destinationGroup[$record['id_destination']])) {
                $destinationGroup[$record['id_destination']] = [
                    'count_positive' => 0,
                    'count_negative' => 0,
                    'sum_positive' => 0,
                    'sum_negative' => 0,
                ];
            }

            $destinationGroup[$record['id_destination']]['count_'.$dbRecordType]++;
            $destinationGroup[$record['id_destination']]['sum_'.$dbRecordType] = bcadd(
                (string)$destinationGroup[$record['id_destination']]['sum_'.$dbRecordType],
                (string)$record['val'],
                ConfigService::DECIMAL_PLACES
            );
        }

        //aggregate
        foreach ($this->aggregationService->searchRaw( $conditions->aggregate) as $record)
        {
            if (!isset($destinationGroup[$record['id_destination']])) {
                $destinationGroup[$record['id_destination']] = [
                    'count_positive' => 0,
                    'count_negative' => 0,
                    'sum_positive' => 0,
                    'sum_negative' => 0,
                ];
            }

            foreach(['positive', 'negative'] as $recordType) {
                $destinationGroup[$record['id_destination']]['count_'.$recordType] += $record['count_'.$recordType];
                $destinationGroup[$record['id_destination']]['sum_'.$recordType] = bcadd(
                    (string) $destinationGroup[$record['id_destination']]['sum_'.$recordType],
                    (string) $record['sum_'.$recordType],
                    ConfigService::DECIMAL_PLACES
                );
            }
        }

        return $destinationGroup;

    }


}