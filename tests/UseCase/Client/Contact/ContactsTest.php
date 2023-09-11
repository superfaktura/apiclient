<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Client\Contact;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Client\Contact\Contacts;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Client\ClientNotFoundException;
use SuperFaktura\ApiClient\Contract\Client\Contact\CannotCreateContactException;
use SuperFaktura\ApiClient\Contract\Client\Contact\CannotDeleteContactException;
use SuperFaktura\ApiClient\Contract\Client\Contact\CannotGetAllContactsException;

#[CoversClass(Contacts::class)]
#[CoversClass(ClientNotFoundException::class)]
#[CoversClass(CannotCreateContactException::class)]
#[CoversClass(CannotGetAllContactsException::class)]
#[CoversClass(CannotDeleteContactException::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(CannotCreateRequestException::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RateLimit::class)]
final class ContactsTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    public function testGetAllClientWithContacts(): void
    {
        $fixture = __DIR__ . '/fixtures/client_with_contacts.json';

        $response = $this->getContacts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getAllByClientId(1);

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetAllClientWithoutContacts(): void
    {
        $fixture = __DIR__ . '/fixtures/client_without_contacts.json';

        $response = $this->getContacts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getAllByClientId(1);

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetAllClientNotFound(): void
    {
        $this->expectException(ClientNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getContacts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getAllByClientId(1);
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllContactsException::class);

        $this
            ->getContacts($this->getHttpClientWithMockRequestException())
            ->getAllByClientId(1);
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllContactsException::class);

        $this->getContacts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{'),
            ),
        )
            ->getAllByClientId(1);
    }

    public function testDelete(): void
    {
        $this
            ->getContacts($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->delete(1);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertSame(RequestMethodInterface::METHOD_GET, $request->getMethod());
        self::assertSame('/contact_people/delete/1', $request->getUri()->getPath());
        self::assertSame(self::AUTHORIZATION_HEADER_VALUE, $request->getHeaderLine('Authorization'));
    }

    public function testDeleteClientNotFound(): void
    {
        $this->expectException(CannotDeleteContactException::class);

        $fixture = __DIR__ . '/fixtures/delete-contact-not-found.json';

        $this->getContacts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->delete(1);
    }

    public function testDeleteInsufficientPermissions(): void
    {
        $this->expectException(CannotDeleteContactException::class);

        $fixture = __DIR__ . '/fixtures/delete-insufficient-permissions.json';

        $this->getContacts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->delete(1);
    }

    /**
     * @return \Generator<array{client_id: int, data: array<string, mixed>}>
     */
    public static function createProvider(): \Generator
    {
        $data = [
            'name' => 'Joe Doe',
            'email' => 'joe@doe.com',
        ];

        yield 'client contact is created' => [
            'client_id' => 1,
            'data' => $data,
            'request_body' => 'data=' . json_encode(['ContactPerson' => ['client_id' => 1, ...$data]]),
        ];

        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@doe.com',
            'phone' => '+421949123456',
        ];

        yield 'another client contact is created' => [
            'client_id' => 2,
            'data' => $data,
            'request_body' => 'data=' . json_encode(['ContactPerson' => ['client_id' => 2, ...$data]]),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('createProvider')]
    public function testCreate(int $client_id, array $data, string $request_body): void
    {
        $this
            ->getContacts($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->create($client_id, $data);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertSame(RequestMethodInterface::METHOD_POST, $request->getMethod());
        self::assertSame('/contact_people/add/api%3A1', $request->getUri()->getPath());
        self::assertSame($request_body, (string) $request->getBody());
        self::assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        self::assertSame(self::AUTHORIZATION_HEADER_VALUE, $request->getHeaderLine('Authorization'));
    }

    public function testCreateMissingRequiredData(): void
    {
        $this->expectException(CannotCreateContactException::class);

        $fixture = __DIR__ . '/fixtures/create-contact-missing-data.json';

        $this->getContacts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->create(1, []);
    }

    public function testCreateInsufficientPermissions(): void
    {
        $this->expectException(CannotCreateContactException::class);

        $fixture = __DIR__ . '/fixtures/create-contact-insufficient-permissions.json';

        $this->getContacts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->create(1, []);
    }

    public function testCreateClientNotFound(): void
    {
        $this->expectException(CannotCreateContactException::class);

        $fixture = __DIR__ . '/fixtures/create-contact-client-not-found.json';

        $this->getContacts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->create(1, []);
    }

    public function testCreateResponseDecodeFailed(): void
    {
        $this->expectException(CannotCreateContactException::class);

        $this->getContacts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{'),
            ),
        )
            ->create(1, []);
    }

    public function testCreateInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this->getContacts(
            $this->getHttpClientWithMockResponse(),
        )
            ->create(1, ['name' => NAN]);
    }

    private function getContacts(Client $client): Contacts
    {
        return new Contacts(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            // no real requests are made during testing
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
