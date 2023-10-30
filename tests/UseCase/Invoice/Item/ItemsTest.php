<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Invoice\Item;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\UseCase\Invoice\Items;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Contract\Invoice\Item\CannotDeleteInvoiceItemException;

#[CoversClass(Items::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
final class ItemsTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    public static function deleteProvider(): \Generator
    {
        yield 'delete single item' => [
            'invoice_id' => 1,
            'items' => [2],
        ];

        yield 'delete multiple items' => [
            'invoice_id' => 2,
            'items' => [3, 4],
        ];
    }

    /**
     * @param int[] $items
     */
    #[DataProvider('deleteProvider')]
    public function testDelete(int $invoice_id, array $items): void
    {
        $this
            ->getItems($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->delete($invoice_id, $items);

        $this->request()
            ->delete(sprintf('/invoice_items/delete/%s/invoice_id%%3A%d', implode(',', $items), $invoice_id))
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
    }

    public function testDeleteFailed(): void
    {
        $this->expectException(CannotDeleteInvoiceItemException::class);
        $this->expectExceptionMessage('Unexpected error');

        $fixture = __DIR__ . '/../../fixtures/unexpected-error.json';

        $this
            ->getItems($this->getHttpClientReturning($fixture))
            ->delete(1, [2]);
    }

    public function testDeleteRequestFailed(): void
    {
        $this->expectException(CannotDeleteInvoiceItemException::class);

        $this
            ->getItems($this->getHttpClientWithMockRequestException())
            ->delete(1, [2]);
    }

    private function getItems(ClientInterface $client): Items
    {
        return new Items(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
