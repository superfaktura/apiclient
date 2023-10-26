<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Expense;

use GuzzleHttp\Psr7\Response;
use SuperFaktura\ApiClient\Filter\Sort;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\UsesClass;
use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Filter\TimePeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Contract\PaymentType;
use SuperFaktura\ApiClient\Filter\SortDirection;
use SuperFaktura\ApiClient\Filter\TimePeriodEnum;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Expense\Expenses;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;
use SuperFaktura\ApiClient\Contract\Expense\ExpenseType;
use SuperFaktura\ApiClient\UseCase\Expense\ExpensesQuery;
use SuperFaktura\ApiClient\Contract\Expense\ExpenseStatus;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Expense\ExpenseNotFoundException;
use SuperFaktura\ApiClient\Contract\Expense\CannotGetExpenseException;
use SuperFaktura\ApiClient\Contract\Expense\CannotCreateExpenseException;
use SuperFaktura\ApiClient\Contract\Expense\CannotGetAllExpensesException;

#[CoversClass(Expenses::class)]
#[CoversClass(CannotCreateExpenseException::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(NamedParamsConvertor::class)]
#[UsesClass(Sort::class)]
#[UsesClass(ExpensesQuery::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
final class ExpensesTest extends ExpensesTestCase
{
    public function testGetById(): void
    {
        $fixture = __DIR__ . '/fixtures/expense.json';

        $response = $this
            ->getExpenses($this->getHttpClientReturning($fixture))
            ->getById(1);

        $this->request()
            ->get('/expenses/view/1.json')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetByIdNotFound(): void
    {
        $this->expectException(ExpenseNotFoundException::class);

        $this
            ->getExpenses($this->getHttpClientWithMockResponse($this->getHttpNotFoundResponse()))
            ->getById(1);
    }

    public function testGetByIdGenericError(): void
    {
        $fixture = __DIR__ . '/fixtures/generic-error.json';

        $this->expectException(CannotGetExpenseException::class);
        $this->expectExceptionMessage('Expense error');

        $this
            ->getExpenses($this->getHttpClientReturning($fixture))
            ->getById(1);
    }

    public function testGetByIdRequestFailed(): void
    {
        $this->expectException(CannotGetExpenseException::class);

        $this
            ->getExpenses($this->getHttpClientWithMockRequestException())
            ->getById(1);
    }

    public function testGetByIdResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetExpenseException::class);

