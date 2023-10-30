<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Invoice;

use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Contract\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\UseCase\Invoice\Items;
use SuperFaktura\ApiClient\Response\BinaryResponse;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Invoice\Invoices;
use SuperFaktura\ApiClient\Contract\Invoice\Export\Format;
use SuperFaktura\ApiClient\UseCase\Invoice\Payment\Payments;
use SuperFaktura\ApiClient\UseCase\Invoice\PdfExportOptions;
use SuperFaktura\ApiClient\Contract\Invoice\Export\DocumentSort;
use SuperFaktura\ApiClient\UseCase\Invoice\ExportRequestFactory;
use SuperFaktura\ApiClient\Contract\Invoice\CannotExportInvoicesException;

#[CoversClass(Invoices::class)]
#[CoversClass(ExportRequestFactory::class)]
#[CoversClass(PdfExportOptions::class)]
#[UsesClass(Items::class)]
#[UsesClass(Payments::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(BinaryResponse::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
final class InvoicesExportTest extends InvoicesTestCase
{
    public static function exportProvider(): \Generator
    {
        yield 'export to single pdf with default options' => [
            'expected' => 'data=' . json_encode([
                ExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                ExportRequestFactory::EXPORT => [
                    'is_msel' => true,
                    'invoices_pdf' => true,
                    'merge_pdf' => true,
                    'only_merge' => true,
                ],
            ], JSON_THROW_ON_ERROR),
            'ids' => [1, 2],
            'format' => Format::PDF,
            'options' => new PdfExportOptions(),
            'fixture' => __DIR__ . '/../../Response/fixtures/foo.pdf',
        ];

        yield 'export to single pdf in english with custom options' => [
            'expected' => 'data=' . json_encode([
                ExportRequestFactory::INVOICE => ['ids' => [3, 4]],
                ExportRequestFactory::EXPORT => [
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
            'fixture' => __DIR__ . '/../../Response/fixtures/bar.pdf',
        ];

        yield 'export to zip archive with default options' => [
            'expected' => 'data=' . json_encode([
                ExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                ExportRequestFactory::EXPORT => [
                    'is_msel' => true,
                    'invoices_pdf' => true,
                ],
            ], JSON_THROW_ON_ERROR),
            'ids' => [1, 2],
            'format' => Format::ZIP,
            'options' => new PdfExportOptions(),
            'fixture' => __DIR__ . '/fixtures/export.zip',
        ];

        yield 'export to zip archive in english with custom options' => [
            'expected' => 'data=' . json_encode([
                ExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                ExportRequestFactory::EXPORT => [
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
            'fixture' => __DIR__ . '/fixtures/export.zip',
        ];

        yield 'export to zip archive sorted to folders by client name' => [
            'expected' => 'data=' . json_encode([
                ExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                ExportRequestFactory::EXPORT => [
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
            'fixture' => __DIR__ . '/fixtures/export.zip',
        ];

        yield 'export to zip archive sorted to folders by created date' => [
            'expected' => 'data=' . json_encode([
                ExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                ExportRequestFactory::EXPORT => [
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
            'fixture' => __DIR__ . '/fixtures/export.zip',
        ];

        yield 'export to xlsx' => [
            'expected' => 'data=' . json_encode([
                ExportRequestFactory::INVOICE => ['ids' => [1, 2]],
                ExportRequestFactory::EXPORT => [
                    'is_msel' => true,
                    'invoices_xls' => true,
                ],
            ], JSON_THROW_ON_ERROR),
            'ids' => [1, 2],
            'format' => Format::XLSX,
            'options' => new PdfExportOptions(),
            'fixture' => __DIR__ . '/fixtures/export.xlsx',
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
        string $fixture,
    ): void {
        $response = $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                self::getPsrBinaryResponse($fixture, StatusCodeInterface::STATUS_OK),
            ),
        )
            ->export($ids, $format, $options);

        $request = $this->getLastRequest();

        $this->request()
            ->post('/exports')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame($expected, (string) $request?->getBody());
        self::assertStringEqualsFile($fixture, (string) stream_get_contents($response->data));
    }

    public function testExportRequestFailed(): void
    {
        $this->expectException(CannotExportInvoicesException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockRequestException())
            ->export([1], Format::PDF);
    }

    public function testExportBadRequest(): void
    {
        $this->expectException(CannotExportInvoicesException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getInvoices($this->getHttpClientReturning($fixture, StatusCodeInterface::STATUS_BAD_REQUEST))
            ->export([1], Format::PDF);
    }
}
