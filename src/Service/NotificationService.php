<?php

declare(strict_types=1);

namespace Bilo\Service;

use Bilo\Entity\Record;
use Bilo\Enum\IdTable;

class NotificationService
{
    public function __construct(
        private readonly TableIdService $tableIdService,
        private readonly AggregationService $aggregationService,
        private readonly RecordService $recordService,
    )
    {
    }


    public function getNotification(Record $recordEntity): array
    {
        $previousDestinationReference = [
            'count_positive' => 0,
            'count_negative' => 0,
            'sum_positive' => 0,
            'sum_negative' => 0,
        ];

        $idDestination = $this->tableIdService->getId(IdTable::destinations, $recordEntity->destinationId);
        $idReference = $this->tableIdService->getId(IdTable::refs, $recordEntity->reference);

        $conditions = $this->aggregationService->aggregationUntil($recordEntity->time->modify('-1 second')->format('Y-m-d H:i:s'));

        $recordType = $recordEntity->positive ? 'positive' : 'negative';

        //edge
        foreach ($this->recordService->searchDestinationReference($idDestination, $idReference, $conditions->direct) as $record)
        {
            $dbRecordType = $record['record_type'] ? 'positive' : 'negative';
            $previousDestinationReference['count_'.$dbRecordType]++;
            $previousDestinationReference['sum_'.$dbRecordType] = bcadd(
                (string)$previousDestinationReference['sum_'.$dbRecordType],
                (string)$record['val'],
                ConfigService::DECIMAL_PLACES
            );
        }

        //aggregate
        foreach ($this->aggregationService->searchDestinationReference($idDestination, $idReference, $conditions->aggregate) as $record)
        {
            foreach(['positive', 'negative'] as $recordType) {
                $previousDestinationReference['count_'.$recordType] += $record['count_'.$recordType];
                $previousDestinationReference['sum_'.$recordType] = bcadd(
                    (string) $previousDestinationReference['sum_'.$recordType],
                    (string) $record['sum_'.$recordType],
                    ConfigService::DECIMAL_PLACES
                );
            }
        }

        return [
            'record' => $recordEntity,
            'previousDestinationReference' => $previousDestinationReference,
        ];


    }

}