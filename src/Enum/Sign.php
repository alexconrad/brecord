<?php
declare(strict_types=1);

namespace Bilo\Enum;

enum Sign: string
{
    case GREATER_OR_EQUAL_THAN = '>=';
    case LESS_THAN = '<';
}