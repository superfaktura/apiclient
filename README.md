# SuperFaktúra PHP API Client 2

## Overview
The SuperFaktúra API enables the integration of external applications, allowing the remote creation of documents and retrieval of data about them. It also enables the sending of invoices via email or regular mail.

## Requirements
To use this package, you will need at least PHP 8.2.

## Quickstart

In order to make it easier to integrate your application with SuperFaktúra, we've prepared a simple API client for you, which allows you to issue your invoices remotely with minimal effort.

However, if you wish to create your own API client, we also provide [general documentation](https://github.com/superfaktura/docs).

If you want to try out API integration, we have a testing environment (sandbox) for [Slovak](https://sandbox.superfaktura.sk) and [Czech](https://sandbox.superfaktura.cz) market available for you.

## Installation

Simply run this [composer](https://getcomposer.org/) command:
```sh
$ composer require superfaktura/apiclient
```

## Examples

### Starting with API
To use this API client, you need to first:
### 1. Create an account in SuperFaktúra
   - depending on your market of choice create an account on either of these:
     - https://moja.superfaktura.sk/registracia
     - https://moje.superfaktura.cz/registrace
     - https://meine.superfaktura.at/registrierung
     - https://sandbox.superfaktura.sk/registracia
     - https://sandbox.superfaktura.cz/registrace
   - after log in try to create an invoice
### 2. Set up an Api Client instance with given credentials
   - can be found on either of these:
     - https://moja.superfaktura.sk/api_access
     - https://moje.superfaktura.cz/api_access
     - https://meine.superfaktura.at/api_access
     - https://sandbox.superfaktura.sk/api_access
     - https://sandbox.superfaktura.cz/api_access

```php

<?php
include_once __DIR__ . '/vendor/autoload.php';

$api = new \SuperFaktura\ApiClient\ApiClient(
    new \SuperFaktura\ApiClient\Authorization\SimpleProvider(
        'test@example.com',
        'YOUR_APIKEY',
        'Example s.r.o.',
        1,
    ),
    \SuperFaktura\ApiClient\MarketUri::SLOVAK,
);
```
As an alternative, you can authorize through [.env configuration](#authorization-with-env-file).

### 3. Usage

```php
$response = $api->invoices->create(
    invoice: [
        'name' => 'Test API',
        'variable' => '12345',
    ],
    items: [
        [
            'name' => 'item name',
            'description' => 'item description',
            'tax' => 20,
            'unit_price' => 10,
        ],
    ],
    client: [
        'name' => 'Client name',
        'ico' => '44981082',
        'comment' => 'Client comment',
        'update_addressbook' => 1,
    ],
);
var_dump($response->data);
```

### Error handling

```php
try {
    $response = $api->invoices->create(
        invoice: [
            'name' => 'Test API',
            'variable' => '12345',
        ],
        items: [
            [
                'name' => 'item name',
                'description' => 'item description',
                'tax' => 20,
                'unit_price' => 10,
            ],
        ],
        client: [
            'name' => 'Client name',
            'ico' => '44981082',
            'comment' => 'Client comment',
            'update_addressbook' => 1,
        ],
    );
    
    var_dump($response->data);
} catch (\SuperFaktura\ApiClient\Contract\Invoice\CannotCreateInvoiceException $exception) {
    echo 'Cannot create invoice: ' . $exception->getMessage() . PHP_EOL;
} catch (\SuperFaktura\ApiClient\Request\CannotCreateRequestException $exception) {
    echo 'Cannot create request: ' . $exception->getMessage() . PHP_EOL;
}
```

### Use cases samples

#### Bank accounts
##### 1. `BankAccounts::getAll`
Returns list of bank accounts.
```php
$response = $api->bank_accounts->getAll();
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/bank-account.md#get-list-of-bank-accounts).

##### 2. `BankAccounts::create`
Creates a bank account and returns its data
```php
$response = $api->bank_accounts->create([
    'bank_name' => 'NovaBanka',
    'iban' => 'SK000011112222333344',
    'swift' => 'suzuki',
    'default' => 1,
    'show' => 1,
    'show_account' => 1,
]);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/bank-account.md#add-bank-account).

##### 3. `BankAccounts::update`
Updates an existing bank account and returns its data.
```php
$response = $api->bank_accounts->update(
    id: 1,
    bank_account: [
        'bank_name' => 'StaroNovaBanka',
        'swift' => '777777',
    ],
);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/bank-account.md#update-bank-account).

##### 4. `BankAccounts::delete`
Deletes an existing bank account.
```php
$api->bank_accounts->delete(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/bank-account.md#delete-bank-account).

#### Cash registers
Returns list of cash registers.
##### 1. `CashRegisters::getAll`
```php
$response = $api->cash_registers->getAll();
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/cash-register.md#get-cash-registers-details).

##### 2. `CashRegisters::getById`
Returns details of cash register.
```php
$response = $api->cash_registers->getById(1);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/cash-register.md#get-cash-register-by-id).

#### Cash register items
##### 1. `CashRegister\Items::create`
Creates a new cash register item and returns its data.
```php
$response = $api->cash_registers->items->create(
    cash_register_id: 1,
    data: [
        'amount' => 9.99,
        'currency' => \SuperFaktura\ApiClient\UseCase\Money\Currency::EURO,
    ],
);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/cash-register-item.md#add-cash-register-item).

#### Clients
##### 1. `Clients::getAll`
Returns list of clients filtered by given query object.
```php
$response = $api->clients->getAll(new \SuperFaktura\ApiClient\UseCase\Client\ClientsQuery(
    full_text: 'Test',
    created: new \SuperFaktura\ApiClient\Filter\TimePeriod(
        period: \SuperFaktura\ApiClient\Filter\TimePeriodEnum::THIS_YEAR
    ),
));
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/clients.md#get-client-list).

##### 2. `Clients::getById`
Returns details of client.
```php
$response = $api->clients->getById(4);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/clients.md#view-client-detail).

##### 3. `Clients::create`
Creates a client record and returns its data.
```php
$response = $api->clients->create([
    'name' => 'SuperFaktúra s.r.o',
]);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/clients.md#create-client).

##### 4. `Clients::update`
Updates an existing client record and returns its data.
```php
$response = $api->clients->update(
    id: 1,
    data: [
        'name' => 'SuperFaktúra s.r.o',
    ]
);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/clients.md#edit-client).

##### 5. `Clients::delete`
Deletes an existing client.
```php
$api->clients->delete(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/clients.md#delete-client).

#### Client's contact people
##### 1. `Client\Contact\Contacts::getAllByClientId`
Returns list of contact people for a given client id.
```php
$response = $api->clients->contacts->getAllByClientId(1);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/contact-persons.md#get-contact-persons).

##### 2. `Client\Contact\Contacts::create`
Creates a contact person and returns its data.
```php
$response = $api->clients->contacts->create(
    client_id: 1,
    contact: [
        'name' => 'Joe Doe',
        'email' => 'joe@superfaktura.sk',
    ],
);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/contact-persons.md#add-contact-person-to-client).

##### 3. `Client\Contact\Contacts::delete`
Deletes an existing contact person.
```php
$api->clients->contacts->delete(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/contact-persons.md#delete-contact-person).

#### Countries

##### 1. `Countries::getAll`
Returns list of available countries.
```php
$response = $api->countries->getAll();
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/value-lists.md#country-list).

#### Expenses

##### 1. `Expenses::getAll`
Returns list of expenses filtered by given query object.
```php
$response = $api->expenses->getAll(
    new \SuperFaktura\ApiClient\UseCase\Expense\ExpensesQuery(
        full_text: 'SuperFaktura',
        type: \SuperFaktura\ApiClient\Contract\Expense\ExpenseType::INVOICE,
        created: new \SuperFaktura\ApiClient\Filter\TimePeriod(
            period: \SuperFaktura\ApiClient\Filter\TimePeriodEnum::THIS_YEAR
        ),
    )
);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/expenses.md#get-list-of-expenses).

##### 2. `Expenses::getById`
Returns details of expense.
```php
$response = $api->expenses->getById(1);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/expenses.md#expense-detail).


##### 3. `Expenses::getAllCategories`
Returns available expense categories.
```php
$response = $api->expenses->getAllCategories();
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/value-lists.md#expense-categories).

##### 4. `Expenses::create`
Creates an expense and returns its data.
```php
$response = $api->expenses->create(
    expense: [
        'name' => 'Foo bar',
        'currency' => \SuperFaktura\ApiClient\UseCase\Money\Currency::EURO,
    ],
    items: [
        [
            'description' => 'description of item 1',
            'name' => 'item 1',
            'tax' => 20,
            'unit_price' => 10,
        ]
    ],
    client: [
        'ico' => '46655034',
    ],
    extra: ['vat_transfer' => 1],
    tags: [1, 2],
);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/expenses.md#add-expense).

##### 5. `Expenses::update`
Updates an existing expense and returns its data.
```php
$response = $api->expenses->update(
    id: 1,
    expense: [
        'name' => 'Foo',
    ],
);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/expenses.md#edit-expense).

##### 6. `Expenses::delete`
Deletes an existing expense.
```php
$api->expenses->delete(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/expenses.md#delete-expense).

#### Expense payments
##### 1. `Expense\Payment\Payments:create`
Creates a new expense payment and returns its data.
```php
$response = $api->expenses->payments->create(
    id: 95,
    payment: new \SuperFaktura\ApiClient\UseCase\Expense\Payment\Payment(
        amount: 10,
        currency: \SuperFaktura\ApiClient\UseCase\Money\Currency::EURO,
        payment_type: \SuperFaktura\ApiClient\Contract\PaymentType::CASH,
    ),
);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/expenses.md#add-expense-payment).

##### 2. `Expense\Payment\Payments:delete`
Deletes an existing expense payment.
```php
$api->expenses->payments->delete(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/expenses.md#delete-expense-payment).

#### Invoices

##### 1. `Invoices::getById`
Returns details of invoice.
```php
$response = $api->invoices->getById(1);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#get-invoice-detail).
##### 2. `Invoices::getByIds`
Returns details of multiple invoices.
```php
$response = $api->invoices->getByIds([1,2,3]);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#get-invoices-details).
##### 3. `Invoices::getAll`
Returns list of invoices filtered by given query object.
```php
$query = new \SuperFaktura\ApiClient\UseCase\Invoice\InvoicesQuery(
    sort: new \SuperFaktura\ApiClient\Filter\Sort('created', \SuperFaktura\ApiClient\Filter\SortDirection::DESC),
    client_id: 172,
    created: new \SuperFaktura\ApiClient\Filter\TimePeriod(
        \SuperFaktura\ApiClient\Filter\TimePeriodEnum::FROM_TO,
            new DateTimeImmutable('2023-11-01'),
            new DateTimeImmutable('2023-11-08'),
    ),
);
$response = $api->invoices->getAll($query);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#get-list-of-invoices).
##### 4. `Invoices::downloadPdf`
Returns PDF export of invoice.
```php
$response = $api->invoices->downloadPdf(372, \SuperFaktura\ApiClient\Contract\Language::SLOVAK);
file_put_contents(__DIR__ . '/invoice.pdf', $response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#get-invoice-pdf).

##### 5. `Invoices::create`
Creates an invoice and returns its data.
```php
$response = $api->invoices->create(
    invoice: [
        'name' => 'Test API',
        'variable' => '12345',
    ],
    items: [
        [
            'name' => 'item name',
            'description' => 'item description',
            'tax' => 20,
            'unit_price' => 10,
        ],
    ],
    client: [
        'name' => 'Client name',
        'ico' => '44981082',
        'comment' => 'Client comment',
        'update_addressbook' => 1,
    ],
    settings: [
        'language' => \SuperFaktura\ApiClient\Contract\Language::SLOVAK,
        'signature' => true,
    ],
    extra: [
        'pickup_point_id' => 23,
    ],
    my_data: [
        'address' => 'Fiktivna 1',
        'business_register' => "-",
        'city' => 'Prague',
        'company_name' => 'MyData Inc.',
        'country_id' => 191,
        'dic' => 'SK99999999',
        'ic_dph' => 'ABCDE',
        'zip' => '999 88',
    ],
    tags: [4],
);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#add-invoice).
##### 6. `Invoices::update`
Updates an existing invoice and returns its data.
```php
$response = $api->invoices->update(
    id: 1,
    invoice: [
        'name' => 'Test API updated',
        'variable' => '12345',
    ],
    items: [
        [
            'name' => 'item name',
            'description' => 'item description',
            'tax' => 20,
            'unit_price' => 10,
        ],
    ],
    client: [
        'name' => 'Client name',
        'ico' => '44981082',
        'comment' => 'Client comment',
        'update_addressbook' => 1,
    ],
    settings: [
        'language' => \SuperFaktura\ApiClient\Contract\Language::SLOVAK,
        'signature' => true,
    ],
    extra: [
        'pickup_point_id' => 23,
    ],
    my_data: [
        'address' => 'Fiktivna 1',
        'business_register' => "-",
        'city' => 'Prague',
        'company_name' => 'MyData Inc.',
        'country_id' => 191,
        'dic' => 'SK99999999',
        'ic_dph' => 'ABCDE',
        'zip' => '999 88',
    ],
    tags: [4],
);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#edit-invoice).
##### 7. `Invoices::delete`
Deletes an existing invoice.
```php
$api->invoices->delete(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#delete-invoice).
##### 8. `Invoices::changeLanguage`
Changes language on an existing invoice.
```php
$api->invoices->changeLanguage(1, \SuperFaktura\ApiClient\Contract\Language::CZECH);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#set-invoice-language).

##### 9. `Invoices::markAsSent`
Toggles invoice mark as sent status.
```php
$api->invoices->markAsSent(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#mark-invoice-as-sent).

##### 10. `Invoices::markAsSentViaEmail`
Marks invoice as sent via email.
```php
$api->invoices->markAsSentViaEmail(1, 'test@example.com', 'Subject', 'Message');
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#mark-invoice-as-sent-via-email).
##### 11. `Invoices::sendViaEmail`
Sends invoice via email.
```php
$email = new \SuperFaktura\ApiClient\UseCase\Invoice\Email(
    email: 'test@example.com',
    pdf_language: \SuperFaktura\ApiClient\Contract\Language::CZECH,
    bcc: ['test2@example.com'],
    cc: ['test3@example.com'],
    subject: 'Subject of email',
    message: 'Message',
);
$api->invoices->sendViaEmail(1, $email);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#send-invoice-via-mail).

##### 12. `Invoices::sendViaPostOffice`
```php
$address = new \SuperFaktura\ApiClient\UseCase\Invoice\Address(
    name: 'John Doe',
    address: 'Vymyslena 1',
    city: 'Bratislava',
    country_id: 191,
    state: 'Slovakia',
    zip: '99999',
);
$api->invoices->sendViaPostOffice(372, $address);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#send-invoice-via-post).

#### Invoice\Items

##### 1. `Invoice\Item::delete`
Deletes invoice items with given ids.
```php
$api->invoices->items->delete(1, [1,2,3]);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#delete-invoice-item).

#### Invoice\Payments

##### 1. `Invoice\Payment::markAsUnPayable`
Marks invoice as "will not be paid".
```php
$api->invoices->payments->markAsUnPayable(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#set-invoice-as-will-not-be-paid).

#####  2. `Invoice\Payment::create`
Adds payment to invoice.
```php
$payment = new \SuperFaktura\ApiClient\UseCase\Invoice\Payment\Payment(
    amount: 6.00,
    currency: \SuperFaktura\ApiClient\UseCase\Money\Currency::EURO,
    payment_type: \SuperFaktura\ApiClient\Contract\PaymentType::CARD,
    document_number: '123',
    payment_date: new DateTimeImmutable('now'),
);
$api->invoices->payments->create(1, $payment);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#pay-invoice).

##### 3. `Invoice\Payment::delete`
Deletes payment from invoice.
```php
$api->invoices->payments->delete(123);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#delete-invoice-payment).
#### Related documents

##### 1. `RelatedDocuments::link`
Adds link between two documents.
```php
$relation = new \SuperFaktura\ApiClient\UseCase\RelatedDocument\Relation(
    parent_id: 1,
    parent_type: \SuperFaktura\ApiClient\Contract\RelatedDocument\DocumentType::INVOICE,
    child_id: 1,
    child_type: \SuperFaktura\ApiClient\Contract\RelatedDocument\DocumentType::EXPENSE,
);
$response = $api->related_documents->link($relation);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#add-related-item).

##### 2. `RelatedDocuments::unlink`
Removes link between two documents.
```php
$api->related_documents->unlink(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/invoice.md#delete-related-item).

#### Exports

##### 1. `Exports::getStatus`
Returns progress of the export.

```php
$response = $api->exports->getStatus(1);
var_dump($response->data);
```
##### 2. `Exports::download`
Download completed export.

```php
$response = $api->exports->download(1);
file_put_contents(__DIR__ . '/export.zip', $response->data);
```

##### 3. `Exports::exportInvoices`
Export multiple invoices with possible configuration.

```php
$response = $api->exports->exportInvoices(
    [1,2,3],
    \SuperFaktura\ApiClient\Contract\Export\Format::PDF,
    new \SuperFaktura\ApiClient\UseCase\Export\PdfExportOptions(
        language: \SuperFaktura\ApiClient\Contract\Language::SLOVAK,
        hide_signature: true,
    ),
);
var_dump($response->data);
```

#### Stock items

##### 1. `Stock\Items::create`
Creates a new stock item.
```php
$response = $api->stock_items->create([
    'name' => 'Cake - red velvet',
    'sku'  => 'CK-RV',
    'unit_price' => 30.99,
    'purchase_unit_price' => 39.99,
]);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/stock.md#add-stock-item).

##### 2. `Stock\Items::getById`
Returns details of stock item.
```php
$response = $api->stock_items->getById(1);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/stock.md#view-stock-item-details).

##### 3. `Stock\Items::getAll`
Returns list of stock items filtered by given query object.
```php
$query = new \SuperFaktura\ApiClient\Contract\Stock\ItemsQuery(
    price_from: 30.99,
    price_to: 39.99,
);
$response = $api->stock_items->getAll($query);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/stock.md#get-list-of-stock-items).

##### 4. `Stock\Items::update`
Updates an existing stock item.
```php
$response = $api->stock_items->update(1, [
    'name' => 'Updated name',
]);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/stock.md#edit-stock-item).

##### 5. `Stock\Items::delete`
Deletes an existing stock item.
```php
$api->stock_items->delete(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/stock.md#delete-stock-item).
#### Stock movements

##### 1. `Stock\Movements::create`
Creates multiple stock logs for an existing stock item id.
```php
$response = $api->stock_items->movements->create(1, [
    ['quantity' => 5, 'note' => 'Restocking the supplies'],
    ['quantity' => -5, 'note' => 'I must eat something for dinner'],
]);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/stock.md#add-stock-movement).

##### 2. `Stock\Movements::createWithSku`
Creates multiple stock logs for an existing stock item SKU.
```php
$response = $api->stock_items->movements->createWithSku('CK-RV', [
    ['quantity' => 5, 'note' => 'Restocking the supplies'],
    ['quantity' => -5, 'note' => 'I must eat something for dinner'],
]);
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/stock.md#add-stock-movement).

#### Tag

##### 1. `Tags::getAll`
Returns list of tags.
```php
$response = $api->tags->getAll();
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/tags.md#get-list-of-tags).

##### 2. `Tags::create`
Creates a tag and returns its data
```php
$response = $api->tags->create('my-tag');
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/tags.md#add-tag).

##### 3. `Tags::update`
Updates an existing tag and returns its data.
```php
$response = $api->tags->update(1, 'my-updated-tag');
var_dump($response->data);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/tags.md#edit-tag).

##### 4. `Tags::delete`
Deletes an existing tag.
```php
$api->tags->delete(1);
```
For more information check [API documentation](https://github.com/superfaktura/docs/blob/master/tags.md#delete-tag).

### Enumerations

- [MarketUri](./src/MarketUri.php)
- [Languages](./src/Contract/Language.php)
- [Currencies](./src/UseCase/Money/Currency.php)
- [Delivery types](./src/Contract/Invoice/DeliveryType.php)
- [Expense types](./src/Contract/Expense/ExpenseType.php)
- [Expense statuses](./src/Contract/Expense/ExpenseStatus.php)
- [Invoice statuses](./src/Contract/Invoice/InvoiceStatus.php)
- [Invoice types](./src/Contract/Invoice/InvoiceType.php)
- [Payment types](./src/Contract/PaymentType.php)
- [Time filter constants](./src/Filter/TimePeriodEnum.php)

### Authorization with .env file
#### 1. Create .env file:
```dotenv
SF_APICLIENT_EMAIL=test@example.com
SF_APICLIENT_KEY=YOUR_APIKEY
SF_APICLIENT_APP_TITLE='Example s.r.o.'
SF_APICLIENT_COMPANY_ID=1
```
And then use in your code:
```php
$api = new \SuperFaktura\ApiClient\ApiClient(
    new \SuperFaktura\ApiClient\Authorization\EnvFileProvider(__DIR__ . '/.env'),
    'https://moja.superfaktura.sk',
);
```
