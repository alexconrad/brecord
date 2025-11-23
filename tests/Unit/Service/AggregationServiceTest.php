<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Bilo\DAO\ToAggregateDao;
use Bilo\Service\AggregationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AggregationServiceTest extends TestCase
{
    private AggregationService $aggregationService;

    protected function setUp(): void
    {
        $toAggregateDaoMock = $this->createMock(ToAggregateDao::class);
        $this->aggregationService = new AggregationService($toAggregateDaoMock);
    }

    #[DataProvider('aggregationUntilDataProvider')]
    public function testAggregationUntil($endDate, $expecting): void
    {
        // Test with a specific date: 2025-11-21 14:30:00
        $result = $this->aggregationService->aggregationUntil($endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('aggregate', $result);
        $this->assertArrayHasKey('direct', $result);

        $this->assertEquals($expecting, $result);
    }

    public static function aggregationUntilDataProvider(): array
    {
        return [
            'middle of the hour' =>[
                '2025-11-21 14:30:00',
                [
                    'aggregate' => "dated < '2025-11-21 13:00:00'",
                    'direct' => "dated BETWEEN '2025-11-21 13:00:00' AND '2025-11-21 14:30:00'",
                ],
            ],
            'start of the hour' => [
                '2025-11-21 14:00:00',
                [
                    'aggregate' => "dated < '2025-11-21 13:00:00'",
                    'direct' => "dated BETWEEN '2025-11-21 13:00:00' AND '2025-11-21 14:00:00'",
                ],
            ],
            'leap year' => [
                '2024-03-01 00:00:00',
                [
                    'aggregate' => "dated < '2024-02-29 23:00:00'",
                    'direct' => "dated BETWEEN '2024-02-29 23:00:00' AND '2024-03-01 00:00:00'",
                ],
            ],
        ];
    }

}
