<?php

declare(strict_types=1);

namespace Bilo\Consumers;

use JsonSerializable;

interface ConsumerInterface
{
    /**
     * @param  string  $message
     * @return void
     * @throws Exception\DelayMessageException
     */
    public function consume(string $message): void;
}