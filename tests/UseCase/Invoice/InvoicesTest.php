<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Invoice;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use SuperFaktura\ApiClient\Filter\Sort;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Filter\TimePeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Filter\SortDirection;
use SuperFaktura\ApiClient\Filter\TimePeriodEnum;
use SuperFaktura\ApiClient\UseCase\Invoice\Items;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Invoice\Invoices;
use SuperFaktura\ApiClient\Contract\Invoice\Language;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;
use SuperFaktura\ApiClient\Contract\Invoice\PaymentType;
use SuperFaktura\ApiClient\Contract\Invoice\DeliveryType;
use SuperFaktura\ApiClient\UseCase\Invoice\InvoicesQuery;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceStatus;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceNotFoundException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotGetInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotCreateInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotDeleteInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotUpdateInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotGetAllInvoicesException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotChangeInvoiceLanguageException;

#[CoversClass(Invoices::class)]
#[CoversClass(CannotCreateInvoiceException::class)]
#[CoversClass(CannotUpdateInvoiceException::class)]
#[CoversClass(InvoicesQuery::class)]
#[CoversClass(RequestException::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(NamedParamsConvertor::class)]
#[UsesClass(Sort::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(Items::class)]
final class InvoicesTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    public function testGetById(): void
    {
        $fixture = __DIR__ . '/fixtures/detail-single.json';

        $response = $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getById(1);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertGetRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoices/view/1.json', $request->getUri()->getPath());
        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetByIdNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getById(1);
    }

    public function testGetByIdInsufficientPermissions(): void
    {
        $this->expectException(CannotGetInvoiceException::class);

        $fixture = __DIR__ . '/fixtures/insufficient-permissions.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getById(1);
    }

    public function testGetByIdRequestFailed(): void
    {
        $this->expectException(CannotGetInvoiceException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockRequestException(),
        )
            ->getById(1);
    }

    public function testGetByIdResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetInvoiceException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{"Invoice":'),
            ),
        )
            ->getById(1);
    }

    public function testGetByIds(): void
    {
        $fixture = __DIR__ . '/fixtures/detail-multiple.json';

        $response = $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getByIds([1,2]);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertGetRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoices/getInvoiceDetails/1,2', $request->getUri()->getPath());
        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetByIdsRequestFailed(): void
    {
        $this->expectException(CannotGetInvoiceException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockRequestException(),
        )
            ->getByIds([1,2]);
    }

    public function testGetByIdsResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetInvoiceException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{"Invoice":'),
            ),
        )
            ->getByIds([1,2]);
    }

    public function testGetAll(): void
    {
        $fixture = __DIR__ . '/fixtures/list.json';

        $response = $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getAll();

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertGetRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllInvoicesException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockRequestException(),
        )
            ->getAll();
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllInvoicesException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{"items":'),
            ),
        )
            ->getAll();
    }

    public static function getAllQueryProvider(): \Generator
    {
        $base_uri = '/invoices/index.json/';

        yield 'no filter specified, default query parameters' => [
            'expected' => $base_uri . self::getQueryString(),
            'query' => new InvoicesQuery(),
        ];

        yield 'filter by amount from' => [
            'expected' => $base_uri . self::getQueryString(['amount_from' => 1.0]),
            'query' => new InvoicesQuery(amount_from: 1.0),
        ];

        yield 'filter by amount to' => [
            'expected' => $base_uri . self::getQueryString(['amount_to' => 9.99]),
            'query' => new InvoicesQuery(amount_to: 9.99),
        ];

        yield 'filter by client id' => [
            'expected' => $base_uri . self::getQueryString(['client_id' => 1]),
            'query' => new InvoicesQuery(client_id: 1),
        ];

        yield 'filter by created date since to range' => [
            'expected' => $base_uri . self::getQueryString([
                'created' => TimePeriodEnum::FROM_TO->value,
                'created_since' => '2023-01-02T01:02:03+00:00',
                'created_to' => '2023-02-03T04:05:06+00:00',
            ]),
            'query' => new InvoicesQuery(
                created: new TimePeriod(
                    period: TimePeriodEnum::FROM_TO,
                    from: new \DateTimeImmutable('2023-01-02 01:02:03'),
                    to: new \DateTimeImmutable('2023-02-03 04:05:06'),
                ),
            ),
        ];

        yield 'filter by delivery date since to range' => [
            'expected' => $base_uri . self::getQueryString([
                'delivery' => TimePeriodEnum::FROM_TO->value,
                'delivery_since' => '2023-01-02T01:02:03+00:00',
                'delivery_to' => '2023-02-03T04:05:06+00:00',
            ]),
            'query' => new InvoicesQuery(
                delivery: new TimePeriod(
                    period: TimePeriodEnum::FROM_TO,
                    from: new \DateTimeImmutable('2023-01-02 01:02:03'),
                    to: new \DateTimeImmutable('2023-02-03 04:05:06'),
                ),
            ),
        ];

        yield 'filter by delivery type' => [
            'expected' => $base_uri . self::getQueryString(['delivery_type' => DeliveryType::COURIER->value]),
            'query' => new InvoicesQuery(delivery_types: [DeliveryType::COURIER]),
        ];

        yield 'filter by multiple delivery types' => [
            'expected' => $base_uri . self::getQueryString([
                'delivery_type' => DeliveryType::COURIER->value . '|' . DeliveryType::MAIL->value,
            ]),
            'query' => new InvoicesQuery(delivery_types: [DeliveryType::COURIER, DeliveryType::MAIL]),
        ];

        yield 'filter by ignored invoice id' => [
            'expected' => $base_uri . self::getQueryString(['ignore' => 1]),
            'query' => new InvoicesQuery(ignored_invoices: [1]),
        ];

        yield 'filter by multiple ignored invoice id' => [
            'expected' => $base_uri . self::getQueryString(['ignore' => '1|2']),
            'query' => new InvoicesQuery(ignored_invoices: [1, 2]),
        ];

        yield 'filter by formatted invoice number' => [
            'expected' => $base_uri . self::getQueryString(['invoice_no_formatted' => 'FA2023001']),
            'query' => new InvoicesQuery(formatted_number: 'FA2023001'),
        ];

        yield 'filter by modified date since to range' => [
            'expected' => $base_uri . self::getQueryString([
                'modified' => TimePeriodEnum::FROM_TO->value,
                'modified_since' => '2023-01-02T01:02:03+00:00',
                'modified_to' => '2023-02-03T04:05:06+00:00',
            ]),
            'query' => new InvoicesQuery(
                modified: new TimePeriod(
                    period: TimePeriodEnum::FROM_TO,
                    from: new \DateTimeImmutable('2023-01-02 01:02:03'),
                    to: new \DateTimeImmutable('2023-02-03 04:05:06'),
                ),
            ),
        ];

        yield 'filter by order number' => [
            'expected' => $base_uri . self::getQueryString(['order_no' => 'OBJ2023001']),
            'query' => new InvoicesQuery(order_number: 'OBJ2023001'),
        ];

        yield 'filter by paid date since to range' => [
            'expected' => $base_uri . self::getQueryString([
                'paid' => TimePeriodEnum::FROM_TO->value,
                'paid_since' => '2023-01-02T01:02:03+00:00',
                'paid_to' => '2023-02-03T04:05:06+00:00',
            ]),
            'query' => new InvoicesQuery(
                paid: new TimePeriod(
                    period: TimePeriodEnum::FROM_TO,
                    from: new \DateTimeImmutable('2023-01-02 01:02:03'),
                    to: new \DateTimeImmutable('2023-02-03 04:05:06'),
                ),
            ),
        ];

        yield 'filter by payment type' => [
            'expected' => $base_uri . self::getQueryString(['payment_type' => PaymentType::CARD->value]),
            'query' => new InvoicesQuery(payment_types: [PaymentType::CARD]),
        ];

        yield 'filter by multiple payment types' => [
            'expected' => $base_uri . self::getQueryString([
                'payment_type' => PaymentType::CARD->value . '|' . PaymentType::CASH->value,
            ]),
            'query' => new InvoicesQuery(payment_types: [PaymentType::CARD, PaymentType::CASH]),
        ];

        yield 'full text search' => [
            'expected' => $base_uri . self::getQueryString(['search' => base64_encode('SuperFaktúra')]),
            'query' => new InvoicesQuery(full_text: 'SuperFaktúra'),
        ];

        yield 'filter by invoice status' => [
            'expected' => $base_uri . self::getQueryString(['status' => InvoiceStatus::PAID->value]),
            'query' => new InvoicesQuery(status: InvoiceStatus::PAID),
        ];

        yield 'filter by tag id' => [
            'expected' => $base_uri . self::getQueryString(['tag' => 1]),
            'query' => new InvoicesQuery(tag: 1),
        ];

        yield 'filter by variable symbol' => [
            'expected' => $base_uri . self::getQueryString(['variable' => '123456789']),
            'query' => new InvoicesQuery(variable_symbol: '123456789'),
        ];

        yield 'pagination' => [
            'expected' => $base_uri . self::getQueryString(['page' => 2, 'per_page' => 50]),
            'query' => new InvoicesQuery(page: 2, items_per_page: 50),
        ];

        yield 'sort' => [
            'expected' => $base_uri . self::getQueryString(
                ['sort' => 'name', 'direction' => SortDirection::DESC->value],
            ),
            'query' => new InvoicesQuery(sort: new Sort(attribute: 'name', direction: SortDirection::DESC)),
        ];
    }

    #[DataProvider('getAllQueryProvider')]
    public function testGetAllQuery(string $expected, InvoicesQuery $query): void
    {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->getAll($query);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertSame($expected, $request->getUri()->getPath());
    }

    /**
     * @return \Generator<array{data: array<string, mixed>, request_body: string}>
     */
    public static function createProvider(): \Generator
    {
        $data = [
            Invoices::INVOICE => ['name' => 'Invoice'],
            Invoices::INVOICE_ITEM => [
                ['name' => 'Foo', 'unit_price' => 9.99],
            ],
            Invoices::CLIENT => ['name' => 'Joe Doe', 'ico' => '12345678'],
        ];

        yield 'invoice is created with minimal data' => [
            'data' => $data,
            'request_body' => 'data=' . json_encode($data),
        ];

        $data = [
            Invoices::INVOICE => ['name' => 'Invoice 2'],
            Invoices::INVOICE_ITEM => [
                ['name' => 'Bar', 'unit_price' => 10],
            ],
            Invoices::CLIENT => ['name' => 'Jane Doe', 'ico' => '87654321'],
            Invoices::INVOICE_SETTING => ['language' => 'eng'],
            Invoices::INVOICE_EXTRA => ['pickup_point_id' => 1],
            Invoices::MY_DATA => ['name' => 'SuperFaktura s.r.o.'],
            Invoices::TAG => [1, 2, 3],
        ];

        yield 'invoice is created with all data' => [
            'data' => $data,
            'request_body' => 'data=' . json_encode(
                array_merge($data, [Invoices::TAG => [Invoices::TAG => $data[Invoices::TAG]]]),
            ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('createProvider')]
    public function testCreate(array $data, string $request_body): void
    {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->create(
                invoice: $data[Invoices::INVOICE],
                items: $data[Invoices::INVOICE_ITEM],
                client: $data[Invoices::CLIENT],
                settings: $data[Invoices::INVOICE_SETTING] ?? [],
                extra: $data[Invoices::INVOICE_EXTRA] ?? [],
                my_data: $data[Invoices::MY_DATA] ?? [],
                tags: $data[Invoices::TAG] ?? [],
            );

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertPostRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoices/create', $request->getUri()->getPath());
        self::assertSame($request_body, (string) $request->getBody());
        self::assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
    }

    public static function createValidationErrorsProvider(): \Generator
    {
        yield 'missing client data' => [
            'response' => __DIR__ . '/fixtures/missing-client-data.json',
            'errors' => [
                'data_bad_format' => 'Missing required client data.',
            ],
        ];

        yield 'invalid currency' => [
            'response' => __DIR__ . '/fixtures/invalid-currency.json',
            'errors' => ['Incorrect currency'],
        ];

        yield 'zero invoice items' => [
            'response' => __DIR__ . '/fixtures/zero-invoice-items.json',
            'errors' => [
                'type' => ['Dokument musí obsahovať aspoň jednu položku'],
            ],
        ];

        yield 'invalid dates' => [
            'response' => __DIR__ . '/fixtures/invalid-dates.json',
            'errors' => [
                'created' => ['Neplatný dátum.'],
                'delivery' => ['Neplatný dátum.'],
            ],
        ];
    }

    /**
     * @param string[]|array<string, string[]> $errors
     */
    #[DataProvider('createValidationErrorsProvider')]
    public function testCreateValidationErrors(string $response, array $errors): void
    {
        try {
            $this->getInvoices(
                $this->getHttpClientWithMockResponse(
                    new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($response)),
                ),
            )
                ->create(
                    invoice: [],
                    items: [],
                    client: [],
                );

            self::fail(sprintf('Expected exception of type: %s to be thrown', CannotCreateInvoiceException::class));
        } catch (CannotCreateInvoiceException $exception) {
            self::assertEquals($errors, $exception->getErrors());
        }
    }

    public function testCreateInsufficientPermissions(): void
    {
        $this->expectException(CannotCreateInvoiceException::class);

        $fixture = __DIR__ . '/fixtures/insufficient-permissions.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->create(
                invoice: [],
                items: [],
                client: [],
            );
    }

    public function testCreateResponseDecodeFailed(): void
    {
        $this->expectException(CannotCreateInvoiceException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{'),
            ),
        )
            ->create(
                invoice: [],
                items: [],
                client: [],
            );
    }

    public function testCreateInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(),
        )
            ->create(
                invoice: ['name' => NAN],
                items: [],
                client: [],
            );
    }

    public function testCreateRequestFailed(): void
    {
        $this->expectException(CannotCreateInvoiceException::class);
        $this->getInvoices($this->getHttpClientWithMockRequestException())->create([], [], []);
    }

    /**
     * @return \Generator<array{invoice_id: int, data: array<string, mixed>, request_body: string}>
     */
    public static function updateProvider(): \Generator
    {
        $data = [
            Invoices::INVOICE => ['discount' => 5],
        ];

        yield 'invoice is updated' => [
            'invoice_id' => 1,
            'data' => $data,
            'request_body' => 'data=' . json_encode([
                Invoices::INVOICE => ['id' => 1, ...$data[Invoices::INVOICE]],
            ]),
        ];

        $data = [
            Invoices::INVOICE => ['id' => 2],
            Invoices::INVOICE_ITEM => [
                ['name' => 'Bar', 'unit_price' => 10],
            ],
            Invoices::CLIENT => ['name' => 'Jane Doe', 'ico' => '87654321'],
            Invoices::INVOICE_SETTING => ['language' => 'eng'],
            Invoices::INVOICE_EXTRA => ['pickup_point_id' => 1],
            Invoices::MY_DATA => ['name' => 'SuperFaktura s.r.o.'],
            Invoices::TAG => [1, 2, 3],
        ];

        yield 'another invoice is updated' => [
            'invoice_id' => 2,
            'data' => $data,
            'request_body' => 'data=' . json_encode(
                array_merge($data, [Invoices::TAG => [Invoices::TAG => $data[Invoices::TAG]]]),
            ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('updateProvider')]
    public function testUpdate(int $invoice_id, array $data, string $request_body): void
    {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->update(
                id: $invoice_id,
                invoice: $data[Invoices::INVOICE] ?? [],
                items: $data[Invoices::INVOICE_ITEM] ?? [],
                client: $data[Invoices::CLIENT] ?? [],
                settings: $data[Invoices::INVOICE_SETTING] ?? [],
                extra: $data[Invoices::INVOICE_EXTRA] ?? [],
                my_data: $data[Invoices::MY_DATA] ?? [],
                tags: $data[Invoices::TAG] ?? [],
            );

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertPostRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoices/edit', $request->getUri()->getPath());
        self::assertSame($request_body, (string) $request->getBody());
        self::assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
    }

    public static function updateValidationErrorsProvider(): \Generator
    {
        yield 'invalid currency' => [
            'response' => __DIR__ . '/fixtures/invalid-currency.json',
            'errors' => ['Incorrect currency'],
        ];

        yield 'invalid dates' => [
            'response' => __DIR__ . '/fixtures/invalid-dates.json',
            'errors' => [
                'created' => ['Neplatný dátum.'],
                'delivery' => ['Neplatný dátum.'],
            ],
        ];
    }

    /**
     * @param string[]|array<string, string[]> $errors
     */
    #[DataProvider('updateValidationErrorsProvider')]
    public function testUpdateValidationErrors(string $response, array $errors): void
    {
        try {
            $this->getInvoices(
                $this->getHttpClientWithMockResponse(
                    new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($response)),
                ),
            )
                ->update(1);

            self::fail(sprintf('Expected exception of type: %s to be thrown', CannotUpdateInvoiceException::class));
        } catch (CannotUpdateInvoiceException $exception) {
            self::assertEquals($exception->getErrors(), $errors);
        }
    }

    public function testUpdateNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->update(1);
    }

    public function testUpdateInsufficientPermissions(): void
    {
        $this->expectException(CannotUpdateInvoiceException::class);

        $fixture = __DIR__ . '/fixtures/insufficient-permissions.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->update(1);
    }

    public function testUpdateResponseDecodeFailed(): void
    {
        $this->expectException(CannotUpdateInvoiceException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{'),
            ),
        )
            ->update(1);
    }

    public function testUpdateInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(),
        )
            ->update(1, ['name' => NAN]);
    }

    public function testUpdateRequestFailed(): void
    {
        $this->expectException(CannotUpdateInvoiceException::class);
        $this->getInvoices($this->getHttpClientWithMockRequestException())->update(0);
    }

    /**
     * @return \Generator<int[]>
     */
    public static function deleteProvider(): \Generator
    {
        yield 'delete invoice' => [1];
        yield 'delete another invoice' => [2];
    }

    #[DataProvider('deleteProvider')]
    public function testDelete(int $id): void
    {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->delete($id);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertDeleteRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/invoices/delete/' . $id, $request->getUri()->getPath());
    }

    public function testDeleteNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->delete(1);
    }

    public function testDeleteFailed(): void
    {
        $this->expectException(CannotDeleteInvoiceException::class);

        $fixture = __DIR__ . '/fixtures/delete-error.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->delete(1);
    }

    public function testDeleteRequestFailed(): void
    {
        $this->expectException(CannotDeleteInvoiceException::class);

        $this
            ->getInvoices($this->getHttpClientWithMockRequestException())
            ->delete(1);
    }

    public function testDeleteResponseDecodeFailed(): void
    {
        $this->expectException(CannotDeleteInvoiceException::class);
        $this->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->delete(0);
    }

    /**
     * @return \Generator<array{id: int, language: Language}>
     */
    public static function changeLanguageProvider(): \Generator
    {
        yield 'change invoice language' => [
            'id' => 1,
            'language' => Language::ENGLISH,
        ];

        yield 'change another invoice language' => [
            'id' => 2,
            'language' => Language::CZECH,
        ];
    }

    #[DataProvider('changeLanguageProvider')]
    public function testChangeLanguage(int $id, Language $language): void
    {
        $this
            ->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->changeLanguage($id, $language);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertGetRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame(
            sprintf('/invoices/setinvoicelanguage/%d/lang:%s', $id, $language->value),
            $request->getUri()->getPath(),
        );
    }

    public function testChangeLanguageInvoiceNotFound(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->changeLanguage(1, Language::ENGLISH);
    }

    public function testChangeLanguageInsufficientPermissions(): void
    {
        $this->expectException(CannotChangeInvoiceLanguageException::class);

        $fixture = __DIR__ . '/fixtures/change-language-insufficient-permissions.json';

        $this->getInvoices(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->changeLanguage(1, Language::ENGLISH);
    }

    public function testChangeLanguageRequestFailed(): void
    {
        $this->expectException(CannotChangeInvoiceLanguageException::class);

        $this->getInvoices(
            $this->getHttpClientWithMockRequestException(),
        )
            ->changeLanguage(1, Language::ENGLISH);
    }

    public function testChangeLanguageResponseDecodeFailed(): void
    {
        $this->expectException(CannotChangeInvoiceLanguageException::class);

        $this->getInvoices($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->changeLanguage(0, Language::SLOVAK);
    }

    private function getInvoices(Client $client): Invoices
    {
        return new Invoices(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            query_params_convertor: new NamedParamsConvertor(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }

    /**
     * @param array<string, string|int|float|bool> $params
     */
    private static function getQueryString(array $params = []): string
    {
        $default_query_params = [
            'listinfo' => 1,
            'page' => 1,
            'per_page' => 200,
            'sort' => 'id',
            'direction' => SortDirection::DESC->value,
        ];

        return (new NamedParamsConvertor())->convert(
            array_merge($default_query_params, $params),
        );
    }
}
