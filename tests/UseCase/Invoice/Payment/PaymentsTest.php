<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Invoice\Payment;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Contract\PaymentType;
use SuperFaktura\ApiClient\UseCase\Money\Currency;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Invoice\Payment\Payment;
use SuperFaktura\ApiClient\UseCase\Invoice\Payment\Payments;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceNotFoundException;
use SuperFaktura\ApiClient\Contract\Invoice\Payment\CannotPayInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\Payment\CannotMarkAsUnpayableException;
use SuperFaktura\ApiClient\Contract\Invoice\Payment\CannotDeleteInvoicePaymentException;

#[CoversClass(Payments::class)]
#[CoversClass(Payment::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
final class PaymentsTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    public static function idProvider(): \Generator
    {
        yield 'id' => [1];
        yield 'another id' => [2];
    }

    #[DataProvider('idProvider')]
    public function testMarkAsUnPayable(int $id): void
    {
        $this
            ->getPayments($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->markAsUnPayable($id);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertGetRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoices/will_not_be_paid/' . $id, $request->getUri()->getPath());
    }

    public function testMarkAsUnPayableNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $fixture = __DIR__ . '/../fixtures/not-found.json';

        $this->getPayments(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->markAsUnPayable(1);
    }

    public function testMarkAsUnPayableWrongInvoice(): void
    {
        $this->expectException(CannotMarkAsUnpayableException::class);

        $fixture = __DIR__ . '/fixtures/mark-as-unpayable-wrong-invoice.json';

        $this->getPayments(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->markAsUnPayable(1);
    }

    public function testMarkAsUnPayableRequestFailed(): void
    {
        $this->expectException(CannotMarkAsUnpayableException::class);

        $this
            ->getPayments($this->getHttpClientWithMockRequestException())
            ->markAsUnPayable(1);
    }

    public static function invoicePayProvider(): \Generator
    {
        yield 'with minimal data' => [
            'request_body' => 'data=' . json_encode([
                Payments::INVOICE_PAYMENT => [
                    'invoice_id' => 1,
                ],
            ], JSON_THROW_ON_ERROR),
            'id' => 1,
        ];

        yield 'with all options' => [
            'request_body' => 'data=' . json_encode([
                Payments::INVOICE_PAYMENT => [
                    'invoice_id' => 1,
                    'amount' => 9.99,
                    'currency' => Currency::CZECH_REPUBLIC_KORUNA->value,
                    'payment_type' => PaymentType::CASH->value,
                    'document_number' => 'ABC123',
                    'cash_register_id' => 2,
                    'date' => (new \DateTimeImmutable('2023-12-24'))->format('Y-m-d'),
                ],
            ], JSON_THROW_ON_ERROR),
            'id' => 1,
            'payment_type' => PaymentType::CASH,
            'amount' => 9.99,
            'currency' => Currency::CZECH_REPUBLIC_KORUNA,
            'document_number' => 'ABC123',
            'cash_register_id' => 2,
            'payment_date' => new \DateTimeImmutable('2023-12-24'),
        ];
    }

    #[DataProvider('invoicePayProvider')]
    public function testPay(
        string $request_body,
        int $id,
        ?PaymentType $payment_type = null,
        ?float $amount = null,
        ?Currency $currency = null,
        ?string $document_number = null,
        ?int $cash_register_id = null,
        ?\DateTimeImmutable $payment_date = null,
    ): void {
        $this
            ->getPayments($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->pay(
                id: $id,
                payment: new Payment(
                    amount: $amount,
                    currency: $currency,
                    payment_type: $payment_type,
                    document_number: $document_number,
                    cash_register_id: $cash_register_id,
                    payment_date: $payment_date,
                ),
            );

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertPostRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoice_payments/add/ajax%3A1/api%3A1', $request->getUri()->getPath());
        self::assertSame($request_body, (string) $request->getBody());
    }

    public function testPayErrorResponse(): void
    {
        $this->expectException(CannotPayInvoiceException::class);
        $this->expectExceptionMessage('Invalid document type');

        $fixture = __DIR__ . '/fixtures/pay-error-response.json';

        $this->getPayments(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->pay(1);
    }

    public function testPayRequestFailed(): void
    {
        $this->expectException(CannotPayInvoiceException::class);

        $this
            ->getPayments($this->getHttpClientWithMockRequestException())
            ->pay(1);
    }

    public function testPayInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this
            ->getPayments($this->getHttpClientWithMockResponse())
            ->pay(
                id: 1,
                payment: new Payment(amount: NAN),
            );
    }

    #[DataProvider('idProvider')]
    public function testDelete(int $id): void
    {
        $this
            ->getPayments($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->delete($id);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertDeleteRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoice_payments/delete/' . $id, $request->getUri()->getPath());
    }

    public function testDeleteFailed(): void
    {
        $this->expectException(CannotDeleteInvoicePaymentException::class);
        $this->expectExceptionMessage('Error deleting payment');

        $fixture = __DIR__ . '/fixtures/delete-payment-error-response.json';

        $this->getPayments(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->delete(1);
    }

    public function testDeleteRequestFailed(): void
    {
        $this->expectException(CannotDeleteInvoicePaymentException::class);

        $this
            ->getPayments($this->getHttpClientWithMockRequestException())
            ->delete(1);
    }

    private function getPayments(Client $client): Payments
    {
        return new Payments(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
