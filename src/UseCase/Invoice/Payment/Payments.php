<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Invoice\Payment;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\StatusCodeInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceNotFoundException;
use SuperFaktura\ApiClient\Contract\Invoice\Payment\CannotPayInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\Payment\CannotMarkAsUnpayableException;
use SuperFaktura\ApiClient\Contract\Invoice\Payment\CannotDeleteInvoicePaymentException;

final class Payments implements Contract\Invoice\Payment\Payments
{
    public const INVOICE_PAYMENT = 'InvoicePayment';

    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function markAsUnPayable(int $invoice_id): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/invoices/will_not_be_paid/' . $invoice_id,
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotMarkAsUnpayableException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new InvoiceNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotMarkAsUnpayableException($request, $response->data['error_message'] ?? '');
        }
    }

    public function create(int $id, Payment $payment = new Payment()): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/invoice_payments/add/ajax%3A1/api%3A1',
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody(Utils::streamFor('data=' . $this->transformDataToJson($id, $payment)));

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotPayInvoiceException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotPayInvoiceException($request, $response->data['message'] ?? '');
        }

        return $response;
    }

    public function delete(int $id): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_DELETE,
                $this->base_uri . '/invoice_payments/delete/' . $id,
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotDeleteInvoicePaymentException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotDeleteInvoicePaymentException($request, $response->data['message'] ?? '');
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
                    self::INVOICE_PAYMENT => array_filter([
                        'invoice_id' => $id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency?->value,
                        'payment_type' => $payment->payment_type?->value,
                        'document_number' => $payment->document_number,
                        'cash_register_id' => $payment->cash_register_id,
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
