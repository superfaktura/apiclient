<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Export;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\BinaryResponse;
use SuperFaktura\ApiClient\UseCase\Export\PdfExportOptions;
use SuperFaktura\ApiClient\Contract\Invoice\CannotExportInvoicesException;

interface Exports
{
    /**
     * @throws ExportNotFoundException
     * @throws CannotGetExportStatusException
     */
    public function getStatus(int $id): Response;

    /**
     * @throws ExportNotFoundException
     * @throws CannotDownloadExportException
     */
    public function download(int $id): BinaryResponse;

    /**
     * @param int[] $ids
     *
     * @throws CannotExportInvoicesException
     */
    public function exportInvoices(
        array $ids,
        Format $format,
        PdfExportOptions $pdf_options = new PdfExportOptions(),
    ): Response;
}
