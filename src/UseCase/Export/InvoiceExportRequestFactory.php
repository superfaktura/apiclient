<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Export;

use SuperFaktura\ApiClient\Contract\Export\Format;
use SuperFaktura\ApiClient\Contract\Export\DocumentSort;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;

final class InvoiceExportRequestFactory
{
    public const INVOICE = 'Invoice';

    public const EXPORT = 'Export';

    /**
     * @param int[] $ids
     *
     * @throws CannotCreateRequestException
     */
    public function createJsonRequest(
        array $ids,
        Format $format,
        PdfExportOptions $pdf_options): string
    {
        $export_options = array_filter([
            'is_msel' => true,
            ...$this->getFormatSpecificOptions($format, $pdf_options),
            'pdf_lang_default' => $pdf_options->language?->value,
            'hide_pdf_payment_info' => $pdf_options->hide_payment_info,
            'hide_signature' => $pdf_options->hide_signature,
        ]);

        try {
            return json_encode(
                [
                    self::INVOICE => ['ids' => $ids],
                    self::EXPORT => $export_options,
                ],
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return array<string, bool>
     */
    private function getFormatSpecificOptions(Format $format, PdfExportOptions $pdf_options): array
    {
        return match ($format) {
            Format::PDF => [
                'invoices_pdf' => true,
                'merge_pdf' => true,
                'only_merge' => true,
            ],
            Format::ZIP => [
                'invoices_pdf' => true,
                ...match ($pdf_options->document_sort) {
                    DocumentSort::CLIENT => ['pdf_sort_client' => true],
                    DocumentSort::DATE => ['pdf_sort_date' => true],
                    default => [],
                },
            ],
        };
    }
}
