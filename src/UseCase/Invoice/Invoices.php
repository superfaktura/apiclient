<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Invoice;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\StatusCodeInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\BinaryResponse;
use SuperFaktura\ApiClient\Contract\Invoice\Language;
use SuperFaktura\ApiClient\Filter\QueryParamsConvertor;
use SuperFaktura\ApiClient\Contract\Invoice\PaymentType;
use SuperFaktura\ApiClient\Contract\Invoice\DeliveryType;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Response\CannotCreateResponseException;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceNotFoundException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotGetInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotSendInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotCreateInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotDeleteInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotUpdateInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotGetAllInvoicesException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotDownloadInvoiceException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotMarkInvoiceAsSentException;
use SuperFaktura\ApiClient\Contract\Invoice\CannotChangeInvoiceLanguageException;

final readonly class Invoices implements Contract\Invoice\Invoices
{
    public const INVOICE = 'Invoice';

    public const INVOICE_ITEM = 'InvoiceItem';

    public const INVOICE_SETTING = 'InvoiceSetting';

    public const INVOICE_EXTRA = 'InvoiceExtra';

    public const INVOICE_EMAIL = 'InvoiceEmail';

    public const CLIENT = 'Client';

    public const MY_DATA = 'MyData';

    public const SEND_EMAIL = 'Email';

    public const SEND_POST_OFFICE = 'Post';

    public const TAG = 'Tag';

    public Items $items;

    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private QueryParamsConvertor $query_params_convertor,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
        $this->items = new Items(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            base_uri: $this->base_uri,
            authorization_header_value: $this->authorization_header_value,
        );
    }

    public function getById(int $id): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                sprintf('%s/invoices/view/%d.json', $this->base_uri, $id),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetInvoiceException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new InvoiceNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotGetInvoiceException($request, $response->data['error_message'] ?? '');
        }

        return $response;
    }

    public function getByIds(array $ids): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/invoices/getInvoiceDetails/' . implode(',', $ids),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            return $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetInvoiceException($request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getAll(InvoicesQuery $query = new InvoicesQuery()): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/invoices/index.json/' . $this->getListQueryString($query),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            return $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetAllInvoicesException($request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function downloadPdf(int $id, Language $language): BinaryResponse
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                sprintf('%s/%s/invoices/pdf/%d', $this->base_uri, $language->value, $id),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromBinaryResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|CannotCreateResponseException $e) {
            throw new CannotDownloadInvoiceException($request, $e->getMessage(), $e->getCode(), $e);
        }

        return match ($response->status_code) {
            StatusCodeInterface::STATUS_OK => $response,
            StatusCodeInterface::STATUS_NOT_FOUND => throw new InvoiceNotFoundException($request),
            default => throw new CannotDownloadInvoiceException($request),
        };
    }

    public function create(
        array $invoice,
        array $items,
        array $client,
        array $settings = [],
        array $extra = [],
        array $my_data = [],
        array $tags = [],
    ): Response {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/invoices/create',
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody(
                Utils::streamFor('data=' . $this->invoiceDataToJson(
                    invoice: $invoice,
                    items: $items,
                    client: $client,
                    settings: $settings,
                    extra: $extra,
                    my_data: $my_data,
                    tags: $tags,
                )),
            );

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotCreateInvoiceException($request, [], $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotCreateInvoiceException(
                $request,
                $this->normalizeErrorMessages($response),
            );
        }

        return $response;
    }

    public function update(
        int $id,
        array $invoice = [],
        array $items = [],
        array $client = [],
        array $settings = [],
        array $extra = [],
        array $my_data = [],
        array $tags = [],
    ): Response {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/invoices/edit',
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody(
                Utils::streamFor('data=' . $this->invoiceDataToJson(
                    invoice: ['id' => $id, ...$invoice],
                    items: $items,
                    client: $client,
                    settings: $settings,
                    extra: $extra,
                    my_data: $my_data,
                    tags: $tags,
                )),
            );

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotUpdateInvoiceException($request, [], $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new InvoiceNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotUpdateInvoiceException(
                $request,
                $this->normalizeErrorMessages($response),
            );
        }

        return $response;
    }

    public function delete(int $id): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_DELETE,
                $this->base_uri . '/invoices/delete/' . $id,
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotDeleteInvoiceException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new InvoiceNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotDeleteInvoiceException($request, $response->data['error_message'] ?? '');
        }
    }

    public function changeLanguage(int $id, Language $language): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . sprintf('/invoices/setinvoicelanguage/%d/lang:%s', $id, $language->value),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotChangeInvoiceLanguageException($request, $e->getMessage(), $e->getCode(), $e);
        }

        match ($response->status_code) {
            StatusCodeInterface::STATUS_OK => null,
            StatusCodeInterface::STATUS_NOT_FOUND => throw new InvoiceNotFoundException($request),
            default => throw new CannotChangeInvoiceLanguageException(
                $request, $response->data['message'] ?? '',
            ),
        };
    }

    public function markAsSent(int $id): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/invoices/mark_sent/' . $id,
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotMarkInvoiceAsSentException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new InvoiceNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotMarkInvoiceAsSentException($request, $response->data['error_message'] ?? '');
        }
    }

    public function markAsSentViaEmail(
        int $id,
        string $email,
        string $subject = '',
        string $message = '',
    ): void {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/invoices/mark_as_sent',
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(
                Utils::streamFor($this->invoiceEmailDataToJson(
                    id: $id,
                    email: $email,
                    subject: $subject,
                    message: $message,
                )),
            );

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotMarkInvoiceAsSentException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new InvoiceNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotMarkInvoiceAsSentException($request, $response->data['error_message'] ?? '');
        }
    }

    public function sendViaEmail(int $id, Email $email): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/invoices/send',
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($this->emailDataToJson($id, $email)));

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotSendInvoiceException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new InvoiceNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotSendInvoiceException($request, $response->data['error_message'] ?? '');
        }
    }

    public function sendViaPostOffice(int $id, Address $address = new Address()): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/invoices/post',
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($this->postOfficeDataToJson($id, $address)));

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotSendInvoiceException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new InvoiceNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotSendInvoiceException($request, $response->data['error_message'] ?? '');
        }
    }

    /**
     * @return string[]
     */
    private function normalizeErrorMessages(Response $response): array
    {
        if (is_array($response->data['error_message'])) {
            return $response->data['error_message'];
        }

        return [$response->data['error_message'] ?? ''];
    }

    /**
     * @param array<string, mixed> $invoice
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $client
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $extra
     * @param array<string, mixed> $my_data
     * @param int[] $tags
     *
     * @throws CannotCreateRequestException
     */
    private function invoiceDataToJson(
        array $invoice,
        array $items,
        array $client,
        array $settings,
        array $extra,
        array $my_data,
        array $tags,
    ): string {
        try {
            return json_encode(
                array_filter([
                    self::INVOICE => $invoice,
                    self::INVOICE_ITEM => $items,
                    self::CLIENT => $client,
                    self::INVOICE_SETTING => $settings,
                    self::INVOICE_EXTRA => $extra,
                    self::MY_DATA => $my_data,
                    self::TAG => $tags !== []
                        ? [self::TAG => $tags]
                        : [],
                ]),
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws CannotCreateRequestException
     */
    private function invoiceEmailDataToJson(
        int $id,
        string $email,
        string $subject,
        string $message,
    ): string {
        try {
            return json_encode(
                [
                    self::INVOICE_EMAIL => [
                        'invoice_id' => $id,
                        'email' => $email,
                        'subject' => $subject,
                        'message' => $message,
                    ],
                ],
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws CannotCreateRequestException
     */
    private function emailDataToJson(int $id, Email $email): string
    {
        try {
            return json_encode(
                [
                    self::SEND_EMAIL => array_filter(
                        [
                            'invoice_id' => $id,
                            'to' => $email->email,
                            'pdf_language' => $email->pdf_language->value,
                            'bcc' => $email->bcc,
                            'cc' => $email->cc,
                            'subject' => $email->subject,
                            'message' => $email->message,
                        ],
                    ),
                ],
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws CannotCreateRequestException
     */
    private function postOfficeDataToJson(int $id, Address $address): string
    {
        try {
            return json_encode(
                [
                    self::SEND_POST_OFFICE => array_filter(
                        [
                            'invoice_id' => $id,
                            'delivery_name' => $address->name,
                            'delivery_address' => $address->address,
                            'delivery_city' => $address->city,
                            'delivery_country_id' => $address->country_id,
                            'delivery_state' => $address->state,
                            'delivery_zip' => $address->zip,
                        ],
                    ),
                ],
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function getListQueryString(InvoicesQuery $query): string
    {
        return $this->query_params_convertor->convert([
            'listinfo' => 1,
            'page' => $query->page,
            'per_page' => $query->items_per_page,
            'sort' => $query->sort->attribute,
            'direction' => $query->sort->direction->value,
            'amount_from' => $query->amount_from,
            'amount_to' => $query->amount_to,
            'client_id' => $query->client_id,
            'delivery' => $query->delivery?->period->value,
            'delivery_since' => $query->delivery?->from?->format('c'),
            'delivery_to' => $query->delivery?->to?->format('c'),
            'delivery_type' => $query->delivery_types !== []
                ? implode(
                    InvoicesQuery::VALUES_SEPARATOR,
                    array_map(static fn (DeliveryType $type) => $type->value, $query->delivery_types),
                )
                : null,
            'ignore' => $query->ignored_invoices !== []
                ? implode(InvoicesQuery::VALUES_SEPARATOR, $query->ignored_invoices)
                : null,
            'invoice_no_formatted' => $query->formatted_number,
            'order_no' => $query->order_number,
            'paid' => $query->paid?->period->value,
            'paid_since' => $query->paid?->from?->format('c'),
            'paid_to' => $query->paid?->to?->format('c'),
            'payment_type' => $query->payment_types !== []
                ? implode(
                    InvoicesQuery::VALUES_SEPARATOR,
                    array_map(static fn (PaymentType $type) => $type->value, $query->payment_types),
                )
                : null,
            'search' => $query->full_text !== null
                ? base64_encode($query->full_text)
                : null,
            'status' => $query->status?->value,
            'variable' => $query->variable_symbol,
            'tag' => $query->tag,
            'created' => $query->created?->period->value,
            'created_since' => $query->created?->from?->format('c'),
            'created_to' => $query->created?->to?->format('c'),
            'modified' => $query->modified?->period->value,
            'modified_since' => $query->modified?->from?->format('c'),
            'modified_to' => $query->modified?->to?->format('c'),
        ]);
    }
}
