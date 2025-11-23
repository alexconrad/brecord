<?php
declare(strict_types=1);

namespace Bilo\Entity;

class Conditions
{
    /**
     * @param  SearchInterval[]|non-empty-array  $direct
     * @param  SearchInterval[]|null  $aggregate
     */
    public function __construct(
        public readonly array $direct,
        public readonly ?array $aggregate,
    ) {

    }

}