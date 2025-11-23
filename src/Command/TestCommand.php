<?php

declare(strict_types=1);

namespace Bilo\Command;

use Bilo\Service\AggregationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    public function __construct(
        private readonly AggregationService $aggregationService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('test')
            ->setDescription('Process aggregation table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('test');



        $start = '2025-11-21 10:10:04';
        $ended = '2025-11-21 15:10:16';

        $start = '2025-11-30 23:10:01';
        $ended = '2025-12-01 05:10:01';

        foreach($this->aggregationService->hourlySegments($start, $ended) as $qwe) {
            print_r($qwe);
        }
        exit();



        $ranges = $this->aggregationService->getAggregationRanges($start, $ended);
        print_r($ranges);
        echo "\n=====\n";

        /*
         * Example:
         * $start = '2025-11-20 13:40:55';
         * $ended = '2025-11-21 13:41:01';
         *
         *  start 2025-11-20 13:40:55 ==> 2025-11-20 13:59:59
         *  start 2025-11-20 14:00:00
         *  start 2025-11-20 15:00:00
         *  start 2025-11-20 17:00:00
         *  ....
         *  start 2025-11-20 22:00:00
         *  start 2025-11-20 23:00:00
         *  start 2025-11-21 00:00:00
         *  start 2025-11-21 01:00:00
         *  ....
         *  start 2025-11-13 12:00:00
         *  start 2025-11-13 13:00:01 ===> 2025-11-13 13:41:00
         *
         *  from non_aggregated :  2025-11-20 13:40:55 ==> 2025-11-20 14:59:59
         *  from non_aggregated :  2025-11-20 13:40:55 ==> 2025-11-20 14:59:59  (max 2 hour)
         *  from non_aggregated :  2025-11-21 12:00:00 ==> 2025-11-21 12:59:59
         *  from non_aggregated :  2025-11-20 13:00:00 ==> 2025-11-20 13:41:00  (max 2 hour)
         *  from aggregated : 2025-11-20 15 ==> 2025-11-20 11
         *
         */

        $test = '2012-12-12 03:59:00';
        echo "TEST: $test\n";
        $qwe = $this->aggregationService->aggregationUntil($test);
        echo "TEST UNTIL: $test\n";
        print_r($qwe);
        echo "\n=====\n";
        $qwe = $this->aggregationService->aggregationAfter($test);
        echo "TEST AFTER: $test\n";
        print_r($qwe);

        echo "\n=====\n";
        $start = '2012-12-12 01:30:00';
        $end = '2012-12-12 05:31:00';
        $qwe = $this->aggregationService->getAggregationRanges($start, $end);
        echo "TEST BETWEEN: $test\n";
        print_r($qwe);

        //echo "[START DATE] AGGREGATION ========> $qwe    +     DATA [$qwe , $test] \n"; // de fapt end date only filter

        //$qwe = $this->aggregationService->aggregationAfter('2012-12-12 04:49:10');
        //echo "[  END DATE]  DATA [$test  $qwe)     +      [$qwe ===> AGRREGATION  \n";

        echo "\n";


        return Command::SUCCESS;

    }


}
