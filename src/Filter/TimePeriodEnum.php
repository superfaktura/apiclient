<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Filter;

enum TimePeriodEnum: int
{
    case ALL = 0;
    case TODAY = 1;
    case YESTERDAY = 2;
    case FROM_TO = 3;
    case THIS_MONTH = 4;
    case LAST_MONTH = 5;
    case THIS_YEAR = 6;
    case LAST_YEAR = 7;
    case THIS_QUARTER = 8;
    case THIS_WEEK = 9;
    case LAST_QUARTER = 10;
    case LAST_HOUR = 11;
    case THIS_HOUR = 12;
}
