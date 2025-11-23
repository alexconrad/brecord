<?php

declare(strict_types=1);

namespace Bilo\Enum;

enum Queue: int
{
    case search_request = 2;
    case notification = 3;
    case notification_http = 7;
    case alert = 4;
    case delayed_alert = 5;
    case delayed_notification = 6;
    case search_aggregate = 8;
}