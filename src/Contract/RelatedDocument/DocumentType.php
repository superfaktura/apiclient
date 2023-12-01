<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\RelatedDocument;

enum DocumentType: string
{
    case INVOICE = 'invoice';

    case EXPENSE = 'expense';
}
