<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Tag;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\StatusCodeInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Contract\Tag\TagNotFoundException;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Tag\CannotCreateTagException;
use SuperFaktura\ApiClient\Contract\Tag\CannotDeleteTagException;
use SuperFaktura\ApiClient\Contract\Tag\CannotUpdateTagException;
use SuperFaktura\ApiClient\Contract\Tag\CannotGetAllTagsException;
use SuperFaktura\ApiClient\Contract\Tag\TagAlreadyExistsException;

final class Tags implements Contract\Tag\Tags
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function getAll(): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/tags/index.json',
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            return $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetAllTagsException($request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function create(string $tag): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/tags/add',
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($this->tagDataToJson($tag)));

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotCreateTagException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_CONFLICT) {
            throw new TagAlreadyExistsException($request, $response->data['error_message'] ?? '');
        }

        if ($response->isError()) {
            throw new CannotCreateTagException($request, $response->data['error_message'] ?? '');
        }

        return $response;
    }

    public function update(int $id, string $tag): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_PATCH,
                $this->base_uri . '/tags/edit/' . $id,
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($this->tagDataToJson($tag)));

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotUpdateTagException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new TagNotFoundException($request, $response->data['error_message'] ?? '');
        }

        if ($response->isError()) {
            throw new CannotUpdateTagException($request, $response->data['error_message'] ?? '');
        }

        return $response;
    }

    public function delete(int $id): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_DELETE,
                $this->base_uri . '/tags/delete/' . $id,
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotDeleteTagException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new TagNotFoundException($request, $response->data['error_message'] ?? '');
        }

        if ($response->isError()) {
            throw new CannotDeleteTagException($request, $response->data['error_message'] ?? '');
        }
    }

    /**
     * @throws CannotCreateRequestException
     */
    private function tagDataToJson(string $tag): string
    {
        try {
            return json_encode(['name' => $tag], JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
