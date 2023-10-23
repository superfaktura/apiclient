<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Invoice;

use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Contract\Invoice\Item\CannotDeleteInvoiceItemException;

final class Items implements Contract\Invoice\Item\Items
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function delete(int $invoice_id, array $item_ids): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_DELETE,
                $this->base_uri . sprintf(
                    '/invoice_items/delete/%s/invoice_id%%3A%d',
                    implode(',', $item_ids),
                    $invoice_id,
                ),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromHttpResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotDeleteInvoiceItemException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotDeleteInvoiceItemException($request, $response->data['message'] ?? '');
        }
    }
}
