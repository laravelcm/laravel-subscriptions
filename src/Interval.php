<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions;

enum Interval: string
{
    case YEAR = 'year';

    case MONTH = 'month';

    case DAY = 'day';
}
