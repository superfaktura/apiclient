<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice;

use SuperFaktura\ApiClient\Contract\Language;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\UseCase\Invoice\Email;
use SuperFaktura\ApiClient\Response\BinaryResponse;
use SuperFaktura\ApiClient\UseCase\Invoice\Address;
use SuperFaktura\ApiClient\UseCase\Invoice\InvoicesQuery;
use SuperFaktura\ApiClient\Contract\Invoice\Export\Format;
use SuperFaktura\ApiClient\UseCase\Invoice\PdfExportOptions;
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
     * @throws CannotDownloadInvoiceException
     * @throws InvoiceNotFoundException
     */
    public function downloadPdf(int $id, Language $language): BinaryResponse;

    /**
     * @param int[] $ids
     *
     * @throws CannotExportInvoicesException
     */
    public function export(
        array $ids,
        Format $format,
        PdfExportOptions $pdf_options = new PdfExportOptions(),
    ): BinaryResponse;

    /**
     * @param array<string, mixed> $invoice
     * @param array<array<string, mixed>> $items
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
     * @param array<array<string, mixed>> $items
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

    /**
     * @throws InvoiceNotFoundException
     * @throws CannotMarkInvoiceAsSentException
     */
    public function markAsSent(int $id): void;

    /**
     * @throws InvoiceNotFoundException
     * @throws CannotMarkInvoiceAsSentException
     */
    public function markAsSentViaEmail(
        int $id,
        string $email,
        string $subject = '',
        string $message = '',
    ): void;

    /**
     * @throws CannotSendInvoiceException
     * @throws InvoiceNotFoundException
     */
    public function sendViaEmail(int $id, Email $email): void;

    /**
     * @throws CannotSendInvoiceException
     * @throws InvoiceNotFoundException
     */
    public function sendViaPostOffice(int $id, Address $address = new Address()): void;

    /**
     * @throws CannotCreateInvoiceException
     * @throws CannotCreateRequestException
     */
    public function createRegularFromProforma(int $proforma_id): Response;
}
