<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice\Item;

interface Items
{
    /**
     * @param int[] $item_ids
     *
     * @throws CannotDeleteInvoiceItemException
     */
    public function delete(int $invoice_id, array $item_ids): void;
}
