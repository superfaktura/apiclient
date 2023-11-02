<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Tag;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use SuperFaktura\ApiClient\UseCase\Tag\Tags;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Contract\Tag\TagNotFoundException;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Tag\CannotCreateTagException;
use SuperFaktura\ApiClient\Contract\Tag\CannotDeleteTagException;
use SuperFaktura\ApiClient\Contract\Tag\CannotUpdateTagException;
use SuperFaktura\ApiClient\Contract\Tag\CannotGetAllTagsException;
use SuperFaktura\ApiClient\Contract\Tag\TagAlreadyExistsException;

#[CoversClass(Tags::class)]
#[CoversClass(RequestException::class)]
#[UsesClass(Response::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
final class TagsTest extends TestCase
{
    protected const AUTHORIZATION_HEADER_VALUE = 'foo';

    public function testGetAll(): void
    {
        $fixture = __DIR__ . '/fixtures/all.json';

        $response = $this
            ->getTags($this->getHttpClientReturning($fixture))
            ->getAll();

        $this->request()
            ->get('/tags/index.json')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllTagsException::class);

        $this
            ->getTags($this->getHttpClientWithMockRequestException())
            ->getAll();
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllTagsException::class);

        $this
            ->getTags($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->getAll();
    }

    public static function createProvider(): \Generator
    {
        yield 'tag is created' => [
            'request_body' => json_encode(['name' => 'fizz'], JSON_THROW_ON_ERROR),
            'tag' => 'fizz',
        ];

        yield 'another tag is created' => [
            'request_body' => json_encode(['name' => 'buzz'], JSON_THROW_ON_ERROR),
            'tag' => 'buzz',
        ];
    }

    #[DataProvider('createProvider')]
    public function testCreate(string $request_body, string $tag): void
    {
        $fixture = __DIR__ . '/fixtures/save-success.json';

        $response = $this
            ->getTags($this->getHttpClientReturning($fixture))
            ->create($tag);

        $this
            ->request()
            ->post('/tags/add')
            ->withHeader('Content-Type', 'application/json')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($request_body, (string) $this->getLastRequest()?->getBody());
        self::assertSame($this->arrayFromFixture($fixture), $response->data);

    }

    public function testCreateErrorResponse(): void
    {
        $this->expectException(CannotCreateTagException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getTags($this->getHttpClientReturning($fixture))
            ->create('fizz');
    }

    public function testCreateTagAlreadyExists(): void
    {
        $this->expectException(TagAlreadyExistsException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getTags($this->getHttpClientReturning($fixture, StatusCodeInterface::STATUS_CONFLICT))
            ->create('fizz');
    }

    public function testCreateRequestFailed(): void
    {
        $this->expectException(CannotCreateTagException::class);

        $this
            ->getTags($this->getHttpClientWithMockRequestException())
            ->create('fizz');
    }

    public function testCreateInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this
            ->getTags($this->getHttpClientWithMockResponse())
            ->create("\xB1\x31");
    }

    public function testCreateResponseDecodeFailed(): void
    {
        $this->expectException(CannotCreateTagException::class);

        $this
            ->getTags($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->create('fizz');
    }

    public static function updateProvider(): \Generator
    {
        yield 'tag is created' => [
            'request_body' => json_encode(['name' => 'fizz'], JSON_THROW_ON_ERROR),
            'id' => 1,
            'tag' => 'fizz',
        ];

        yield 'another tag is created' => [
            'request_body' => json_encode(['name' => 'buzz'], JSON_THROW_ON_ERROR),
            'id' => 2,
            'tag' => 'buzz',
        ];
    }

    #[DataProvider('updateProvider')]
    public function testUpdate(string $request_body, int $id, string $tag): void
    {
        $fixture = __DIR__ . '/fixtures/save-success.json';

        $response = $this
            ->getTags($this->getHttpClientReturning($fixture))
            ->update($id, $tag);

        $this
            ->request()
            ->patch('/tags/edit/' . $id)
            ->withHeader('Content-Type', 'application/json')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($request_body, (string) $this->getLastRequest()?->getBody());
        self::assertSame($this->arrayFromFixture($fixture), $response->data);

    }

    public function testUpdateErrorResponse(): void
    {
        $this->expectException(CannotUpdateTagException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getTags($this->getHttpClientReturning($fixture))
            ->update(1, 'fizz');
    }

    public function testUpdateTagNotFound(): void
    {
        $this->expectException(TagNotFoundException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getTags($this->getHttpClientReturning($fixture, StatusCodeInterface::STATUS_NOT_FOUND))
            ->update(1, 'fizz');
    }

    public function testUpdateRequestFailed(): void
    {
        $this->expectException(CannotUpdateTagException::class);

        $this
            ->getTags($this->getHttpClientWithMockRequestException())
            ->update(1, 'fizz');
    }

    public function testUpdateInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this
            ->getTags($this->getHttpClientWithMockResponse())
            ->update(1, "\xB1\x31");
    }

    public function testUpdateResponseDecodeFailed(): void
    {
        $this->expectException(CannotUpdateTagException::class);

        $this
            ->getTags($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->update(1, 'fizz');
    }

    /**
     * @return \Generator<int[]>
     */
    public static function tagIdProvider(): \Generator
    {
        yield 'tag' => [1];
        yield 'another tag' => [2];
    }

    #[DataProvider('tagIdProvider')]
    public function testDelete(int $id): void
    {
        $this
            ->getTags($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->delete($id);

        $this->request()
            ->delete('/tags/delete/' . $id)
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
    }

    public function testDeleteNotFound(): void
    {
        $this->expectException(TagNotFoundException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getTags($this->getHttpClientReturning($fixture, StatusCodeInterface::STATUS_NOT_FOUND))
            ->delete(1);
    }

    public function testDeleteFailed(): void
    {
        $this->expectException(CannotDeleteTagException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getTags($this->getHttpClientReturning($fixture))
            ->delete(1);
    }

    public function testDeleteRequestFailed(): void
    {
        $this->expectException(CannotDeleteTagException::class);

        $this
            ->getTags($this->getHttpClientWithMockRequestException())
            ->delete(1);
    }

    public function testDeleteResponseDecodeFailed(): void
    {
        $this->expectException(CannotDeleteTagException::class);

        $this
            ->getTags($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->delete(1);
    }

    private function getTags(ClientInterface $client): Tags
    {
        return new Tags(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
