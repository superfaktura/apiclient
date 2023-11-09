<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use SuperFaktura\ApiClient\Filter\Sort;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Filter\TimePeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Filter\SortDirection;
use SuperFaktura\ApiClient\Filter\TimePeriodEnum;
use SuperFaktura\ApiClient\UseCase\Client\Clients;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;
use SuperFaktura\ApiClient\UseCase\Client\ClientsQuery;
use SuperFaktura\ApiClient\UseCase\Client\Contact\Contacts;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Client\ClientNotFoundException;
use SuperFaktura\ApiClient\Contract\Client\CannotGetClientException;
use SuperFaktura\ApiClient\Contract\Client\CannotCreateClientException;
use SuperFaktura\ApiClient\Contract\Client\CannotDeleteClientException;
use SuperFaktura\ApiClient\Contract\Client\CannotUpdateClientException;
use SuperFaktura\ApiClient\Contract\Client\CannotGetAllClientsException;

#[CoversClass(Clients::class)]
#[CoversClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[CoversClass(CannotGetClientException::class)]
#[CoversClass(CannotGetAllClientsException::class)]
#[CoversClass(CannotCreateClientException::class)]
#[CoversClass(CannotCreateRequestException::class)]
#[CoversClass(ClientsQuery::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(NamedParamsConvertor::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(Sort::class)]
#[UsesClass(Contacts::class)]
final class ClientsTest extends TestCase
{
    public function testGetClientById(): void
    {
        $fixture = __DIR__ . '/fixtures/client.json';

        $response = $this->getClients(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getById(1);

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetClientByIdNotFound(): void
    {
        $this->expectException(ClientNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getClients(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getById(1);
    }

    public function testGetClientByIdInvalidId(): void
    {
        $this->expectException(CannotGetClientException::class);

        $fixture = __DIR__ . '/fixtures/get-by-id-invalid-id.json';

        $this->getClients(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_BAD_REQUEST, [], $this->jsonFromFixture($fixture)),
            ),
        )->getById(0);
    }

    public function testGetClientByIdRequestFailed(): void
    {
        $this->expectException(CannotGetClientException::class);

        $this->getClients(
            $this->getHttpClientWithMockRequestException(),
        )
            ->getById(1);
    }

    public function testGetClientByIdResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetClientException::class);

        $this->getClients(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{"Client":'),
            ),
        )
            ->getById(1);
    }

