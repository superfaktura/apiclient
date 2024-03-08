<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Export;

enum Status: int
{
    case ERROR = 0;
    case COMPLETED = 1;
    case IN_PROGRESS = 2;
    case SCHEDULED = 3;
}
