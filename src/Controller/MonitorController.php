<?php
declare(strict_types=1);

namespace Bilo\Controller;

use Bilo\Database\Connection;
use Bilo\Enum\Queue;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MonitorController
{
    public function __construct(
        private readonly Connection $connection,
    )
    {
    }


    public function index(Request $request, Response $response): Response
    {
        // PID first, then the command
        $cmd = "ps -eo pid,args | grep php | grep cli.php | grep consume | grep -v grep";
        exec($cmd, $output);

        $query = "SELECT queue_id, COUNT(1) FROM queues WHERE deleted=0 GROUP BY queue_id";

        $groups = [];

        foreach ($output as $line) {

            // Extract PID (first column)
            if (!preg_match('/^\s*(\d+)\s+(.*)$/', $line, $m)) {
                continue;
            }

            $pid = $m[1];
            $cmdline = $m[2];

            // Extract NAME (word after "consume")
            if (!preg_match('/consume\s+([^\s]+)/', $cmdline, $matches)) {
                continue;
            }

            $name = $matches[1];

            if (!isset($groups[$name])) {
                $groups[$name] = [];
            }

            // Prepend PID
            $groups[$name][] = $pid." ".$cmdline;
        }

        ob_start();
        require_once __DIR__.'/../../public/processes.php';
        $content = ob_get_clean();

        $response->getBody()->write($content);
        return $response;
    }

    public function add(Request $request, Response $response, array $args): Response
    {
        $queue = $args['queue'];
        $name = Queue::{$queue}->name;

        $cliPath = realpath(__DIR__.'/../../bin/cli.php');
        $phpBin = 'php';

        $cmd = sprintf(
            'nohup %s %s consume '.$name.' > /dev/null 2>&1 &',
            escapeshellcmd($phpBin),
            escapeshellarg($cliPath)
        );

        exec($cmd);

        return $response
            ->withHeader('Location', '/index.php/monitor')
            ->withStatus(StatusCodeInterface::STATUS_TEMPORARY_REDIRECT);
    }

    public function stop(Request $request, Response $response, array $args): Response
    {
        $pid = $args['pid'];
        $pid = (int) $pid;

        $cmd = "ps -eo pid,args | grep php | grep cli.php | grep consume | grep -v grep";
        exec($cmd, $output);

        $found = false;
        foreach ($output as $line) {
            $line = trim($line);
            if (str_starts_with($line, $pid." ")) {
                $found = true;
            }
        }
        if (!$found) {
            return $response->withStatus(StatusCodeInterface::STATUS_NOT_FOUND);
        }

        $cmd = "kill -9 $pid";
        exec($cmd, $output2, $returnVar);

        if ($returnVar !== 0) {
            $response->getBody()->write("Command failed with exit code $returnVar\n<br>Output/Error:\n".implode("\n111<br>", $output2));
            return $response;
        }

        return $response
            ->withHeader('Location', '/index.php/monitor')
            ->withStatus(StatusCodeInterface::STATUS_TEMPORARY_REDIRECT);

    }
}