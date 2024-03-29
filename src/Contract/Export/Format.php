<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Export;

enum Format: string
{
    /** Documents merged into one PDF file */
    case PDF = 'pdf';

    /** Documents in zip archive */
    case ZIP = 'zip';
}