        $this
            ->getExpenses(
                $this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()),
            )
            ->getById(1);
    }

    public function testGetAll(): void
    {
        $fixture = __DIR__ . '/fixtures/list.json';

        $response = $this->getExpenses(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getAll();

        $this->request()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllExpensesException::class);

        $this->getExpenses(
            $this->getHttpClientWithMockRequestException(),
        )
            ->getAll();
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllExpensesException::class);

        $this->getExpenses(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{"items":'),
            ),
        )
            ->getAll();
    }

    public static function getAllQueryProvider(): \Generator
    {
        $base_uri = '/expenses/index.json/';

        yield 'no filter specified, default query parameters' => [
            'expected' => $base_uri . self::getQueryString(),
            'query' => new ExpensesQuery(),
        ];

        yield 'filter by amount from' => [
            'expected' => $base_uri . self::getQueryString(['amount_from' => 1.0]),
            'query' => new ExpensesQuery(amount_from: 1.0),
        ];

        yield 'filter by amount to' => [
            'expected' => $base_uri . self::getQueryString(['amount_to' => 9.99]),
            'query' => new ExpensesQuery(amount_to: 9.99),
        ];

        yield 'filter by category id' => [
            'expected' => $base_uri . self::getQueryString(['category' => 1]),
            'query' => new ExpensesQuery(category_id: 1),
        ];

        yield 'filter by client id' => [
            'expected' => $base_uri . self::getQueryString(['client_id' => 1]),
            'query' => new ExpensesQuery(client_id: 1),
        ];

        yield 'filter by created date since to range' => [
            'expected' => $base_uri . self::getQueryString([
                'created' => TimePeriodEnum::FROM_TO->value,
                'created_since' => '2023-01-02T01:02:03+00:00',
                'created_to' => '2023-02-03T04:05:06+00:00',
            ]),
            'query' => new ExpensesQuery(
                created: new TimePeriod(
                    period: TimePeriodEnum::FROM_TO,
                    from: new \DateTimeImmutable('2023-01-02 01:02:03'),
                    to: new \DateTimeImmutable('2023-02-03 04:05:06'),
                ),
            ),
        ];

        yield 'filter by modified date since to range' => [
            'expected' => $base_uri . self::getQueryString([
                'modified' => TimePeriodEnum::FROM_TO->value,
                'modified_since' => '2023-01-02T01:02:03+00:00',
                'modified_to' => '2023-02-03T04:05:06+00:00',
            ]),
            'query' => new ExpensesQuery(
                modified: new TimePeriod(
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
            'query' => new ExpensesQuery(
                delivery: new TimePeriod(
                    period: TimePeriodEnum::FROM_TO,
                    from: new \DateTimeImmutable('2023-01-02 01:02:03'),
                    to: new \DateTimeImmutable('2023-02-03 04:05:06'),
                ),
            ),
        ];

        yield 'filter by due date since to range' => [
            'expected' => $base_uri . self::getQueryString([
                'due' => TimePeriodEnum::FROM_TO->value,
                'due_since' => '2023-01-02T01:02:03+00:00',
                'due_to' => '2023-02-03T04:05:06+00:00',
            ]),
            'query' => new ExpensesQuery(
                due: new TimePeriod(
                    period: TimePeriodEnum::FROM_TO,
                    from: new \DateTimeImmutable('2023-01-02 01:02:03'),
                    to: new \DateTimeImmutable('2023-02-03 04:05:06'),
                ),
            ),
        ];

        yield 'filter by payment type' => [
            'expected' => $base_uri . self::getQueryString(['payment_type' => PaymentType::TRANSFER->value]),
            'query' => new ExpensesQuery(payment_type: PaymentType::TRANSFER),
        ];

        yield 'full text search' => [
            'expected' => $base_uri . self::getQueryString(['search' => base64_encode('SuperFaktúra')]),
            'query' => new ExpensesQuery(full_text: 'SuperFaktúra'),
        ];

        yield 'pagination' => [
            'expected' => $base_uri . self::getQueryString(['page' => 2, 'per_page' => 50]),
            'query' => new ExpensesQuery(page: 2, items_per_page: 50),
        ];

        yield 'sort' => [
            'expected' => $base_uri . self::getQueryString(
                ['sort' => 'name', 'direction' => SortDirection::DESC->value],
            ),
            'query' => new ExpensesQuery(sort: new Sort(attribute: 'name', direction: SortDirection::DESC)),
        ];

        yield 'filter by expense status' => [
            'expected' => $base_uri . self::getQueryString(['status' => ExpenseStatus::PAID->value]),
            'query' => new ExpensesQuery(statuses: [ExpenseStatus::PAID]),
        ];

        yield 'filter by multiple expense statuses' => [
            'expected' => $base_uri . self::getQueryString([
                'status' => ExpenseStatus::PAID->value . '|' . ExpenseStatus::PARTIALLY_PAID->value,
            ]),
            'query' => new ExpensesQuery(statuses: [ExpenseStatus::PAID, ExpenseStatus::PARTIALLY_PAID]),
        ];

        yield 'filter by expense type' => [
            'expected' => $base_uri . self::getQueryString(['type' => ExpenseType::BILL->value]),
            'query' => new ExpensesQuery(type: ExpenseType::BILL),
        ];
    }

    #[DataProvider('getAllQueryProvider')]
    public function testGetAllQuery(string $expected, ExpensesQuery $query): void
    {
        $this
            ->getExpenses($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->getAll($query);

        $request = $this->getLastRequest();

        $this->request()
            ->get($expected)
            ->assert();
    }

    public static function createProvider(): \Generator
    {
        $data = [
            Expenses::EXPENSE => ['name' => 'Expense'],
        ];

        yield 'expense is created with minimal data' => [
            'data' => $data,
            'request_body' => 'data=' . json_encode($data),
        ];

        $data = [
            Expenses::EXPENSE => ['name' => 'Expense 2'],
            Expenses::EXPENSE_ITEM => [
                ['name' => 'Bar', 'unit_price' => 10],
            ],
            Expenses::CLIENT => ['name' => 'Jane Doe', 'ico' => '87654321'],
            Expenses::EXPENSE_EXTRA => ['pickup_point_id' => 1],
            Expenses::MY_DATA => ['name' => 'SuperFaktura s.r.o.'],
            Expenses::TAG => [1, 2, 3],
        ];

        yield 'expense is created with all data' => [
            'data' => $data,
            'request_body' => 'data=' . json_encode(
                array_merge($data, [Expenses::TAG => [Expenses::TAG => $data[Expenses::TAG]]]),
            ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('createProvider')]
    public function testCreate(array $data, string $request_body): void
    {
        $fixture = __DIR__ . '/fixtures/create-update-success.json';

        $response = $this
            ->getExpenses($this->getHttpClientReturning($fixture))
            ->create(
                expense: $data[Expenses::EXPENSE],
                items: $data[Expenses::EXPENSE_ITEM] ?? [],
                client: $data[Expenses::CLIENT] ?? [],
                extra: $data[Expenses::EXPENSE_EXTRA] ?? [],
                my_data: $data[Expenses::MY_DATA] ?? [],
                tags: $data[Expenses::TAG] ?? [],
            );

        $this->request()
            ->post('/expenses/add')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($request_body, (string) $this->getLastRequest()?->getBody());
        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public static function createErrorsProvider(): \Generator
    {
        yield 'generic error' => [
            'fixture' => __DIR__ . '/fixtures/create-error.json',
            'errors' => ['Chýbajúce údaje'],
        ];

        yield 'validation error' => [
            'fixture' => __DIR__ . '/fixtures/create-validation-error.json',
            'errors' => [
                'number' => ['Číslo dokladu sa nezhoduje s uvedeným obdobím'],
            ],
        ];
    }

    /**
     * @param string[]|array<string, string[]> $errors
     */
    #[DataProvider('createErrorsProvider')]
    public function testCreateValidationErrors(string $fixture, array $errors): void
    {
        try {
            $this
                ->getExpenses($this->getHttpClientReturning($fixture))
                ->create(expense: []);

            self::fail(sprintf('Expected exception of type: %s to be thrown', CannotCreateExpenseException::class));
        } catch (CannotCreateExpenseException $exception) {
            self::assertEquals($errors, $exception->getErrors());
        }
    }

    public function testCreateResponseDecodeFailed(): void
    {
        $this->expectException(CannotCreateExpenseException::class);

        $this
            ->getExpenses(
                $this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()),
            )
            ->create(expense: []);
    }

    public function testCreateInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this
            ->getExpenses($this->getHttpClientWithMockResponse())
            ->create(expense: ['name' => NAN]);
    }

    public function testCreateRequestFailed(): void
    {
        $this->expectException(CannotCreateExpenseException::class);
        $this
            ->getExpenses($this->getHttpClientWithMockRequestException())
            ->create(expense: []);
    }

    /**
     * @param array<string, string|int|float|bool> $params
     */
    private static function getQueryString(array $params = []): string
    {
        $default_query_params = [
            'listinfo' => 1,
            'page' => 1,
            'per_page' => 100,
            'sort' => 'id',
            'direction' => SortDirection::DESC->value,
        ];

        return (new NamedParamsConvertor())->convert(
            array_merge($default_query_params, $params),
        );
    }
}
