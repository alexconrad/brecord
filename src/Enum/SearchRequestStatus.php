<?php

declare(strict_types=1);

namespace Bilo\Enum;

enum SearchRequestStatus: int
{
    case QUEUED = 1;
    case IN_PROGRESS = 2;
    case DONE = 3;
    case FAILED = 4;
    case EMPTY = 5;

}
