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
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Invoice\Invoices;
use SuperFaktura\ApiClient\UseCase\Invoice\Payment\Payments;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceNotFoundException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotSendInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotMarkInvoiceAsSentException;

#[CoversClass(Invoices::class)]
#[CoversClass(Email::class)]
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
        $this->expectExceptionMessage('Unexpected error');

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
        $this->expectExceptionMessage('Unexpected error');

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
        $this->expectExceptionMessage('Unexpected error');

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
        $this->expectExceptionMessage('Unexpected error');

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
