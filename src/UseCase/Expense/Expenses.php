<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Expense;

use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\StatusCodeInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Filter\QueryParamsConvertor;
use SuperFaktura\ApiClient\Contract\Expense\ExpenseStatus;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Contract\Expense\ExpenseNotFoundException;
use SuperFaktura\ApiClient\Contract\Expense\CannotGetExpenseException;
use SuperFaktura\ApiClient\Contract\Expense\CannotGetAllExpensesException;

final class Expenses implements Contract\Expense\Expenses
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private QueryParamsConvertor $query_params_convertor,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function getById(int $id): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                sprintf('%s/expenses/view/%d.json', $this->base_uri, $id),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetExpenseException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new ExpenseNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotGetExpenseException($request, $response->data['error_message'] ?? '');
        }

        return $response;
    }

    public function getAll(ExpensesQuery $query = new ExpensesQuery()): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/expenses/index.json/' . $this->getListQueryString($query),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            return $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetAllExpensesException($request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    private function getListQueryString(ExpensesQuery $query): string
    {
        return $this->query_params_convertor->convert([
            'listinfo' => 1,
            'page' => $query->page,
            'per_page' => $query->items_per_page,
            'sort' => $query->sort->attribute,
            'direction' => $query->sort->direction->value,
            'amount_from' => $query->amount_from,
            'amount_to' => $query->amount_to,
            'category' => $query->category_id,
            'client_id' => $query->client_id,
            'created' => $query->created?->period->value,
            'created_since' => $query->created?->from?->format('c'),
            'created_to' => $query->created?->to?->format('c'),
            'modified' => $query->modified?->period->value,
            'modified_since' => $query->modified?->from?->format('c'),
            'modified_to' => $query->modified?->to?->format('c'),
            'delivery' => $query->delivery?->period->value,
            'delivery_since' => $query->delivery?->from?->format('c'),
            'delivery_to' => $query->delivery?->to?->format('c'),
            'due' => $query->due?->period->value,
            'due_since' => $query->due?->from?->format('c'),
            'due_to' => $query->due?->to?->format('c'),
            'payment_type' => $query->payment_type?->value,
            'type' => $query->type?->value,
            'status' => $query->statuses !== []
                ? implode(
                    ExpensesQuery::VALUES_SEPARATOR,
                    array_map(static fn (ExpenseStatus $status) => $status->value, $query->statuses),
                )
                : null,

            'search' => $query->full_text !== null
                ? base64_encode($query->full_text)
                : null,
        ]);
    }
}
