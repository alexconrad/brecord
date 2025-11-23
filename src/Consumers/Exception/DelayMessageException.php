<?php
declare(strict_types=1);

namespace Bilo\Consumers\Exception;

use Bilo\Enum\Queue;
use Exception;

class DelayMessageException extends Exception
{
    public function __construct(
        public readonly int $delaySeconds,
        public readonly ?Queue $moveToQueue = null,
        string $message = ''
    ) {
        parent::__construct($message);
    }
}
