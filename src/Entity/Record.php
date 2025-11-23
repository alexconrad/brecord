<?php

declare(strict_types=1);

namespace Bilo\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;

class Record implements JsonSerializable
{
    public function __construct(
        public readonly string $recordId,
        public readonly DateTimeImmutable $time,
        public readonly string $sourceId,
        public readonly string $destinationId,
        public readonly bool $positive,
        public readonly float $value,
        public readonly string $unit,
        public readonly string $reference,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'recordId' => $this->recordId,
            'time' => $this->time->format(DateTimeInterface::ATOM),
            'sourceId' => $this->sourceId,
            'destinationId' => $this->destinationId,
            'type' => (($this->positive) ? 'positive' : 'negative'),
            'value' => $this->value,
            'unit' => $this->unit,
            'reference' => $this->reference,
        ];
    }

}