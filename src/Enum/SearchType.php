<?php
declare(strict_types=1);

namespace Bilo\Enum;

enum SearchType: int
{
    case LIST = 1;
    case AGGREGATE = 2;

}