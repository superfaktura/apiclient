<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\RelatedDocument;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\RelatedDocument\Relation;
use SuperFaktura\ApiClient\Contract\RelatedDocument\DocumentType;
use SuperFaktura\ApiClient\UseCase\RelatedDocument\RelatedDocuments;
use SuperFaktura\ApiClient\Contract\RelatedDocument\CannotLinkDocumentsException;
use SuperFaktura\ApiClient\Contract\RelatedDocument\CannotUnlinkDocumentsException;

#[CoversClass(RelatedDocuments::class)]
#[CoversClass(Relation::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
final class RelatedDocumentsTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    public static function linkProvider(): \Generator
    {
        yield 'link expense to invoice' => [
            'request_url' => '/invoices/addRelatedItem',
            'request_body' => json_encode([
                'parent_id' => 1,
                'parent_type' => DocumentType::INVOICE->value,
                'child_id' => 2,
                'child_type' => DocumentType::EXPENSE->value,
            ], JSON_THROW_ON_ERROR),
            'relation' => new Relation(
                parent_id: 1,
                parent_type: DocumentType::INVOICE,
                child_id: 2,
                child_type: DocumentType::EXPENSE,
            ),
        ];

        yield 'link invoice to expense' => [
            'request_url' => '/expenses/addRelatedItem',
            'request_body' => json_encode([
                'parent_id' => 3,
                'parent_type' => DocumentType::EXPENSE->value,
                'child_id' => 4,
                'child_type' => DocumentType::INVOICE->value,
            ], JSON_THROW_ON_ERROR),
            'relation' => new Relation(
                parent_id: 3,
                parent_type: DocumentType::EXPENSE,
                child_id: 4,
                child_type: DocumentType::INVOICE,
            ),
        ];
    }

    #[DataProvider('linkProvider')]
    public function testLink(string $request_url, string $request_body, Relation $relation): void
    {
        $fixture = __DIR__ . '/fixtures/link-success.json';

        $response = $this
            ->getRelatedDocuments($this->getHttpClientReturning($fixture))
            ->link($relation);

        $this
            ->request()
            ->post($request_url)
            ->withHeader('Content-Type', 'application/json')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($request_body, (string) $this->getLastRequest()?->getBody());
        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testLinkErrorResponse(): void
    {
        $this->expectException(CannotLinkDocumentsException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getRelatedDocuments($this->getHttpClientReturning($fixture))
            ->link($this->getMockRelation());
    }

    public function testLinkRequestFailed(): void
    {
        $this->expectException(CannotLinkDocumentsException::class);

        $this
            ->getRelatedDocuments($this->getHttpClientWithMockRequestException())
            ->link($this->getMockRelation());
    }

    public static function relationIdProvider(): \Generator
    {
        yield 'relation' => [1];
        yield 'another relation' => [2];
    }

    #[DataProvider('relationIdProvider')]
    public function testUnlink(int $id): void
    {
        $this
            ->getRelatedDocuments($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->unlink($id);

        $this->request()
            ->delete('/invoices/deleteRelatedItem/' . $id)
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
    }

    public function testUnlinkFailed(): void
    {
        $this->expectException(CannotUnlinkDocumentsException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getRelatedDocuments($this->getHttpClientReturning($fixture))
            ->unlink(1);
    }

    public function testUnlinkRequestFailed(): void
    {
        $this->expectException(CannotUnlinkDocumentsException::class);

        $this
            ->getRelatedDocuments($this->getHttpClientWithMockRequestException())
            ->unlink(1);
    }

    private function getRelatedDocuments(ClientInterface $client): RelatedDocuments
    {
        return new RelatedDocuments(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }

    private function getMockRelation(): Relation
    {
        return new Relation(
            parent_id: 1,
            parent_type: DocumentType::INVOICE,
            child_id: 2,
            child_type: DocumentType::INVOICE,
        );
    }
}
