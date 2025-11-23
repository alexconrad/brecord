<?php
declare(strict_types=1);

namespace Bilo\Entity;

use Bilo\Enum\Sign;

class SearchInterval
{
    public function __construct(
        public readonly Sign|string $startDate,
        public readonly string $endDate,
    )
    {
    }

}