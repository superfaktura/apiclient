<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Expense\Payment;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Contract\PaymentType;
use SuperFaktura\ApiClient\UseCase\Money\Currency;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Expense\Payment\Payment;
use SuperFaktura\ApiClient\UseCase\Expense\Payment\Payments;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Test\UseCase\Expense\ExpensesTestCase;
use SuperFaktura\ApiClient\Contract\Expense\Payment\CannotPayExpenseException;
use SuperFaktura\ApiClient\Contract\Expense\Payment\CannotDeleteExpensePaymentException;

#[CoversClass(Payments::class)]
#[CoversClass(Payment::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
final class PaymentsTest extends ExpensesTestCase
{
    public static function expensePayProvider(): \Generator
    {
        yield 'with minimal data' => [
            'request_body' => 'data=' . json_encode([
                Payments::EXPENSE_PAYMENT => [
                    'expense_id' => 1,
                    'amount' => 12,
                ],
            ], JSON_THROW_ON_ERROR),
            'id' => 1,
            'amount' => 12,
        ];

        yield 'with all options' => [
            'request_body' => 'data=' . json_encode([
                Payments::EXPENSE_PAYMENT => [
                    'expense_id' => 1,
                    'amount' => 9.99,
                    'currency' => Currency::CZECH_REPUBLIC_KORUNA->value,
                    'payment_type' => PaymentType::CASH->value,
                    'created' => (new \DateTimeImmutable('2023-12-24'))->format('Y-m-d'),
                ],
            ], JSON_THROW_ON_ERROR),
            'id' => 1,
            'amount' => 9.99,
            'payment_type' => PaymentType::CASH,
            'currency' => Currency::CZECH_REPUBLIC_KORUNA,
            'payment_date' => new \DateTimeImmutable('2023-12-24'),
        ];
    }

    #[DataProvider('expensePayProvider')]
    public function testPay(
        string $request_body,
        int $id,
        float $amount,
        ?PaymentType $payment_type = null,
        ?Currency $currency = null,
        ?\DateTimeImmutable $payment_date = null,
    ): void {
        $this
            ->getPayments($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->create(
                id: $id,
                payment: new Payment(
                    amount: $amount,
                    currency: $currency,
                    payment_type: $payment_type,
                    payment_date: $payment_date,
                ),
            );

        $request = $this->getLastRequest();

        $this->request()
            ->post('/expense_payments/add')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
        self::assertSame($request_body, (string) $request?->getBody());
    }

    public function testPayErrorResponse(): void
    {
        $this->expectException(CannotPayExpenseException::class);
        $this->expectExceptionMessage('Expense error');

        $fixture = __DIR__ . '/../fixtures/generic-error.json';

        $this
            ->getPayments($this->getHttpClientReturning($fixture))
            ->create(1, new Payment(amount: 10));
    }

    public function testPayRequestFailed(): void
    {
        $this->expectException(CannotPayExpenseException::class);

        $this
            ->getPayments($this->getHttpClientWithMockRequestException())
            ->create(1, new Payment(amount: 10));
    }

    public function testPayInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this
            ->getPayments($this->getHttpClientWithMockResponse())
            ->create(1, new Payment(amount: NAN));
    }

    #[DataProvider('expenseIdProvider')]
    public function testDelete(int $id): void
    {
        $this
            ->getPayments($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->delete($id);

        $this->request()
            ->delete('/expense_payments/delete/' . $id)
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
    }

    public function testDeleteFailed(): void
    {
        $this->expectException(CannotDeleteExpensePaymentException::class);
        $this->expectExceptionMessage('Expense error');

        $fixture = __DIR__ . '/../fixtures/generic-error.json';

        $this
            ->getPayments($this->getHttpClientReturning($fixture))
            ->delete(1);
    }

    public function testDeleteRequestFailed(): void
    {
        $this->expectException(CannotDeleteExpensePaymentException::class);

        $this
            ->getPayments($this->getHttpClientWithMockRequestException())
            ->delete(1);
    }

    private function getPayments(ClientInterface $client): Payments
    {
        return new Payments(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
