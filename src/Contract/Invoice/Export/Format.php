<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice\Export;

enum Format: string
{
    /** Documents merged into one PDF file */
    case PDF = 'pdf';

    /** Documents in zip archive */
    case ZIP = 'zip';

    /** Documents in xlsx */
    case XLSX = 'xlsx';
}
