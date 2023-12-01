<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Invoice;

use GuzzleHttp\Psr7\Response;
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
use SuperFaktura\ApiClient\UseCase\Invoice\Payment\Payments;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceNotFoundException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotDownloadInvoiceException;

#[CoversClass(Invoices::class)]
#[UsesClass(Items::class)]
#[UsesClass(Payments::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(BinaryResponse::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
final class InvoicesDownloadTest extends InvoicesTestCase
{
    public static function downloadPdfProvider(): \Generator
    {
        yield 'download PDF' => [
            'invoice_id' => 1,
            'language' => Language::SLOVAK,
            'fixture' => __DIR__ . '/../../Response/fixtures/foo.pdf',
        ];

        yield 'download another PDF' => [
            'invoice_id' => 2,
            'language' => Language::SLOVAK,
            'fixture' => __DIR__ . '/../../Response/fixtures/bar.pdf',
        ];

        yield 'download english PDF version' => [
            'invoice_id' => 1,
            'language' => Language::ENGLISH,
            'fixture' => __DIR__ . '/../../Response/fixtures/foo.pdf',
        ];
    }

    #[DataProvider('downloadPdfProvider')]
    public function testDownloadPdf(
        int $invoice_id,
        Language $language,
        string $fixture,
    ): void {
        $response = $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                self::getPsrBinaryResponse($fixture, StatusCodeInterface::STATUS_OK),
            ),
        )
            ->downloadPdf($invoice_id, $language);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertGetRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame(
            '/' . $language->value . '/invoices/pdf/' . $invoice_id,
            $request->getUri()->getPath(),
        );
        self::assertStringEqualsFile($fixture, (string) stream_get_contents($response->data));
    }

    public function testDownloadPdfNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND),
            ),
        )
            ->downloadPdf(1, Language::SLOVAK);
    }

    public function testDownloadPdfInternalError(): void
    {
        $this->expectException(CannotDownloadInvoiceException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR),
            ),
        )
            ->downloadPdf(1, Language::SLOVAK);
    }

    public function testDownloadPdfRequestFailed(): void
    {
        $this->expectException(CannotDownloadInvoiceException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockRequestException(),
        )
            ->downloadPdf(1, Language::SLOVAK);
    }
}
