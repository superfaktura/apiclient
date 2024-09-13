<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Export;

use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Contract\Language;
use SuperFaktura\ApiClient\Response\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Contract\Export\Format;
use SuperFaktura\ApiClient\UseCase\Export\Exports;
use SuperFaktura\ApiClient\Response\BinaryResponse;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Contract\Export\DocumentSort;
use SuperFaktura\ApiClient\UseCase\Export\PdfExportOptions;
use SuperFaktura\ApiClient\UseCase\Export\InvoiceExportRequestFactory;
use SuperFaktura\ApiClient\Contract\Invoice\CannotExportInvoicesException;

#[CoversClass(Exports::class)]
#[CoversClass(InvoiceExportRequestFactory::class)]
#[CoversClass(PdfExportOptions::class)]
#[UsesClass(Response::class)]
#[UsesClass(BinaryResponse::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(RequestException::class)]
final class ExportInvoicesTest extends ExportTestCase
{
    public static function exportProvider(): \Generator
    {
        yield 'export to single pdf with default options' => [
            'expected' => json_encode([
                InvoiceExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                InvoiceExportRequestFactory::EXPORT => [
                    'is_msel' => true,
                    'invoices_pdf' => true,
                    'merge_pdf' => true,
                    'only_merge' => true,
                ],
            ], JSON_THROW_ON_ERROR),
            'ids' => [1, 2],
            'format' => Format::PDF,
            'options' => new PdfExportOptions(),
        ];

        yield 'export to single pdf in english with custom options' => [
            'expected' => json_encode([
                InvoiceExportRequestFactory::INVOICE => ['ids' => [3, 4]],
                InvoiceExportRequestFactory::EXPORT => [
                    'is_msel' => true,
                    'invoices_pdf' => true,
                    'merge_pdf' => true,
                    'only_merge' => true,
                    'pdf_lang_default' => Language::ENGLISH,
                    'hide_pdf_payment_info' => true,
                    'hide_signature' => true,
                ],
            ], JSON_THROW_ON_ERROR),
            'ids' => [3, 4],
            'format' => Format::PDF,
            'options' => new PdfExportOptions(
                language: Language::ENGLISH,
                hide_payment_info: true,
                hide_signature: true,
            ),
        ];

        yield 'export to zip archive with default options' => [
            'expected' => json_encode([
                InvoiceExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                InvoiceExportRequestFactory::EXPORT => [
                    'is_msel' => true,
                    'invoices_pdf' => true,
                ],
            ], JSON_THROW_ON_ERROR),
            'ids' => [1, 2],
            'format' => Format::ZIP,
            'options' => new PdfExportOptions(),
        ];

        yield 'export to zip archive in english with custom options' => [
            'expected' => json_encode([
                InvoiceExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                InvoiceExportRequestFactory::EXPORT => [
                    'is_msel' => true,
                    'invoices_pdf' => true,
                    'pdf_lang_default' => Language::ENGLISH,
                    'hide_pdf_payment_info' => true,
                    'hide_signature' => true,
                ],
            ], JSON_THROW_ON_ERROR),
            'ids' => [1, 2],
            'format' => Format::ZIP,
            'options' => new PdfExportOptions(
                language: Language::ENGLISH,
                hide_payment_info: true,
                hide_signature: true,
            ),
        ];

        yield 'export to zip archive sorted to folders by client name' => [
            'expected' => json_encode([
                InvoiceExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                InvoiceExportRequestFactory::EXPORT => [
                    'is_msel' => true,
                    'invoices_pdf' => true,
                    'pdf_sort_client' => true,
                ],
            ], JSON_THROW_ON_ERROR),
            'ids' => [1, 2],
            'format' => Format::ZIP,
            'options' => new PdfExportOptions(
                document_sort: DocumentSort::CLIENT,
            ),
        ];

        yield 'export to zip archive sorted to folders by created date' => [
            'expected' => json_encode([
                InvoiceExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                InvoiceExportRequestFactory::EXPORT => [
                    'is_msel' => true,
                    'invoices_pdf' => true,
                    'pdf_sort_date' => true,
                ],
            ], JSON_THROW_ON_ERROR),
            'ids' => [1, 2],
            'format' => Format::ZIP,
            'options' => new PdfExportOptions(
                document_sort: DocumentSort::DATE,
            ),
        ];
    }

    /**
     * @param int[] $ids
     */
    #[DataProvider('exportProvider')]
    public function testExport(
        string $expected,
        array $ids,
        Format $format,
        PdfExportOptions $options,
    ): void {
        $this
            ->getExports($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->exportInvoices($ids, $format, $options);

        $this->request()
            ->post('/exports')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($expected, (string) $this->getLastRequest()?->getBody());
    }

    public function testExportRequestFailed(): void
    {
        $this->expectException(CannotExportInvoicesException::class);

        $this
            ->getExports($this->getHttpClientWithMockRequestException())
            ->exportInvoices([1], Format::PDF);
    }

    public function testExportBadRequest(): void
    {
        $this->expectException(CannotExportInvoicesException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getExports($this->getHttpClientReturning($fixture, StatusCodeInterface::STATUS_BAD_REQUEST))
            ->exportInvoices([1], Format::PDF);
    }
}
