<?php

declare(strict_types=1);

namespace Bilo\Service;

use Bilo\DAO\DataAggregateDao;
use Bilo\DAO\ToAggregateDao;
use Bilo\Entity\Conditions;
use Bilo\Entity\SearchInterval;
use Bilo\Enum\Sign;
use Bilo\Service\Exception\ValidationException;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use Generator;


class AggregationService
{
    public const int EDGE_HOURS = 1;

    public function __construct(
        private readonly ToAggregateDao $toAggregateDao,
        private readonly DataAggregateDao $dataAggregateDao,
    ) {
    }

    public function aggregate(): void
    {
        $this->toAggregateDao->aggregate();
    }

    public function searchDestinationReference(int $idDestination, ?int $idReference, array $searchIntervals): Generator
    {
        if ($idReference === null) {
            return $this->dataAggregateDao->searchDestination($idDestination, $searchIntervals);
        }

        return $this->dataAggregateDao->searchDestinationReference($idDestination, $idReference, $searchIntervals);
    }

    public function searchRaw(array $searchIntervals): Generator
    {
        return $this->dataAggregateDao->searchRaw($searchIntervals);
    }

    public function aggregationAfter(string $startDate): Conditions
    {
        $start = new DateTimeImmutable($startDate);
        $startOfHour = $start->setTime((int) $start->format('H'), 0, 0);
        $milestone = $startOfHour->modify('+'.(self::EDGE_HOURS + 1).' hours');;

        return new Conditions(
            [
                new SearchInterval($start->format('Y-m-d H:i:s'), $milestone->modify('-1 second')->format('Y-m-d H:i:s'))
            ],
            [
                new SearchInterval(Sign::GREATER_OR_EQUAL_THAN, $milestone->format('Y-m-d H:i:s'))
            ],
        );
    }

    public function aggregationUntil(string $endDate): Conditions
    {
        $end = new DateTimeImmutable($endDate);
        $startOfHour = $end->setTime((int) $end->format('H'), 0, 0);

        $milestone = $startOfHour->modify('-'.self::EDGE_HOURS.' hours')->format('Y-m-d H:i:s');

        return new Conditions(
            [
                new SearchInterval($milestone, $end->format('Y-m-d H:i:s'))
            ],
            [
                new SearchInterval(Sign::LESS_THAN, $milestone)
            ],
        );
    }

    /**
     * @throws ValidationException
     */
    public function getAggregationRanges(string $startDate, string $endDate): Conditions
    {
        $ranges = $this->splitIntoHourlyRanges($startDate, $endDate);
        $edges = $this->pickEdges($ranges);

        $minNonEdge = null;
        $maxNonEdge = null;
        $inner = [];

        foreach ($ranges as $item) {

            if (in_array($item, $edges, true)) {
                $inner[] = $item;
                continue;
            }

            if ($minNonEdge === null || $maxNonEdge === null) {
                $minNonEdge = $item['start'];
                $maxNonEdge = $item['start'];
            }

            if ($item['start'] > $maxNonEdge) {
                $maxNonEdge = $item['start'];
            }
        }

        $directConditions = [];
        foreach ($this->mergeIntervals($inner) as $interval) {
            $directConditions[] = new SearchInterval($interval['start'], $interval['end']);
        }

        $aggregateCondition = $minNonEdge === null ? null : new SearchInterval($minNonEdge, $maxNonEdge);

        return new Conditions(
            $directConditions,
            [$aggregateCondition]
        );
    }

    public function hourlySegments(DateTimeImmutable $start, DateTimeImmutable $end): Generator
    {
        if ($start > $end) {
            return; // No segments
        }

        $currentStart = $start;

        while ($currentStart < $end) {
            $currentEnd = $currentStart->modify('+1 hour');

            // Make sure we don't go past the global end
            if ($currentEnd >= $end) {
                $currentEnd = $end->modify('+1 second');
            }

            yield [
                $currentStart->format('Y-m-d H:i:s'),
                $currentEnd->modify('-1 second')->format('Y-m-d H:i:s'),
            ];

            $currentStart = $currentEnd;
        }
    }


    /**
     * @throws ValidationException
     */
    private function splitIntoHourlyRanges(string $startDate, string $endDate): array
    {
        try {
            $start = new DateTimeImmutable($startDate);
            $end = new DateTimeImmutable($endDate);
        } catch (DateMalformedStringException) {
            throw new ValidationException('Invalid dates');
        }

        if ($end < $start) {
            throw new ValidationException('End date must be >= start date.');
        }

        $ranges = [];

        // 1. First partial hour
        $firstRangeEnd = $start->setTime(
            (int) $start->format('H'),
            59,
            59
        );

        if ($firstRangeEnd > $end) {
            // Only one partial range total
            $ranges[] = [
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ];
            return $ranges;
        }

        $ranges[] = [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $firstRangeEnd->format('Y-m-d H:i:s'),
        ];

        // 2. Full hours between
        try {
            $current = $firstRangeEnd->modify('+1 second');
            while ($current->modify('+1 hour') <= $end) {
                $ranges[] = [
                    'start' => $current->format('Y-m-d H:i:s'),
                    'end' => $current->setTime(
                        (int) $current->format('H'),
                        59,
                        59
                    )->format('Y-m-d H:i:s'),
                ];

                // move to the next full hour
                $current = $current->modify('+1 hour');
            }
        } catch (DateMalformedStringException $e) {
            throw new ValidationException('Could not modify date');
        } // start of next hour

        // 3. Last partial hour
        if ($current <= $end) {
            $ranges[] = [
                'start' => $current->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ];
        }

        return $ranges;
    }

    private function pickEdges(array $ranges): array
    {
        $count = count($ranges);

        if ($count <= 2) {
            return $ranges; // nothing to dedupe, return as is
        }

        // First two
        $result = array_slice($ranges, 0, 2);

        // Last two
        $tail = array_slice($ranges, -2);

        // Merge without duplicates (strict comparison)
        foreach ($tail as $item) {
            if (!in_array($item, $result, true)) {
                $result[] = $item;
            }
        }

        return $result;
    }


    private function mergeIntervals(array $intervals): array
    {
        if (empty($intervals)) {
            return [];
        }

        // Sort by start time
        usort($intervals, fn($a, $b) => strcmp($a['start'], $b['start']));

        $merged = [];
        $current = [
            'start' => new DateTime($intervals[0]['start']),
            'end' => new DateTime($intervals[0]['end']),
        ];

        foreach ($intervals as $interval) {

            $start = new DateTime($interval['start']);
            $end = new DateTime($interval['end']);

            // Compute (current.end + 1 second)
            $currentEndPlusOne = clone $current['end'];
            $currentEndPlusOne->modify('+1 second');

            // Overlap OR exactly touching (1 sec apart)
            if ($start <= $currentEndPlusOne) {

                // Extend end if needed
                if ($end > $current['end']) {
                    $current['end'] = $end;
                }

            } else {
                // No overlap
                $merged[] = [
                    'start' => $current['start']->format('Y-m-d H:i:s'),
                    'end' => $current['end']->format('Y-m-d H:i:s'),
                ];
                $current = ['start' => $start, 'end' => $end];
            }
        }

        // Push final interval
        $merged[] = [
            'start' => $current['start']->format('Y-m-d H:i:s'),
            'end' => $current['end']->format('Y-m-d H:i:s'),
        ];

        return $merged;
    }


}