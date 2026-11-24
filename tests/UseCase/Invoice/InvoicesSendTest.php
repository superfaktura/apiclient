<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Invoice;

use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Contract\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\UseCase\Invoice\Email;
use SuperFaktura\ApiClient\UseCase\Invoice\Items;
use SuperFaktura\ApiClient\UseCase\Invoice\Address;
use SuperFaktura\ApiClient\UseCase\Webhook\Webhook;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Invoice\Invoices;
use SuperFaktura\ApiClient\UseCase\Invoice\Payment\Payments;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceNotFoundException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotGetInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotSendInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotMarkInvoiceAsSentException;

#[CoversClass(Invoices::class)]
#[CoversClass(Email::class)]
#[UsesClass(Webhook::class)]
#[CoversClass(Address::class)]
#[UsesClass(Items::class)]
#[UsesClass(Payments::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
final class InvoicesSendTest extends InvoicesTestCase
{
    #[DataProvider('invoiceIdProvider')]
    public function testMarkAsSent(int $id): void
    {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->markAsSent($id);

        $this->request()
            ->get('/invoices/mark_sent/' . $id)
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
    }

    public function testMarkAsSentNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpNotFoundResponse()))
            ->markAsSent(1);
    }

    public function testMarkAsSentWrongInvoice(): void
    {
        $this->expectException(CannotMarkInvoiceAsSentException::class);
        $this->expectExceptionMessageIsOrContains('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getInvoices($this->getHttpClientReturning($fixture))
            ->markAsSent(1);
    }

    public function testMarkAsSentRequestFailed(): void
    {
        $this->expectException(CannotMarkInvoiceAsSentException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockRequestException())
            ->markAsSent(1);
    }

    public static function markAsSentViaEmailProvider(): \Generator
    {
        $data = [
            'invoice_id' => 1,
            'email' => 'jane.doe@superfaktura.sk',
            'subject' => '',
            'message' => '',
        ];

        yield 'with minimal data' => [
            'request_body' => json_encode([Invoices::INVOICE_EMAIL => $data]),
            ...$data,
        ];

        $data = [
            'invoice_id' => 2,
            'email' => 'joe.doe@superfaktura.sk',
            'subject' => 'Foo bar',
            'message' => 'Lorem ipsum',
        ];

        yield 'with subject and body' => [
            'request_body' => json_encode([Invoices::INVOICE_EMAIL => $data]),
            ...$data,
        ];
    }

    #[DataProvider('markAsSentViaEmailProvider')]
    public function testMarkAsSentViaEmail(
        string $request_body,
        int $invoice_id,
        string $email,
        string $subject,
        string $message,
    ): void {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->markAsSentViaEmail($invoice_id, $email, $subject, $message);

        $request = $this->getLastRequest();

        $this->request()
            ->post('/invoices/mark_as_sent')
            ->withHeader('Content-Type', 'application/json')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
        self::assertSame($request_body, (string) $request?->getBody());
    }

    public function testMarkAsSentViaEmailInvoiceNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpNotFoundResponse()))
            ->markAsSentViaEmail(1, 'joe.doe@superfaktura.sk');
    }

    public function testMarkAsSentViaEmailBadRequest(): void
    {
        $this->expectException(CannotMarkInvoiceAsSentException::class);
        $this->expectExceptionMessageIsOrContains('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getInvoices($this->getHttpClientReturning($fixture))
            ->markAsSentViaEmail(1, 'joe.doe@superfaktura.sk');
    }

    public function testMarkAsSentViaEmailRequestFailed(): void
    {
        $this->expectException(CannotMarkInvoiceAsSentException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockRequestException())
            ->markAsSentViaEmail(1, 'joe.doe@superfaktura.sk');
    }

    public function testMarkAsSentViaEmailInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse())
            ->markAsSentViaEmail(1, 'joe.doe@superfaktura.sk', "\xB1\x31");
    }

    public static function sendViaEmailProvider(): \Generator
    {
        $data = [
            'invoice_id' => 1,
            'to' => 'joe.doe@superfaktura.sk',
            'pdf_language' => Language::SLOVAK,
            'bcc' => [],
            'cc' => [],
            'subject' => null,
            'message' => null,
        ];

        yield 'with minimal data' => [
            'request_body' => json_encode([
                Invoices::SEND_EMAIL => array_filter($data, static fn (mixed $value): bool => (bool) $value),
            ]),
            ...$data,
        ];
        $request = [
            'invoice_id' => 2,
            'to' => 'jane.doe@superfaktura.sk',
            'pdf_language' => Language::ENGLISH,
            'bcc' => ['joe.doe@superfaktura.sk', 'joe@doe.sk'],
            'cc' => ['foo@superfaktura.sk', 'foo@bar.sk'],
            'subject' => 'Foo bar',
            'body' => 'Lorem ipsum dolor sit amet',
        ];
        $data = [
            'invoice_id' => 2,
            'to' => 'jane.doe@superfaktura.sk',
            'pdf_language' => Language::ENGLISH,
            'bcc' => ['joe.doe@superfaktura.sk', 'joe@doe.sk'],
            'cc' => ['foo@superfaktura.sk', 'foo@bar.sk'],
            'subject' => 'Foo bar',
            'message' => $request['body'],
        ];

        yield 'with all options' => [
            'request_body' => json_encode([
                Invoices::SEND_EMAIL => array_filter($request, static fn (mixed $value): bool => (bool) $value),
            ]),
            ...$data,
        ];
    }

    /**
     * @param string[] $bcc
     * @param string[] $cc
     */
    #[DataProvider('sendViaEmailProvider')]
    public function testSendViaEmail(
        string $request_body,
        int $invoice_id,
        string $to,
        Language $pdf_language,
        array $bcc,
        array $cc,
        ?string $subject,
        ?string $message,
    ): void {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->sendViaEmail(
                id: $invoice_id,
                email: new Email(
                    email: $to,
                    pdf_language: $pdf_language,
                    bcc: $bcc,
                    cc: $cc,
                    subject: $subject,
                    message: $message,
                ),
            );

        $request = $this->getLastRequest();

        $this->request()
            ->post('/invoices/send')
            ->withHeader('Content-Type', 'application/json')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
        self::assertSame($request_body, (string) $request?->getBody());
    }

    public function testSendViaEmailInvoiceNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpNotFoundResponse()))
            ->sendViaEmail(
                1,
                new Email(email: 'joe.doe@superfaktura.sk', pdf_language: Language::SLOVAK),
            );
    }

    public function testSendViaEmailBadRequest(): void
    {
        $this->expectException(CannotSendInvoiceException::class);
        $this->expectExceptionMessageIsOrContains('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getInvoices($this->getHttpClientReturning($fixture))
            ->sendViaEmail(
                1,
                new Email(email: 'joe.doe@superfaktura.sk', pdf_language: Language::SLOVAK),
            );
    }

    public function testSendViaEmailRequestFailed(): void
    {
        $this->expectException(CannotSendInvoiceException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockRequestException())
            ->sendViaEmail(
                1,
                new Email(email: 'joe.doe@superfaktura.sk', pdf_language: Language::SLOVAK),
            );
    }

    public function testSendViaEmailInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse())
            ->sendViaEmail(
                1,
                new Email(email: "\xB1\x31", pdf_language: Language::SLOVAK),
            );
    }

    public function testSendViaPeppol(): void
    {
        $data = [
            'error' => 0,
            'data' => ['invoice_id' => 1, 'operation_id' => 'operation-1', 'status' => 'queued'],
        ];
        $response = $this
            ->getInvoices($this->getHttpClientWithMockResponse(
                new \GuzzleHttp\Psr7\Response(202, [], json_encode($data, JSON_THROW_ON_ERROR)),
            ))
            ->sendViaPeppol(1);

        $this->request()
            ->post('/invoices/sendPeppol')
            ->withHeader('Content-Type', 'application/json')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame('{"invoice_id":1}', (string) $this->getLastRequest()?->getBody());
        self::assertSame(202, $response->status_code);
        self::assertSame($data, $response->data);
    }

    public function testSendViaPeppolWithWebhook(): void
    {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse(
                new \GuzzleHttp\Psr7\Response(202, [], '{"error":0,"data":{"operation_id":"operation-1"}}'),
            ))
            ->sendViaPeppol(1, new Webhook('https://example.com/webhook', '0'));

        self::assertSame([
            'invoice_id' => 1,
            'webhook_url' => 'https://example.com/webhook',
            'webhook_secret' => '0',
        ], json_decode((string) $this->getLastRequest()?->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testSendViaPeppolInvoiceNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpNotFoundResponse()))
            ->sendViaPeppol(1);
    }

    public function testSendViaPeppolBadRequest(): void
    {
        $this->expectException(CannotSendInvoiceException::class);
        $this->expectExceptionMessageIsOrContains('Unexpected error');

        $this
            ->getInvoices($this->getHttpClientReturning(__DIR__ . '/../fixtures/unexpected-error.json', 422))
            ->sendViaPeppol(1);
    }

    public function testSendViaPeppolUnexpectedHttpStatus(): void
    {
        $this->expectException(CannotSendInvoiceException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse(
                new \GuzzleHttp\Psr7\Response(403, [], '{}'),
            ))
            ->sendViaPeppol(1);
    }

    public function testSendViaPeppolRequestFailed(): void
    {
        $this->expectException(CannotSendInvoiceException::class);

        $this->getInvoices($this->getHttpClientWithMockRequestException())->sendViaPeppol(1);
    }

    public function testSendViaPeppolInvalidResponse(): void
    {
        $this->expectException(CannotSendInvoiceException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse(
                new \GuzzleHttp\Psr7\Response(202, [], 'invalid json'),
            ))
            ->sendViaPeppol(1);
    }

    public function testSendViaPeppolInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse())
            ->sendViaPeppol(1, new Webhook('https://example.com/webhook', "\xB1\x31"));
    }

    public static function peppolResponseDataProvider(): \Generator
    {
        yield 'missing data' => ['{"error":0}'];
        yield 'invalid data' => ['{"error":0,"data":"invalid"}'];
        yield 'missing ID' => ['{"error":0,"data":{}}'];
        yield 'numeric ID' => ['{"error":0,"data":{"operation_id":123}}'];
        yield 'empty ID' => ['{"error":0,"data":{"operation_id":""}}'];
        yield 'blank ID' => ['{"error":0,"data":{"operation_id":" "}}'];
    }

    #[DataProvider('peppolResponseDataProvider')]
    public function testSendViaPeppolReturnsResponseData(string $body): void
    {
        $response = $this
            ->getInvoices($this->getHttpClientWithMockResponse(
                new \GuzzleHttp\Psr7\Response(202, [], $body),
            ))
            ->sendViaPeppol(1);

        self::assertSame(202, $response->status_code);
        self::assertSame(json_decode($body, true, 512, JSON_THROW_ON_ERROR), $response->data);
    }

    public static function peppolSendingStatusProvider(): \Generator
    {
        foreach (['queued', 'processing', 'pending', 'accepted', 'failed'] as $status) {
            yield $status => [$status];
        }
    }

    #[DataProvider('peppolSendingStatusProvider')]
    public function testGetPeppolSendingStatus(string $status): void
    {
        $data = [
            'error' => 0,
            'data' => [
                'invoice_id' => 372,
                'operation_id' => 'operation-1',
                'status' => $status,
                'provider_status' => null,
                'failure_code' => $status === 'failed' ? 'delivery_failed' : null,
                'completed_at' => null,
            ],
        ];
        $response = $this
            ->getInvoices($this->getHttpClientWithMockResponse(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode($data, JSON_THROW_ON_ERROR)),
            ))
            ->getPeppolSendingStatus(372);

        $this->request()
            ->get('/invoices/getPeppolStatus/372')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame(200, $response->status_code);
        self::assertSame($data, $response->data);
    }

    public static function peppolSendingStatusErrorProvider(): \Generator
    {
        foreach ([400, 401, 403, 404, 429, 500] as $status) {
            yield (string) $status => [$status, '{"error":1,"error_message":"Status unavailable"}'];
        }

        yield 'API error' => [200, '{"error":1,"error_message":"Status unavailable"}'];
        yield 'HTTP error without error flag' => [500, '{"error_message":"Status unavailable"}'];
    }

    #[DataProvider('peppolSendingStatusErrorProvider')]
    public function testGetPeppolSendingStatusError(int $status, string $body): void
    {
        $this->expectException(CannotGetInvoiceException::class);
        $this->expectExceptionMessageIsOrContains('Status unavailable');

        $this
            ->getInvoices($this->getHttpClientWithMockResponse(
                new \GuzzleHttp\Psr7\Response($status, [], $body),
            ))
            ->getPeppolSendingStatus(372);
    }

    public function testGetPeppolSendingStatusInvalidJson(): void
    {
        $this->expectException(CannotGetInvoiceException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse(
                new \GuzzleHttp\Psr7\Response(200, [], 'invalid json'),
            ))
            ->getPeppolSendingStatus(372);
    }

    public function testGetPeppolSendingStatusRequestFailed(): void
    {
        $this->expectException(CannotGetInvoiceException::class);

        $this->getInvoices($this->getHttpClientWithMockRequestException())->getPeppolSendingStatus(372);
    }

    public static function sendViaPostOfficeProvider(): \Generator
    {
        $data = [
            'invoice_id' => 1,
            'delivery_name' => null,
            'delivery_address' => null,
            'delivery_city' => null,
            'delivery_country_id' => null,
            'delivery_state' => null,
            'delivery_zip' => null,
        ];

        yield 'with minimal data' => [
            'request_body' => json_encode([
                Invoices::SEND_POST_OFFICE => array_filter($data, static fn (mixed $value): bool => (bool) $value),
            ]),
            ...$data,
        ];

        $data = [
            'invoice_id' => 1,
            'delivery_name' => 'Joe Doe',
            'delivery_address' => 'Pri Suchom mlyne 6',
            'delivery_city' => 'Bratislava',
            'delivery_country_id' => 191,
            'delivery_state' => 'Slovenská republika',
            'delivery_zip' => '811 04',
        ];

        yield 'with all options' => [
            'request_body' => json_encode([
                Invoices::SEND_POST_OFFICE => array_filter($data, static fn (mixed $value): bool => (bool) $value),
            ]),
            ...$data,
        ];
    }

    #[DataProvider('sendViaPostOfficeProvider')]
    public function testSendViaPostOffice(
        string $request_body,
        int $invoice_id,
        ?string $delivery_name,
        ?string $delivery_address,
        ?string $delivery_city,
        ?int $delivery_country_id,
        ?string $delivery_state,
        ?string $delivery_zip,
    ): void {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->sendViaPostOffice(
                id: $invoice_id,
                address: new Address(
                    name: $delivery_name,
                    address: $delivery_address,
                    city: $delivery_city,
                    country_id: $delivery_country_id,
                    state: $delivery_state,
                    zip: $delivery_zip,
                ),
            );

        $request = $this->getLastRequest();

        $this->request()
            ->post('/invoices/post')
            ->withHeader('Content-Type', 'application/json')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($request_body, (string) $request?->getBody());
    }

    public function testSendViaPostOfficeInvoiceNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpNotFoundResponse()))
            ->sendViaPostOffice(1);
    }

    public function testSendViaPostOfficeBadRequest(): void
    {
        $this->expectException(CannotSendInvoiceException::class);
        $this->expectExceptionMessageIsOrContains('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getInvoices($this->getHttpClientReturning($fixture))
            ->sendViaPostOffice(1);
    }

    public function testSendViaPostOfficeRequestFailed(): void
    {
        $this->expectException(CannotSendInvoiceException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockRequestException())
            ->sendViaPostOffice(1);
    }

    public function testSendViaPostOfficeInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockResponse())
            ->sendViaPostOffice(1, new Address(name: "\xB1\x31"));
    }
}
