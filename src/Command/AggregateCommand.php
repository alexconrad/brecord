<?php

declare(strict_types=1);

namespace Bilo\Command;

use Bilo\Service\AggregationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\LockableTrait;

class AggregateCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private readonly AggregationService $aggregationService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('aggregate')
            ->setDescription('Process aggregation table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('<comment>The command is already running in another process.</comment>');
            return Command::SUCCESS;
        }

        try {
            $output->writeln('<info>Starting aggregation process...</info>');
            $this->aggregationService->aggregate();
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Error during aggregation process: '.get_class($e).':'.$e->getMessage().'</error>');
            $this->logger->error(get_class($e).':'.$e->getMessage());
            return Command::FAILURE;
        } finally {
            $this->release();
        }

    }


}
