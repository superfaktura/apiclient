<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\UseCase\Invoice\InvoicesQuery;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;

interface Invoices
{
    /**
     * @throws CannotGetInvoiceException
     * @throws InvoiceNotFoundException
     */
    public function getById(int $id): Response;

    /**
     * @param int[] $ids
     *
     * @throws CannotGetInvoiceException
     */
    public function getByIds(array $ids): Response;

    /**
     * @throws CannotGetAllInvoicesException
     */
    public function getAll(InvoicesQuery $query = new InvoicesQuery()): Response;

    /**
     * @param array<string, mixed> $invoice
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $client
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $extra
     * @param array<string, mixed> $my_data
     * @param int[] $tags
     *
     * @throws CannotCreateInvoiceException
     * @throws CannotCreateRequestException
     */
    public function create(
        array $invoice,
        array $items,
        array $client,
        array $settings = [],
        array $extra = [],
        array $my_data = [],
        array $tags = [],
    ): Response;

    /**
     * @param array<string, mixed> $invoice
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $client
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $extra
     * @param array<string, mixed> $my_data
     * @param int[] $tags
     *
     * @throws CannotUpdateInvoiceException
     * @throws CannotCreateRequestException
     * @throws InvoiceNotFoundException
     */
    public function update(
        int $id,
        array $invoice = [],
        array $items = [],
        array $client = [],
        array $settings = [],
        array $extra = [],
        array $my_data = [],
        array $tags = [],
    ): Response;

    /**
     * @throws CannotDeleteInvoiceException
     * @throws InvoiceNotFoundException
     */
    public function delete(int $id): void;

    /**
     * @throws CannotChangeInvoiceLanguageException
     * @throws InvoiceNotFoundException
     */
    public function changeLanguage(int $id, Language $language): void;
}
