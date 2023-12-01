<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Expense\Payment;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Expense\Payment\CannotPayExpenseException;
use SuperFaktura\ApiClient\Contract\Expense\Payment\CannotDeleteExpensePaymentException;

final readonly class Payments implements Contract\Expense\Payment\Payments
{
    public const EXPENSE_PAYMENT = 'ExpensePayment';

    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function create(int $id, Payment $payment): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/expense_payments/add',
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody(Utils::streamFor('data=' . $this->transformDataToJson($id, $payment)));

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotPayExpenseException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotPayExpenseException($request, $response->data['message'] ?? '');
        }

        return $response;
    }

    public function delete(int $id): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_DELETE,
                $this->base_uri . '/expense_payments/delete/' . $id,
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotDeleteExpensePaymentException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotDeleteExpensePaymentException($request, $response->data['message'] ?? '');
        }
    }

    /**
     * @throws CannotCreateRequestException
     */
    private function transformDataToJson(int $id, Payment $payment): string
    {
        try {
            return json_encode(
                [
                    self::EXPENSE_PAYMENT => array_filter([
                        'expense_id' => $id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency?->value,
                        'payment_type' => $payment->payment_type?->value,
                        'created' => $payment->payment_date?->format('Y-m-d'),
                    ]),
                ],
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
