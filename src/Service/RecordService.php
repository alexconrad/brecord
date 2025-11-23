<?php

declare(strict_types=1);

namespace Bilo\Service;

use Bilo\DAO\DataDao;
use Bilo\DAO\ToAggregateDao;
use Bilo\Database\Exception\DuplicateException;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Entity\Record;
use Bilo\Entity\SearchInterval;
use Bilo\Enum\IdTable;
use Bilo\Service\Exception\EntryAlreadyExists;
use Bilo\Service\Exception\ServiceException;
use DateTimeImmutable;
use DateTimeZone;
use Generator;

class RecordService
{
    public function __construct(
        private readonly TableIdService $entityIdService,
        private readonly DataDao $dataDao,
        private readonly ToAggregateDao $toAggregateDao,
        private readonly EntityBuilder $entityBuilder,
    ) {
    }

    /**
     * @throws EntryAlreadyExists|ServiceException
     */
    public function createRecord(Record $record): void
    {
        try {
            $this->dataDao->startTransaction();
        } catch (MySqlQueryException $e) {
            throw new ServiceException("Could start transaction", 0, $e);
        }

        try {
            $idRecord = $this->entityIdService->insertId(IdTable::records, $record->recordId);
        } catch (DuplicateException $e) {
            throw new EntryAlreadyExists("Duplicate record: {$record->recordId}");
        } catch (MySqlQueryException $e) {
            $this->dataDao->rollbackTransaction();
            throw new ServiceException("Could not save record: {$record->recordId}", 0, $e);
        }

        try {
            $idDestination = $this->entityIdService->getOrCreateId(IdTable::destinations, $record->destinationId);
            $idReference = $this->entityIdService->getOrCreateId(IdTable::refs, $record->reference);
            $idSource = $this->entityIdService->getOrCreateId(IdTable::sources, $record->sourceId);
            $idUnit = $this->entityIdService->getOrCreateId(IdTable::units, $record->unit);

            $dated = $record->time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $positive = $record->positive ? 1 : 0;

            $this->dataDao->insertRecord(
                $idRecord,
                $dated,
                $idSource,
                $idDestination,
                $positive,
                $record->value,
                $idUnit,
                $idReference
            );

            $this->toAggregateDao->insert($dated, $idDestination, $idReference, $record->value, $positive);


        } catch (DuplicateException|MySqlQueryException $e) {
            //alert
            $this->dataDao->rollbackTransaction();
            throw new ServiceException(get_class($e).": problem inserting record record: {$record->recordId}", 0, $e);
        }

        try {
            $this->dataDao->commitTransaction();
        } catch (MySqlQueryException $e) {
            throw new ServiceException("Could not commit", 0, $e);
        }
    }

    /**
     * @param  int  $idDestination
     * @param  int  $idReference
     * @param  SearchInterval[]|non-empty-array  $conditions
     * @return Generator
     */
    public function searchDestinationReference(int $idDestination, int $idReference, array $conditions): Generator
    {
        return $this->dataDao->searchDestinationReference($idDestination, $idReference, $conditions);
    }

    /**
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  bool|null  $positive
     * @return Generator|Record[]
     * @throws MySqlQueryException
     */
    public function searchInterval(string $startDate, string $endDate, ?bool $positive): Generator
    {
        foreach ($this->dataDao->search($startDate, $endDate, $positive) as $row) {
            $row['time'] = new DateTimeImmutable($row['time'], new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
            $row['value'] = (float) $row['value'];
            yield $this->entityBuilder->buildRecord($row);
        }
    }

    public function searchForAggregation(array $conditions): Generator
    {
        foreach ($this->dataDao->searchRaw($conditions) as $row) {
            yield $row;
        }
    }

    /**
     * @throws MySqlQueryException
     */
    public function firstDate(): ?string
    {
        return $this->dataDao->firstDate();
    }

}