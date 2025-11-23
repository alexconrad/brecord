<?php

declare(strict_types=1);

namespace Bilo\Command;

use Bilo\Consumers\Exception\DelayMessageException;
use Bilo\Enum\Queue;
use Bilo\Service\QueueService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueCommand extends Command
{
    public const int BREATHE_MS = 100;

    private bool $shouldStop = false;

    public function __construct(
        private readonly QueueService $queueService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('consume')
            ->setDescription('Process queue items')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Queue name (one of: '.implode(', ', array_map(fn($case) => $case->name, Queue::cases())).')'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = $input->getArgument('name');

        $validQueueNames = array_map(fn($case) => $case->name, Queue::cases());

        if (!in_array($queueName, $validQueueNames, true)) {
            $output->writeln('<error>Invalid queue name: '.$queueName.'</error>');
            $output->writeln('Valid queue names are: '.implode(', ', $validQueueNames));
            return Command::FAILURE;
        }

        $queue = Queue::{$queueName};

        $this->registerSignalHandlers($output);

        while (!$this->shouldStop) {
            if ([$messageId, $messageData] = $this->queueService->getMessage($queue)) {
                $output->writeln('STARTING PROCESSING for queue: '.$queue->name.' message id: '.$messageId);
                $output->writeln(print_r($messageData, true));
                try {
                    $this->queueService->consume($queue, $messageData);
                } catch (DelayMessageException $e) {
                    $output->writeln("[{$queue->name}] DELAY PROCESSING message id: ".$messageId.":".$e->getMessage());;
                    $this->queueService->releaseMessage($messageId, $e->delaySeconds, $e->moveToQueue);
                    $this->breathe();
                    continue;
                } catch (\Throwable $e) {
                    $this->logger->error("[{$queue->name}][".get_class($e).']:'.$e->getMessage(), ['messageId' => $messageId, 'data' => $messageData]);
                    $output->writeln("[{$queue->name}][".get_class($e).']:'.$e->getMessage());
                    $output->writeln("[{$queue->name}] FAILED PROCESSING message id: ".$messageId);
                    $this->queueService->releaseMessage($messageId, 30, null);
                    $this->breathe();
                    continue;
                }
                $output->writeln('DONE PROCESSING for queue: '.$queue->name.' message id: '.$messageId);
                $this->queueService->deleteMessage($messageId);
            } else {
                $output->writeln('NO MESSAGES');
            }
            $this->breathe();
        }

        $output->writeln('<info>Queue processing stopped gracefully.</info>');
        return Command::SUCCESS;
    }

    private function breathe(): void
    {
        usleep(self::BREATHE_MS * 1000);
    }

    private function registerSignalHandlers($output): void
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);

            $signalHandler = function (int $signal) use ($output): void {
                $this->shouldStop = true;
                $output->writeln('');
                $output->writeln('<comment>Received shutdown signal. Finishing current task and exiting gracefully...</comment>');
            };

            pcntl_signal(SIGTERM, $signalHandler);
            pcntl_signal(SIGINT, $signalHandler);

            $output->writeln('<info>Signal handlers registered. Press CTRL+C to stop gracefully.</info>');
        } else {
            $output->writeln('<comment>Warning: pcntl extension not loaded. Graceful shutdown not available.</comment>');
        }
    }

}
