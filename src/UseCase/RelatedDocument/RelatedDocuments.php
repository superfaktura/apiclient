<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\RelatedDocument;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\RelatedDocument\DocumentType;
use SuperFaktura\ApiClient\Contract\RelatedDocument\CannotLinkDocumentsException;
use SuperFaktura\ApiClient\Contract\RelatedDocument\CannotUnlinkDocumentsException;

final class RelatedDocuments implements Contract\RelatedDocument\RelatedDocuments
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function link(Relation $relation): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . $this->getLinkRequestUri($relation->parent_type),
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($this->relationToJson($relation)));

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotLinkDocumentsException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotLinkDocumentsException($request, $response->data['error_message'] ?? '');
        }

        return $response;
    }

    public function unlink(int $relation_id): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_DELETE,
                $this->base_uri . '/invoices/deleteRelatedItem/' . $relation_id,
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotUnlinkDocumentsException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotUnlinkDocumentsException($request, $response->data['error_message'] ?? '');
        }
    }

    /**
     * @throws CannotCreateRequestException
     */
    private function relationToJson(Relation $relation): string
    {
        try {
            return json_encode(
                [
                    'parent_id' => $relation->parent_id,
                    'parent_type' => $relation->parent_type->value,
                    'child_id' => $relation->child_id,
                    'child_type' => $relation->child_type->value,
                ],
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function getLinkRequestUri(DocumentType $parent_type): string
    {
        return match ($parent_type) {
            DocumentType::INVOICE => '/invoices/addRelatedItem',
            DocumentType::EXPENSE => '/expenses/addRelatedItem',
        };
    }
}
