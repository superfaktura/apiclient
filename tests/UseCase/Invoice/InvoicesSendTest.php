<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Invoice;

use GuzzleHttp\Psr7\Response;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\UseCase\Invoice\Email;
use SuperFaktura\ApiClient\UseCase\Invoice\Items;
use SuperFaktura\ApiClient\UseCase\Invoice\Address;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Invoice\Invoices;
use SuperFaktura\ApiClient\Contract\Invoice\Language;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceNotFoundException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotSendInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotMarkInvoiceAsSentException;

#[CoversClass(Invoices::class)]
#[CoversClass(Email::class)]
#[CoversClass(Address::class)]
#[UsesClass(Items::class)]
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

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertGetRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoices/mark_sent/' . $id, $request->getUri()->getPath());
    }

    public function testMarkAsSentNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->markAsSent(1);
    }

    public function testMarkAsSentWrongInvoice(): void
    {
        $this->expectException(CannotMarkInvoiceAsSentException::class);

        $fixture = __DIR__ . '/fixtures/mark-as-sent-wrong-invoice.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
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

        self::assertNotNull($request);
        self::assertPostRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoices/mark_as_sent', $request->getUri()->getPath());
        self::assertSame($request_body, (string) $request->getBody());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testMarkAsSentViaEmailInvoiceNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->markAsSentViaEmail(1, 'joe.doe@superfaktura.sk');
    }

    public function testMarkAsSentViaEmailBadRequest(): void
    {
        $this->expectException(CannotMarkInvoiceAsSentException::class);

        $fixture = __DIR__ . '/fixtures/mark-as-sent-bad-request.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->markAsSentViaEmail(1, 'joe.doe@superfaktura.sk');
    }

    public function testMarkAsSentViaEmailRequestFailed(): void
    {
        $this->expectException(CannotMarkInvoiceAsSentException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockRequestException(),
        )
            ->markAsSentViaEmail(1, 'joe.doe@superfaktura.sk');
    }

    public function testMarkAsSentViaEmailInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(),
        )
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
                Invoices::SEND_EMAIL => array_filter($data),
            ]),
            ...$data,
        ];

        $data = [
            'invoice_id' => 2,
            'to' => 'jane.doe@superfaktura.sk',
            'pdf_language' => Language::ENGLISH,
            'bcc' => ['joe.doe@superfaktura.sk', 'joe@doe.sk'],
            'cc' => ['foo@superfaktura.sk', 'foo@bar.sk'],
            'subject' => 'Foo bar',
            'message' => 'Lorem ipsum dolor sit amet',
        ];

        yield 'with all options' => [
            'request_body' => json_encode([
                Invoices::SEND_EMAIL => array_filter($data),
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

        self::assertNotNull($request);
        self::assertPostRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoices/send', $request->getUri()->getPath());
        self::assertSame($request_body, (string) $request->getBody());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testSendViaEmailInvoiceNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->sendViaEmail(
                1,
                new Email(email: 'joe.doe@superfaktura.sk', pdf_language: Language::SLOVAK),
            );
    }

    public function testSendViaEmailBadRequest(): void
    {
        $this->expectException(CannotSendInvoiceException::class);

        $fixture = __DIR__ . '/fixtures/send-email-bad-request.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->sendViaEmail(
                1,
                new Email(email: 'joe.doe@superfaktura.sk', pdf_language: Language::SLOVAK),
            );
    }

    public function testSendViaEmailRequestFailed(): void
    {
        $this->expectException(CannotSendInvoiceException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockRequestException(),
        )
            ->sendViaEmail(
                1,
                new Email(email: 'joe.doe@superfaktura.sk', pdf_language: Language::SLOVAK),
            );
    }

    public function testSendViaEmailInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(),
        )
            ->sendViaEmail(
                1,
                new Email(email: "\xB1\x31", pdf_language: Language::SLOVAK),
            );
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
                Invoices::SEND_POST_OFFICE => array_filter($data),
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
                Invoices::SEND_POST_OFFICE => array_filter($data),
            ]),
            ...$data,
        ];
    }

    #[DataProvider('sendViaPostOfficeProvider')]
    public function testSendViaPostOffice(
        string $request_body,
        int $invoice_id,
        ?string $name,
        ?string $address,
        ?string $city,
        ?int $country_id,
        ?string $state,
        ?string $zip,
    ): void {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->sendViaPostOffice(
                id: $invoice_id,
                address: new Address(
                    name: $name,
                    address: $address,
                    city: $city,
                    country_id: $country_id,
                    state: $state,
                    zip: $zip,
                ),
            );

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertPostRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoices/post', $request->getUri()->getPath());
        self::assertSame($request_body, (string) $request->getBody());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testSendViaPostOfficeInvoiceNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->sendViaPostOffice(1);
    }

    public function testSendViaPostOfficeBadRequest(): void
    {
        $this->expectException(CannotSendInvoiceException::class);

        $fixture = __DIR__ . '/fixtures/send-email-bad-request.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->sendViaPostOffice(1);
    }

    public function testSendViaPostOfficeRequestFailed(): void
    {
        $this->expectException(CannotSendInvoiceException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockRequestException(),
        )
            ->sendViaPostOffice(1);
    }

    public function testSendViaPostOfficeInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(),
        )
            ->sendViaPostOffice(1, new Address(name: "\xB1\x31"));
    }
}
