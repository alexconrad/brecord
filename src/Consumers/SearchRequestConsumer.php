<?php

declare(strict_types=1);

namespace Bilo\Consumers;

use Bilo\Consumers\Exception\DelayMessageException;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Entity\Record;
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

class SearchRequestConsumer implements ConsumerInterface
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
        $recordType = $record[3];

        $positive = null;
        if ($recordType === 'positive') {
            $positive = true;
        } elseif ($recordType === 'negative') {
            $positive = false;
        }

        try {
            $this->searchService->changeStatus($uuid, SearchRequestStatus::IN_PROGRESS);

            if (([$start, $end] = $this->getStartEndDate($startDate, $endDate)) === null) {
                $this->searchService->changeStatus($uuid, SearchRequestStatus::EMPTY);
                return;
            }

            $filename = $uuid.'.csv';
            $reportDir = $this->configService->getRequired(EnvVar::REPORT_DIR);
            $fp = fopen($reportDir.$filename, 'wb+');
            if (!$fp) {
                throw new Exception("Can't open file $filename");
            }

            $this->writeReport($fp, $start, $end, $positive);

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
    private function writeReport($fp, DateTimeImmutable $start, DateTimeImmutable $end, ?bool $positive): void
    {
        $writeHeader = true;
        foreach ($this->aggregationService->hourlySegments($start, $end) as $hourlyRange) {
            foreach ($this->recordService->searchInterval($hourlyRange[0], $hourlyRange[1], $positive) as $record) {
                /** @var $record Record */
                if ($writeHeader) {
                    if (false === fputcsv($fp, array_keys($record->jsonSerialize()), escape: '')) {
                        throw new Exception("Can't write header");
                    }
                    $writeHeader = false;
                }
                fputcsv($fp, array_values($record->jsonSerialize()), escape: '');
            }
        }
    }


}