    public function testGetAll(): void
    {
        $fixture = __DIR__ . '/fixtures/clients.json';

        $response = $this->getClients(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getAll();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public static function getAllQueryProvider(): \Generator
    {
        $base_uri = '/clients/index.json/';

        yield 'no filter specified, default query parameters' => [
            'expected' => $base_uri . self::getQueryString(),
            'query' => new ClientsQuery(),
        ];

        yield 'filter by uuid' => [
            'expected' => $base_uri . self::getQueryString(['search_uuid' => 'd98ae494-76a7-47f0-b4c1-b77d91d8898a']),
            'query' => new ClientsQuery(uuid: 'd98ae494-76a7-47f0-b4c1-b77d91d8898a'),
        ];

        yield 'full text search' => [
            'expected' => $base_uri . self::getQueryString(['search' => base64_encode('SuperFaktúra')]),
            'query' => new ClientsQuery(full_text: 'SuperFaktúra'),
        ];

        yield 'filter by first character' => [
            'expected' => $base_uri . self::getQueryString(['char_filter' => 'S']),
            'query' => new ClientsQuery(first_letter: 'S'),
        ];

        yield 'filter by tag id' => [
            'expected' => $base_uri . self::getQueryString(['tag' => 1]),
            'query' => new ClientsQuery(tag: 1),
        ];

        yield 'filter by created date since to range' => [
            'expected' => $base_uri . self::getQueryString([
                'created' => TimePeriodEnum::FROM_TO->value,
                'created_since' => '2023-01-02T01:02:03+00:00',
                'created_to' => '2023-02-03T04:05:06+00:00',
            ]),
            'query' => new ClientsQuery(
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
            'query' => new ClientsQuery(
                modified: new TimePeriod(
                    period: TimePeriodEnum::FROM_TO,
                    from: new \DateTimeImmutable('2023-01-02 01:02:03'),
                    to: new \DateTimeImmutable('2023-02-03 04:05:06'),
                ),
            ),
        ];

        yield 'pagination' => [
            'expected' => $base_uri . self::getQueryString(['page' => 2, 'per_page' => 50]),
            'query' => new ClientsQuery(page: 2, items_per_page: 50),
        ];

        yield 'sort' => [
            'expected' => $base_uri . self::getQueryString(
                ['sort' => 'name', 'direction' => SortDirection::DESC->value],
            ),
            'query' => new ClientsQuery(sort: new Sort(attribute: 'name', direction: SortDirection::DESC)),
        ];
    }

    #[DataProvider('getAllQueryProvider')]
    public function testGetAllQuery(string $expected, ClientsQuery $query): void
    {
        $http_client = $this->createMock(Client::class);
        $http_client
            ->expects(self::once())
            ->method('sendRequest')
            ->with(
                new Request(
                    method: RequestMethodInterface::METHOD_GET,
                    uri: $expected,
                    headers: ['Authorization' => ''],
                ),
            )
            ->willReturn($this->getHttpOkResponse());

        $this->getClients($http_client)->getAll($query);
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllClientsException::class);

        $this->getClients(
            $this->getHttpClientWithMockRequestException(),
        )
            ->getAll();
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllClientsException::class);

        $this->getClients(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{"items":'),
            ),
        )
            ->getAll();
    }

    public function testDelete(): void
    {
        $this->getClients($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->delete(1);

        $this->request()
            ->delete('/clients/delete/1')
            ->withContentTypeJson()
            ->assert();
    }

    public function testDeleteNotFound(): void
    {
        $this->expectException(ClientNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getClients($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
        ))
            ->delete(0);
    }

    public function testDeleteInsufficientPermissions(): void
    {
        $this->expectException(CannotDeleteClientException::class);
        $this->expectExceptionMessage('You are not authorized to delete this client');

        $fixture = __DIR__ . '/fixtures/delete-insufficient-permissions.json';
        $use_case = $this->getClients($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], $this->jsonFromFixture($fixture)),
        ));
        $use_case->delete(0);
    }

    public function testDeleteWithInvoices(): void
    {
        $this->expectException(CannotDeleteClientException::class);
        $this->expectExceptionMessage('You can\'t delete contact with invoices');

        $fixture = __DIR__ . '/fixtures/delete-client-with-contacts.json';

        $use_case = $this->getClients($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], $this->jsonFromFixture($fixture)),
        ));
        $use_case->delete(1);
    }

    public function testDeleteResponseDecodeFailed(): void
    {
        $this->expectException(CannotDeleteClientException::class);
        $this->expectExceptionMessage('Syntax error');

        $use_case = $this->getClients($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()));
        $use_case->delete(0);
    }

    public function testDeleteRequestFailed(): void
    {
        $this->expectException(CannotDeleteClientException::class);
        $this->expectExceptionMessage(self::ERROR_COMMUNICATING_WITH_SERVER_MESSAGE);

        $use_case = $this->getClients($this->getHttpClientWithMockRequestException());
        $use_case->delete(0);
    }

    private function getClients(Client $client): Clients
    {
        return new Clients(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            query_params_convertor: new NamedParamsConvertor(),
            // no real requests are made during testing
            base_uri: '',
            authorization_header_value: '',
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
            'per_page' => 100,
            'sort' => 'id',
            'direction' => SortDirection::ASC->value,
        ];

        return (new NamedParamsConvertor())->convert(
            array_merge($default_query_params, $params),
        );
    }

    /**
     * @throws \JsonException
     */
    public function testCreate(): void
    {
        $data = ['Client' => ['name' => 'Jozef Mrkvicka']];

        $request_body = json_encode($data, JSON_THROW_ON_ERROR);

        $fixture = __DIR__ . '/fixtures/create.json';
        $response_body_json = $this->jsonFromFixture($fixture);

        $use_case = $this->getClients($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
        ));

        $response = $use_case->create($data);
        $expected_response_body = json_decode($response_body_json, true, 512, JSON_THROW_ON_ERROR);

        $this->request()
            ->post('/clients/create')
            ->withBody($request_body)
            ->withContentTypeJson()
            ->assert();

        self::assertEquals($expected_response_body, $response->data);
    }

    public function testCreateInsufficientPermissions(): void
    {
        $this->expectException(CannotCreateClientException::class);
        $this->expectExceptionMessage('You can\'t create new items');

        $fixture = __DIR__ . '/fixtures/insufficient-permissions.json';
        $use_case = $this->getClients($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], $this->jsonFromFixture($fixture)),
        ));
        $use_case->create([]);
    }

    public function testCreateResponseDecodeFailed(): void
    {
        $this->expectException(CannotCreateClientException::class);
        $this->expectExceptionMessage('Syntax error');

        $use_case = $this->getClients($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()));
        $use_case->create([]);
    }

    public function testCreateRequestFailed(): void
    {
        $this->expectException(CannotCreateClientException::class);
        $this->expectExceptionMessage(self::ERROR_COMMUNICATING_WITH_SERVER_MESSAGE);

        $use_case = $this->getClients($this->getHttpClientWithMockRequestException());
        $use_case->create([]);
    }

    public function testCreateWithNonValidJsonArray(): void
    {
        $this->expectException(CannotCreateRequestException::class);
        $this->expectExceptionMessage(self::JSON_ENCODE_FAILURE_MESSAGE);

        $use_case = $this->getClients($this->getHttpClientWithMockResponse($this->getHttpOkResponse()));
        $use_case->create(['Client' => ['name' => NAN]]);
    }

    /**
     * @throws \JsonException
     */
    public function testUpdate(): void
    {
        $id = 1;
        $data = [
            'Client' => ['name' => 'Jozef Mrkvicka', 'email' => 'jozef.mrkvicka@gmail.com'],
        ];
        $expected_request_body = [
            'Client' => ['id' => $id, ...$data['Client']],
        ];

        $request_body = json_encode($expected_request_body, JSON_THROW_ON_ERROR);

        $this->getClients($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->update($id, $data);

        $this->request()
            ->patch('/clients/edit/' . $id)
            ->withBody($request_body)
            ->withContentTypeJson()
            ->assert();
    }

    public function testUpdateNotFound(): void
    {
        $this->expectException(ClientNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getClients(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->update(1, ['Client' => ['name' => 'Jozef Mrkvicka II', 'email' => 'jozef.mrkvicka2@gmail.com']]);
    }

    public function testUpdateInsufficientPermissions(): void
    {
        $this->expectException(CannotUpdateClientException::class);
        $this->expectExceptionMessage('You can\'t edit this item');

        $fixture = __DIR__ . '/fixtures/edit-insufficient-permissions.json';
        $use_case = $this->getClients($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], $this->jsonFromFixture($fixture)),
        ));
        $use_case->update(0, []);
    }

    public function testUpdateResponseDecodeFailed(): void
    {
        $this->expectException(CannotUpdateClientException::class);
        $this->expectExceptionMessage('Syntax error');

        $use_case = $this->getClients($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()));
        $use_case->update(0, []);
    }

    public function testUpdateRequestFailed(): void
    {
        $this->expectException(CannotUpdateClientException::class);
        $this->expectExceptionMessage(self::ERROR_COMMUNICATING_WITH_SERVER_MESSAGE);

        $use_case = $this->getClients($this->getHttpClientWithMockRequestException());
        $use_case->update(0, []);
    }

    public function testUpdateWithNonValidJsonArray(): void
    {
        $this->expectException(CannotCreateRequestException::class);
        $this->expectExceptionMessage(self::JSON_ENCODE_FAILURE_MESSAGE);

        $use_case = $this->getClients($this->getHttpClientWithMockResponse($this->getHttpOkResponse()));
        $use_case->create(['Client' => ['name' => NAN]]);
    }
}
