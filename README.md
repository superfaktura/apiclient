# SuperFaktúra PHP-API klient

## Overview
 API SuperFaktúry umožňuje prepojenie externých aplikácií so SuperFaktúrou a 
 dovoľuje tak vzdialene vytvárať doklady a získavať údaje o nich. 
 Umožňuje tiež odosielať faktúry emailom alebo poštou.
 
## Quickstart
Aby ste sa nemusli trápiť s priamymi volaniami API funkcií a spôsobom prenosu dát, 
pripravili sme pre Vás jednoduchého API klienta, vďaka ktorému môžete Vaše faktúry vystavovať nadiaľku s 
minimálnym úsilím.

Ak si však chcete vytvoriť vlastného API klienta, máme aj [všeobecnú dokumentáciu](https://github.com/superfaktura/docs).

Keď chcete API integráciu vyskúšať, máme pre vás [testovacie prostredie - sandbox](https://sandbox.superfaktura.sk).
 
## Inštalácia PHP-API klienta

### 1. pomocou [composer-u](https://getcomposer.org/) (doporučený spôsob)

```sh
$ composer require superfaktura/apiclient
```

### 2. pomocou git-u (vyžaduje nainštalovaný systém [git](https://git-scm.com/))

```sh
$ mkdir /var/www/myproject/libs
$ git clone https://github.com/superfaktura/apiclient.git
```
  
### 3. stiahnutie ZIP balíčka
Stiahnite si SuperFaktúra PHP-API kliknutím na tlačidlo ["Stiahnuť ZIP"](https://github.com/superfaktura/apiclient/archive/master.zip), ktoré sa nachádza na github stránke nášho API.

## Ukážky kódu
  Aby sme vám uľahčili prácu pri implementácii nášho API, vytvorili sme ukážky kódu 
  ([sample.php](https://github.com/superfaktura/apiclient/blob/master/examples/sample.php) a [sample2.php](https://github.com/superfaktura/apiclient/blob/master/examples/sample2.php)),
  ktoré demonštrujú jeho funkcionalitu a dpĺňajú našu dokumentáciu o fungujúce príklady.

## Začíname používať SuperFaktúra PHP-API
  Na to, aby ste mohli začať API na plno využívať, je potrebné:
### 1. Zaregistrovať sa v SuperFaktúre
  * Na stránke https://moja.superfaktura.sk/registracia vykonajte registráciu. Automaticky získate 30 dní zadarmo.
  * Po prihlásení vystavte skúšobnú faktúru cez GUI SuperFaktúry
  
### 2. Urobiť základné nastavenia v kóde

```php
require_once('SFAPIclient/SFAPIclient.php');  // len v prípade, že nepoužívate Composer

$sf_api = new SFAPIClient(
    $login, // prihlasovací email do SuperFaktúry
    $token  // API token, ktorý nájdete v SuperFaktúre po prihlásení do svojho účtu "[Nástroje > API prístup](https://moja.superfaktura.sk/api_access)"
);
```
  
### 3. Používanie PHP-API volaní
  Nižšie je uvedený zoznam všetkých možných volaní, ktoré obsahuje najnovšia verzia nášho API.
  *Všetky PHP funkcie nášho API sú verejné členské funkcie triedy SFAPIclient*.
  Príklad vystavenia jednoduchej faktúry (pokračovanie predch. príkladu)
  
  
 #### Vystavenie faktúry 
```php
// set client for new invoice
$sf_api->setClient(array(
    'name' => 'MyClient',
    'address' => 'MyClient address 1',
    'zip' => 12345,
    'city' => 'MyClientCity'
));

// set invoice attributes
$sf_api->setInvoice(array(
    'name' => 'MyInvoice'
));

// add new invoice item
$sf_api->addItem(array(
    'name' => 'MyInvoiceItem',
    'description' => 'Inv. item no. 1',
    'unit_price' => 10,
    'tax' => 20
));

// save invoice in SuperFaktura
$json_response = $sf_api->save();

// TODO: handle exceptions
```
#### Ako vybrať doklady, kde nastala úhrada alebo iná zmena ?
Cez API je možné získať zoznam faktúr, kde nastala zmena (akákoľkvek zmena, čiže editácia, úhrada, odoslanie, ...) za zvolené obdobie.

Príklad filtrovania faktúr zmenených za poslednú hodinu 
```php
require_once('SFAPIclient/SFAPIclient.php');  // inc. SuperFaktúra PHP-API
$login_email = 'login@example.com';  // moja.superfaktura.sk login email
$api_token = 'abcd1234';  // token from my account
$sf_api = new SFAPIclient($login_email, $api_token);  // create SF PHP-API object
$json_response = $sf_api->invoices(array(
	'modified' => 11, // posledná hodina
	'type' => 'regular'
));
  ```
    
Pre filtrovanie pomocou modified odporúčame využiť jednu z nižšie uvedených možností:
```php
Array
(
    [1]  => Dnes
    [2]  => Včera
    [3]  => od – do (v prípade hodnoty od – do je potrebné zadať aj parametre modified_since modified_to)
    [11] => posledná hodina (ak sa dopytujem o 8:36, hľadá doklady zmenené za obdobie 7:36 – 8:36)
    [12] => aktuálna hodina (ak sa dopytujem o 8:36, hľadá doklady zmenené za obdobie väčšie ako 7:59)
)
```

Informácia o tom, či bola faktúra uhradená sa nachádza vo vrátenom zozname dokladov Invoice->status, (1 = neuhradena, 2 = ciastocne, 3 = uhradena)

V prípade že potrebujete detailnejšie informácie k faktúram, stačí následne zavolať funkciu [getInvoiceDetails](#42-getinvoicedetailsids), ktorá vráti detaily viacerých faktúr súčastne.

## Zoznam volaní (verejných členských funkcií vrátane konštruktora triedy SFAPIclient)
  * *__construct($email, $apikey, $apptitle = '', $module = 'API', $company_id = '')*
  * *addItem($item = array())*
  * *addStockItem($item = array())*
  * *addStockMovement($item = array())*
  * *addTags($tag_ids = array())*
  * *clients($params = array(), $list_info = true)*
  * *delete($id)*
  * *deleteInvoiceItem($invoice_id, $id)*
  * *deleteExpense($id)*
  * *deleteStockItem($id)*
  * *edit()*
  * *expense()*
  * *expenses()*
  * *getCountries()*
  * *getSequences()*
  * *getPDF($invoice_id, $token, $language = 'slo')*
  * *getTags()*
  * *invoice($id)*
  * *invoices($params = array(), $list_info = true)*
  * *markAsSent($invoice_id, $email, $subject = '', $message = '')*
  * *payInvoice($invoice_id, $amount, $currency = 'EUR', $date = null, $payment_type = 'transfer')*
  * *payExpense($expense_id, $amount, $currency = 'EUR', $date = null, $payment_type = 'transfer')*
  * *save()*
  * *setClient($key, $value = '')*
  * *setExpense($key, $value = '')*
  * *setInvoice($key, $value = '')*
  * *sendInvoiceEmail($options = array())*
  * *sendInvoicePost($options = array())*
  * *stockItemEdit($item = array())*
  * *stockItems($params = array(), $list_info = true)*
  * *stockItem($id)*
  * *addContactPerson($data)*
  * *getLogos()*
  * *getExpenseCategories()*
  * *setInvoiceSettings($settings)*
  * *setInvoiceExtras($extras)*
  * *deleteInvoicePayment($payment_id)*
  * *deleteExpensePayment($payment_id)*
  * *cashRegister($cash_register_id)*
  * *sendSMS($data)*
  * *setMyData($key, $value = '')*
  * *getInvoiceDetails($ids = '')*
  * *getUserCompaniesData($getAllCompanies = false)*
  * *createRegularFromProforma($proforma_id)*
  * *setEstimateStatus($estimate_id, $status)*
  * *getBankAccounts()*
  * *addBankAccount(array $data)*
  * *updateBankAccount(int $id, array $data)*
  * *deleteBankAccount(int $id)*
  * *addTag(array $data)*
  * *updateTag(int $id, array $data)*
  * *deleteTag(int $id)*
  * *client(int $id)*
 
### 1. __construct
 Konštruktor. Nastaví email a API token pre autorizáciu.
##### Parametre
  * **$email** string povinný
  * **$token** string povinný
  * **$apptitle** string nepovinný, názov aplikácie
  * **$module** string nepovinný, názov modulu 
  * **$company_id** integer nepovinný, ID spoločnosti, s ktorou cez API pracujete (v prípade, že máte len jednu spoločnosti nemusíte uvádzať)

### 2. addItem
 Pridá položku na faktúru.
##### Parametre
 * **$item** pole povinné
 * **$item_type** string typ položky. Možné hodnoty Invoice(default) a Expense  
 

##### Formát fakturačnej položky
 ```php
array(
	'name'          	   => 'Názov položky',
	'description'   	   => 'Popis',
	'quantity'       	   => 1, //množstvo
	'unit'         	 	   => 'ks', //jednotka
	'unit_price'     	   => 40.83, //cena bez dph, resp. celková cena, ak nie ste platcami DPH
	'tax'           	   => 20, //sadzba DPH, ak nie ste platcom DPH, zadajte 0
	'stock_item_id'  	   => 123, //id skladovej polozky
	'sku'             	   => 'SKU123', //skladove oznacenie
	'discount'        	   => 50, //Zľava na položku v %
	'discount_description' => 'Popis zľavy',
	'load_data_from_stock' => true //Načíta nevyplnené údaje položky zo skladu
	'use_document_currency' => true //V prípade, že je zadané load_data_from_stock, tak prepočíta skladovú položku, do meny dokladu.
	'AccountingDetail' => array( // účtovnícke detaily položky, 
		'place' => 'Stredisko Bratislava', //nazov strediska
		'order' => 'názov', //nazov zákazky 
		'operation' => 'názov', //činnosť 
		'type'  => 'item', // typ polozky (item(tovar), service(služba))
		'analytics_account' => '311', //analytický účet 
		'synthetic_account' => '000', //syntetický účet
		'preconfidence' => '5ZV' // predkontácia
	)
)
 ```
### 3. addTags
 Pridá faktúre tagy podľa číselníka
##### Parametre
* **$tags_ids** pole povinné. Pole ID tagov.

Kvôli možnému konfliktu názvov tagov a ich ID-čiek (napr. `2019` - rok vs ID) nie je možné pridávať priamo názvy tagov.
Ak chcete uložiť nový tag, je potrebné ho predtým pridať `addTag`.


### 4. clients
Vráti zoznam klientov
 
([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/clients.md#get-client-list))
 
##### Parametre
* **$params** pole povinné. Parametre pre filtrovanie a stránkovanie.
* **$list_info** bool nepovinné. Určuje, či vrátené dáta budú obsahovať aj údaje o zozname (celkový počet položiek, počet strán...)

##### Možné parametre pre filtrovanie, číselníky hodnôt sa nachádzajú pod zoznamom parametrov
```php
array(
	'search' => '', //Hľadaný výraz v klientovi. Prehľadáva všetky polia.
)
```
##### Formát vrátených dát
```php
{
    "itemCount": 67,
    "pageCount": 7,
    "perPage": 10,
    "page": 1,
    "items": [{
        "Client": {...},
    },...]
}
```

### 5. delete
Zmaže faktúru.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#delete-invoice))

##### Parametre
* **$id** povinné. Získané z Invoice->id.

### 6. deleteInvoiceItem
Zmaže položku na faktúre.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#delete-invoice-item))

##### Parametre
* **$invoice_id** int povinné. Získané z Invoice->id.
* **$id** int povinné. Získané z InvoiceItem->id.

### 7. deleteExpense
Zmaže náklad.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/expenses.md#delete-expense))

##### Parametre
* **$id** int povinné. Získané z Expense->id.

### 8. deleteStockItem
Zmaže skladovú položku.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/stock.md#delete-stock-item))

##### Parametre
* **$id** int povinné. Získané z StockItem->id.

### 9. edit
Uloží nastavené dáta a aktualizuje faktúru.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#edit))

##### Parametre: žiadne
##### Návratová hodnota: objekt
##### Kódy chýb
* **1** Id dokladu má nesprávny formát
* **2** Neexistujúce id dokladu
* **3** Chyba pri editácii faktúry. Volanie treba opakovať.
* **6** Chyba pri validácii údajov. Povinné údaje chýbajú alebo nemajú správny formát.

### 10. expenses
Vráti zoznam nákladov.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/expenses.md#get-list-of-expenses))

##### Parametre
* **$params** pole povinné. Parametre pre filtrovanie a stránkovanie.
* **$list_info** bool nepovinné. Určuje, či vrátené dáta budú obsahovať aj údaje o zozname (celkový počet položiek, počet strán...)

### 11. expense
Vráti detaily nákladu.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/expenses.md#expense-detail))

##### Parametre
* **$expense_id** int povinné. Získané z Expense->id.

### 12. getCountries
Vráti číselník krajín.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/value-lists.md#country-list))

### 13. getSequences
Vráti číselník číselných radov podľa typov dokumentov.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/value-lists.md#sequences))

### 14. getPDF
Vráti PDF súbor s faktúrou.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#get-invoice-pdf))

##### Parametre
* **$invoice_id** int povinné. Získané z Invoice->id.
* **$token** string povinné. Získané z Invoice->token.
* **$language** string nepovinné. Jazyk požadovaného PDF. Možné hodnoty sú {slo, cze, eng, deu, rus, ukr, hun, pol, rom, hrv, slv, spa, ita}

### 15. getTags
Vráti číselník existujúcich tagov.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/tags.md#get-list-of-tags))

### 16. invoice
Vráti detaily faktúry. Ak potrebujete získať detaily viacerých faktúr použite funkciu [getInvoiceDetails](#42-getinvoicedetailsids)

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#get-invoice-detail))
 
##### Parametre
* **$invoice_id** int povinné. Získané z Invoice->id.

### 17. invoices
Vráti zoznam vystavených faktúr.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#get-list-of-invoices))

##### Parametre
* **$params** pole povinné. Parametre pre filtrovanie a stránkovanie.
* **$list_info** bool nepovinné. Určuje, či vrátené dáta budú obsahovať aj údaje o zozname (celkový počet položiek, počet strán...)

##### Možné parametre pre filtrovanie, číselníky hodnôt sa nachádzajú pod zoznamom parametrov  

```php
array(
	'page'          => 1, //Strana
	'per_page'      => 10, //Počet položiek na stranu
	'created'       => 0, //Dátum vystavenia.
	'modified'       => 0, //Dátum vykonania poslednej zmeny.
	'delivery'      => 0, //Dátum dodania.
	'type'          => 'regular', //Typ faktúry. Viacero typov je možné kombinovať pomocou "|" napr. "regular|cancel"
	'delivery_type' => 'mail', //Typ faktúry. Viacero typov je možné kombinovať pomocou "|" napr. "mail|personal"
	'payment_type'  => 'transfer', //Typ faktúry. Viacero typov je možné kombinovať pomocou "|" napr. "mail|personal"
	'status'        => 0, //Stav faktúry.
	'client_id'     => 1, //ID klienta. Zoznam klientov je možné získať metódou clients()
	'amount_from'   => 0, //Suma faktúry od
	'amount_to'     => 0, //Suma faktúry do
	'paid_since'    => 0, //Faktúra uhradená od
	'paid_to'       => 0, //Faktúra uhradená do
	'search'        => '', //Hľadaný výraz vo faktúre. Prehľadáva všetky polia.
	'ignore'        => '1|2|3', //ID faktúr, ktoré sa majú ignorovať.
	'order_no'        => '2016001', //číslo cenovej ponuky z ktorej bola FA vytvorená
)
```

##### Formát vrátených dát

```php
{
    "itemCount": 67,
    "pageCount": 7,
    "perPage": 10,
    "page": 1,
    "items": [{
        "Client": {...},
        "Invoice": {"id": "8358",...}
        "InvoicePayment": {},
        "InvoiceEmail": {},
        "PostStamp": {}
    },...]
}
```
##### Číselníky pre filtrovanie faktúr

Obdobie vystavenia a dodania faktúry
```php
Array
(
    [0] => Všetko
    [1] => Dnes
    [2] => Včera
    [4] => Tento mesiac
    [5] => Minulý mesiac
    [8] => Tento kvartál
    [7] => Minulý rok
    [6] => Tento rok
    [3] => od - do //v prípade hodnoty od - do je potrebné uviesť aj parametre created_since a created_to
)
```

Pre filtrovanie pomocou modified odporúčame využiť jednu z nižšie uvedených možností:
```php
Array
(
    [1]  => Dnes
    [2]  => Včera
    [3]  => od – do (v prípade hodnoty od – do je potrebné zadať aj parametre modified_since modified_to)
    [11] => posledná hodina (ak sa dopytujem o 8:36, hľadá doklady zmenené za obdobie 7:36 – 8:36)
    [12] => aktuálna hodina (ak sa dopytujem o 8:36, hľadá doklady zmenené za obdobie väčšie ako 7:59)
)
```

Typ faktúry
```php
Array
(
	[regular]  => Bežná
	[proforma] => Zálohová faktúra
	[estimate] => Cenová ponuka
	[cancel]   => Dobropis
	[order] => Prijatá objednávka
	[delivery] => Dodací list
)
```

Spôsob dodania
```php  
Array
(
	[mail]          => Poštou
	[courier]       => Kuriérom
	[personal]      => Osobný odber
	[haulage]       => Nákladná doprava
	[pickup_point]  => Odberné miesto
)
```
  
Stav faktúry
```php   
Array
(
	[0]  => Všetko
	[1]  => Čakajú na úhradu
	[2]  => Čiastočne uhradené
	[3]  => Uhradené
	[99] => Po splatnosti
)
  ```
   Príklad filtrovania faktúr zmenených za poslednú hodinu
  ```php
require_once('SFAPIclient/SFAPIclient.php');  // inc. SuperFaktúra PHP-API
$login_email = 'login@example.com';  // moja.superfaktura.sk login email
$api_token = 'abcd1234';  // token from my account
$sf_api = new SFAPIclient($login_email, $api_token);  // create SF PHP-API object
$json_response = $sf_api->invoices(array(
	'modified' => 11, // posledná hodina
	'type' => 'regular'
));
  ```
  
  Príklad filtrovania faktúr pomocou ID číselníka
  ```php
require_once('SFAPIclient/SFAPIclient.php');  // inc. SuperFaktúra PHP-API
$login_email = 'login@example.com';  // moja.superfaktura.sk login email
$api_token = 'abcd1234';  // token from my account
$sf_api = new SFAPIclient($login_email, $api_token);  // create SF PHP-API object
$json_response = $sf_api->invoices(array(
	'sequence_id' => ID, // integer
	'type' => 'regular'
));
  ```
  
### 18. markAsSent
Označí faktúru ako odoslanú emailom. Užitočné, pokiaľ vytvorené faktúry odosielate vlastným systémom, avšak chcete toto odoslanie evidovať aj v SuperFaktúre.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#mark-invoice-as-sent-via-email))

##### Parametre
* **$invoice_id** int povinné. Získané z Invoice->id
* **$email** string povinné. Emailová adresa, kam bola faktúra odoslaná.
* **$subject** string nepovinné. Predmet emailu.
* **$message** string nepovinné. Text emailu.
##### Návratová hodnota: objekt

### 19. payInvoice
Dodatočne pridá úhradu ku faktúre.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#pay-invoice))

##### Parametre
* **$invoice_id** int povinné. Získané z Invoice->id
* **$amount** float povinné. Uhradená suma.
* **$currency** string nepovinné. Mena úhrady, predvolené EUR.
* **$date** string nepovinné. Dátum úhrady, predvolený aktuálny dátum.
* **$payment_type** string nepovinné. Spôsob úhrady, predvolený typ transfer. Možné hodnoty {transfer, cash, paypal, trustpay, credit, debit, cod, accreditation, gopay, viamo}
* **$cash_register_id** int nepovinné. Určuje, ID pokladne.


### 20. payExpense
Dodatočne pridá úhradu k nákladu.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/expenses.md#add-expense-payment))

##### Parametre
* **$expense_id** int povinné. Získané z Expense->id
* **$amount** float povinné. Uhradená suma.
* **$currency** string nepovinné. Mena úhrady, predvolené EUR.
* **$date** string nepovinné. Dátum úhrady, predvolený aktuálny dátum.
* **$payment_type** string nepovinné. Spôsob úhrady, predvolený typ transfer. Možné hodnoty {transfer, cash, paypal, credit, debit, cod, accreditation, gopay, viamo}

### 21. save
Uloží nastavené dáta a vystaví faktúru.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#add-invoice))

##### Paramete: žiadne
##### Návratová hodnota: objekt
```php
{
    "error": 0,
    "error_message": "Invoice created",
    "data": {
        "Invoice": {
            "id": "947592",
            "user_id": "15968",
            "user_profile_id": "10156",
            "client_id": "518992",
            "parent_id": null,
            "proforma_id": null,
            "estimate_id": null,
            "sequence_id": "41008",
            "import_type": null,
            "import_id": null,
            "import_parent_id": null,
            "type": "regular",
            "tax_document": null,
            "name": "Faktura test",
            "lang": null,
            "client_data": "{\"Client\":{\"user_id\":\"15968\",\"user_profile_id\":\"10156\",\"country_id\":\"191\",\"name\":\"Klient test\",\"ico\":987654321,\"dic\":314,\"ic_dph\":null,\"bank_account\":null,\"email\":null,\"address\":null,\"city\":null,\"zip\":null,\"state\":null,\"country\":null,\"delivery_name\":null,\"delivery_address\":null,\"delivery_city\":null,\"delivery_zip\":null,\"delivery_state\":null,\"delivery_country\":null,\"delivery_country_id\":null,\"phone\":null,\"fax\":null,\"due_date\":null,\"default_variable\":null,\"discount\":null,\"currency\":\"EUR\",\"comment\":null,\"tags\":null,\"demo\":\"0\",\"update_addressbook\":true}}",
            "my_data": "{\"MyData\":{\"id\":\"10156\",\"user_id\":\"15968\",\"country_id\":\"191\",\"company_name\":\"Jan Doczy\",\"ico\":\"41432312\",\"dic\":\"CZ29413893fdsafdasfdsadfasfdjasf\",\"ic_dph\":\"012345678fjdksjfkldajklfdjasklfj\",\"business_register\":\"Okr. s\\u00fad BA 1, odd. SRO, vl. \\u010d 1234\\/B\",\"address\":\"\",\"city\":\"\",\"zip\":\"\",\"tax_payer\":\"1\",\"country\":\"Slovensko\",\"BankAccount\":[{\"id\":\"9998\",\"user_id\":\"15968\",\"user_profile_id\":\"10156\",\"default\":\"1\",\"country_id\":\"191\",\"bank_name\":\"UniCredit Bank Slovakia\",\"bank_code\":\"1111\",\"account\":\"1119090001\",\"iban\":\"SK8011110000001119090001\",\"swift\":\"UNCRSKBX\",\"created\":\"2014-10-06 13:25:30\",\"modified\":\"2015-08-21 08:09:07\"},{\"id\":\"10121\",\"user_id\":\"15968\",\"user_profile_id\":\"10156\",\"default\":null,\"country_id\":\"191\",\"bank_name\":\"\",\"bank_code\":\"\",\"account\":\"\",\"iban\":\"\",\"swift\":\"\",\"created\":\"2014-10-17 09:05:54\",\"modified\":\"2014-10-17 09:05:54\"},{\"id\":\"10434\",\"user_id\":\"15968\",\"user_profile_id\":\"10156\",\"default\":null,\"country_id\":\"191\",\"bank_name\":\"\",\"bank_code\":\"\",\"account\":\"\",\"iban\":\"\",\"swift\":\"\",\"created\":\"2014-11-18 14:29:46\",\"modified\":\"2014-11-18 14:29:46\"},{\"id\":\"10502\",\"user_id\":\"15968\",\"user_profile_id\":\"10156\",\"default\":null,\"country_id\":\"191\",\"bank_name\":\"\",\"bank_code\":\"\",\"account\":\"\",\"iban\":\"\",\"swift\":\"\",\"created\":\"2014-11-25 09:33:31\",\"modified\":\"2014-11-25 09:33:31\"},{\"id\":\"10530\",\"user_id\":\"15968\",\"user_profile_id\":\"10156\",\"default\":null,\"country_id\":\"191\",\"bank_name\":\"\",\"bank_code\":\"\",\"account\":\"\",\"iban\":\"\",\"swift\":\"\",\"created\":\"2014-11-27 09:20:29\",\"modified\":\"2014-11-27 09:20:29\"},{\"id\":\"10774\",\"user_id\":\"15968\",\"user_profile_id\":\"10156\",\"default\":null,\"country_id\":\"191\",\"bank_name\":\"\",\"bank_code\":\"\",\"account\":\"\",\"iban\":\"\",\"swift\":\"\",\"created\":\"2014-12-15 14:29:54\",\"modified\":\"2014-12-15 14:29:54\"}]}}",
            "items_data": "Polozka test , ",
            "invoice_no": "9",
            "order_no": null,
            "invoice_no_formatted": "201500009",
            "mask": "YYYYNNN",
            "variable": "201500009",
            "constant": "0308",
            "specific": null,
            "payment_type": null,
            "status": "1",
            "home_currency": "EUR",
            "invoice_currency": "EUR",
            "exchange_rate": "1.00000000000000",
            "amount": "15.00",
            "vat": "0.00",
            "discount": "0",
            "items_name": null,
            "issued_by": null,
            "issued_by_phone": "+421000000000",
            "issued_by_email": "email@example.com",
            "paid": "0.00",
            "amount_paid": "0.00",
            "deposit": "0.00",
            "header_comment": "",
            "comment": "Toto je poznamka.",
            "internal_comment": null,
            "created": "2015-09-02 00:00:00",
            "modified": "2015-09-02 08:53:35",
            "recurring": null,
            "paydate": null,
            "delivery": "2015-09-02 00:00:00",
            "delivery_type": null,
            "due": "2015-09-16",
            "demo": "0",
            "token": "bd1f145f",
            "tags": "",
            "rounding": "item",
            "vat_transfer": null,
            "special_vat_scheme": null,
            "issued_by_web": null,
            "summary_invoice": "0",
            "show_items_with_dph": false,
            "show_special_vat": false
        },
        "Client": {
            "id": "518992",
            "user_id": "15968",
            "user_profile_id": "10156",
            "country_id": "191",
            "name": "Klient test",
            "ico": "987654321",
            "dic": "314",
            "ic_dph": null,
            "bank_account": null,
            "email": null,
            "address": null,
            "city": null,
            "zip": null,
            "state": null,
            "country": null,
            "delivery_name": null,
            "delivery_address": null,
            "delivery_city": null,
            "delivery_zip": null,
            "delivery_state": null,
            "delivery_country": null,
            "delivery_country_id": null,
            "phone": null,
            "fax": null,
            "due_date": null,
            "default_variable": null,
            "discount": null,
            "currency": "EUR",
            "comment": null,
            "tags": null,
            "created": "2015-09-02 08:52:49",
            "modified": "2015-09-02 08:53:35",
            "demo": "0"
        },
        "InvoicePayment": [],
        "InvoiceEmail": [],
        "PostStamp": [],
        "Tag": [],
        "Logo": [
            {
                "id": "24688",
                "model": "User",
                "foreign_key": "10156",
                "dirname": "img",
                "basename": "10156_logo.jpg",
                "checksum": "5239f6774ced537687cf90c4561dd438",
                "group": "logo",
                "alternative": null,
                "created": "2014-11-27 09:20:29",
                "modified": "2014-11-27 09:20:29"
            }
        ],
        "Signature": {
            "id": "25789",
            "model": "User",
            "foreign_key": "10156",
            "dirname": "img",
            "basename": "10156_sig.png",
            "checksum": "d20820f675efdedaef5839a6da30bf97",
            "group": "signature",
            "alternative": null,
            "created": "2014-12-16 14:28:09",
            "modified": "2015-04-15 09:41:08"
        },
        "MyData": {
            "id": "10156",
            "user_id": "15968",
            "country_id": "191",
            "company_name": "Jan Doczy",
            "ico": "41432312",
            "dic": "CZ29413893fdsafdasfdsadfasfdjasf",
            "ic_dph": "012345678fjdksjfkldajklfdjasklfj",
            "business_register": "Okr. súd BA 1, odd. SRO, vl. č 1234/B",
            "address": "",
            "city": "",
            "zip": "",
            "tax_payer": "1",
            "country": {
                "id": "191",
                "name": "Slovensko",
                "iso": "sk",
                "eu": "1"
            },
            "BankAccount": [
                {
                    "id": "9998",
                    "user_id": "15968",
                    "user_profile_id": "10156",
                    "default": "1",
                    "country_id": "191",
                    "bank_name": "UniCredit Bank Slovakia",
                    "bank_code": "1111",
                    "account": "xxxxxxxx",
                    "iban": "SKxxxxxxxxxxxxxxxxxxxxxxx,
                    "swift": "UNCRSKBX",
                    "created": "2014-10-06 13:25:30",
                    "modified": "2015-08-21 08:09:07"
                }
            ]
        },
        "ClientData": {
            "user_id": "15968",
            "user_profile_id": "10156",
            "country_id": "191",
            "name": "Klient test",
            "ico": 987654321,
            "dic": 314,
            "ic_dph": null,
            "bank_account": null,
            "email": null,
            "address": null,
            "city": null,
            "zip": null,
            "state": null,
            "country": null,
            "delivery_name": null,
            "delivery_address": null,
            "delivery_city": null,
            "delivery_zip": null,
            "delivery_state": null,
            "delivery_country": null,
            "delivery_country_id": null,
            "phone": null,
            "fax": null,
            "due_date": null,
            "default_variable": null,
            "discount": null,
            "currency": "EUR",
            "comment": null,
            "tags": null,
            "demo": "0",
            "update_addressbook": true,
            "Country": {
                "id": "191",
                "name": "Slovensko",
                "iso": "sk",
                "eu": "1"
            }
        },
        "InvoiceItem": [
            {
                "id": "2384612",
                "invoice_id": "947592",
                "user_id": "15968",
                "user_profile_id": "10156",
                "stock_item_id": null,
                "name": "Polozka test",
                "description": "",
                "sku": null,
                "quantity": null,
                "unit": null,
                "unit_price": 15,
                "tax": "0",
                "discount": 0,
                "discount_description": null,
                "ordernum": "0",
                "unit_price_discount": 15,
                "item_price": 15,
                "item_price_no_discount": 15,
                "discount_no_vat": 0,
                "discount_with_vat": 0,
                "discount_with_vat_total": 0,
                "discount_no_vat_total": 0,
                "unit_price_vat": 15,
                "unit_price_vat_no_discount": 15,
                "item_price_vat": 15,
                "item_price_vat_no_discount": 15,
                "item_price_vat_check": 15
            }
        ],
        "Summary": {
            "vat_base_separate": [
                15
            ],
            "vat_base_total": 15,
            "vat_separate": [
                0
            ],
            "vat_total": 0,
            "invoice_total": 15,
            "discount": 0
        },
        "SummaryInvoice": {
            "vat_base_separate_positive": [
                15
            ],
            "vat_base_separate_negative": [
                0
            ],
            "vat_separate_positive": [
                0
            ],
            "vat_separate_negative": [
                0
            ]
        },
        "UnitCount": [],
        "Paypal": "https://www.paypal.com/",
        "PaymentLink": ""
    }
}
```
##### Kódy chýb
* **2** Dáta neboli odoslané metódou POST.
* **3** Nesprávne dáta. Odoslané dáta nemajú správny formát.
* **5** Validačný error. Povinné údaje chýbajú alebo sú nesprávne vyplnené.

### 22. setExpense
Nastaví hodnoty pre náklad.

##### Parametre
* **$key** mixed povinné. Môže byť string, alebo pole. Ak je string, nastaví sa konkrétna hodnota v $data['Expense'][$key]. Ak je pole, nastaví sa viacero hodnôt naraz.
* **$value** mixed nepovinné. Ak je $key string, hodnota $value sa nastaví v $data['Expense'][$key]. Ak je $key pole, $value sa ignoruje.

Príklad použitia:

```php 
$api->setExpense('name', 'nazov nakladu');
```
```php
$api->setExpense(array(
		'name' => 'nazov nakladu', // povinný udaj
		'amount' => 10, // suma bez DPH
		'vat' => 20, // DPH v percentách
		'variable' => '123456', // variabilný symbol
		'constant' => '0308', // konštantný symbol
		'attachment' => base64_encode(file_get_contents('/tmp/foo.pdf')),
));
```

Zoznam možných vlastností nákladu:
* **name** - názov nákladu (povinný údaj)
* **amount** - suma bez dane
* **vat** - DPH (percentá)
* **already_paid** - bola už faktúra uhradená? true/false
* **created** - dátum vystavenia
* **comment** - komentár
* **constant** - konštantný symbol
* **delivery** - dátum dodania
* **due** - dátum splatnosti
* **currency** - mena, v ktorej je faktúra vystavená. Možnosti: EUR, USD, GBP, HUF, CZK, PLN, CHF, RUB
* **payment_type** - Spôsob úhrady, číselník hodnôt
* **specific** - špecifický symbol
* **type** - typ faktúry. Možnosti: invoice - prijatá faktúra, bill - pokladničný blok, internal - interný doklad, contribution - odvody
* **variable** - variabilný symbol
*  **taxable_supply** - Dátum zdaniteľného plnenia
*  **document_number** - Číslo dokladu. Napríklad číslo došlej faktúry, číslo pokladničného bloku a podobne.
*  **expense_category_id** - ID príslušnej kategórie. Zoznam všetkých kategórií je možné získať pomocou funkcie getExpenseCategories()
* **atttachment** - súbor prílohy vo formáte base64. Maximálna veľkosť súboru sú 4MB. Povolené typy: `jpg`, `jpeg`, `png`, `tif`, `tiff`, `gif`, `pdf`, `tmp`, `xls`, `xlsx`, `ods`, `doc`, `docx`, `xml`, `csv`, `msg` 

### 23. setInvoice
Nastaví hodnoty pre faktúru
##### Parametre
* *$key* mixed povinné. Môže byť string, alebo pole. Ak je string, nastaví sa konkrétna hodnota v $data['Invoice'][$key]. Ak je pole, nastaví sa viacero hodnôt naraz.
* *$value* mixed nepovinné. Ak je $key string, hodnota $value sa nastaví v $data['Invoice'][$key]. Ak je $key pole, $value sa ignoruje.

Príklad použitia:
```php 
$api->setInvoice('name', 'nazov faktury');
```
```php
$api->setInvoice(array(
	'name' => 'nazov faktury',
	'variable' => '123456',
	'constant' => '0308',
	'bank_accounts' => array(
		array(
			'bank_name' => 'MyBank',
			'account' => '012345678',
			'bank_code' => '1234',
			'iban' => 'SK0000000000000000',
			'swift' => '12345',
		)
	)
));
  ``` 

Zoznam možných vlastností faktúry:
* **already_paid** - bola už faktúra uhradená? true/false
* **created** - dátum vystavenia
* **comment** - komentár
* **constant** - konštantný symbol
* **delivery** - dátum dodania
* **delivery_type** - spôsob dodania, číselník hodnôt
* **deposit** - uhradená záloha
* **discount** - zľava v %
* **discount_total** - nominálna zľava, použije sa iba ak nie je nastavená vlastnosť discount
* **due** - dátum splatnosti
* **estimate_id** - ID cenovej ponuky, na základe ktorej je faktúra vystavená
* **header_comment** - Text nad položkami faktúry
* **internal_comment** - Interná poznánka, nezobrazuje sa klientovi
* **invoice_currency** - mena, v ktorej je faktúra vystavená. Možnosti: EUR, USD, GBP, HUF, CZK, PLN, CHF, RUB
* **invoice_no_formatted** - číslo faktúry
* **issued_by** - faktúru vystavil
* **issued_by_phone** - faktúru vystavil telefón
* **issued_by_email** - faktúru vystavil email
* **issued_by_web** - 	webová stránka zobrazená na faktúre
* **name** - názov faktúry
* **paydate** - dátum úhrady
* **payment_type** - Spôsob úhrady, číselník hodnôt
* **proforma_id** - ID proforma faktúry, na základe ktorej sa vystavuje ostrá faktúra. Ostrá faktúra tak preberie údaje o uhradenej zálohe
* **parent_id** - ID faktúry, ktorú chceme dobropisovať.
* **rounding** - Spôsob zaokrúhľovania DPH. 'document' => za celý dokument, 'item' => po položkaćh (predvolená hodnota), 'item_ext' => maloobchod (odporúčaný typ pre eshopy)
* **specific** - špecifický symbol
* **sequence_id** - ID číselníka, zoznam číselníkov je možné získať metódou getSequences
* **type** - typ faktúry. Možnosti: regular - bežná faktúra, proforma - zálohová faktúra, cancel - dobropis, estimate - cenová ponuka, order - prijatá objednávka
* **variable** - variabilný symbol (v prípade nevyplnenia variabilného symbolu sa automaticky doplní číslo faktúry)
* **bank_accounts** - (pole) zoznam bankových účtov (pozri príklad vyššie)
* **order_no** - číslo objednávky
* **logo_id** - ID loga
* **tax_document** - (boolean) daňový doklad k prijatej platbe (pre správne prepojenie so zálohovou faktúrou je potrebné zadať proforma_id)

### 24. sendInvoiceEmail
Odošle faktúru emailom.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#send-invoice-via-mail))

##### Parametre
* **$options** *array*, povinné.
Príklad použitia:

```php 
$api->sendInvoiceEmail(array(
    'invoice_id' => 123456, // povinné
    'to' => 'example@example.com', // povinné
	'cc' => array(
	    'examplecc@examplecc.com'
	),
	'bcc' => array(
	    'examplebcc@examplebcc.com'
	),
	'subject' => 'Predmet', // pokial nie je nastaveny subject nastavi sa automaticky podla nastaveni
	'body' => 'Sprava' // pokial nie je nastaveny body nastavi sa automaticky podla nastaveni
		));
```
 
Zoznam možných nastavení:
* **invoice_id** *integer*, id faktúry, ktorú chcete odoslať (povinné)
* **to** *string*, na akú emailovú adresu sa má faktúra odoslať (povinné)
* **cc** *array*, cc
* **bcc** *array*, bcc
* **subject** *string*, predmet
* **body** *string*, telo správy
* **pdf_language** *string*, jazyk dokladu

Zoznam možných jazykov pre doklady:
* 'slo' => slovenčina
* 'cze' => čeština
* 'eng' => angličtina
* 'deu' => nemčina
* 'rus' => ruština
* 'ukr' => ukrajinčina
* 'hun' => maďarčina
* 'pol' => poľština
* 'rom' => rumunčina
* 'hrv' => chorvátčina
* 'slv' => slovinčina
* 'spa' => španielčina
* 'ita' => taliančina

### 25. sendInvoicePost
Odošle faktúru poštou.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#send-invoice-via-post))

##### Parametre
* **$options** *array*, povinné.

Príklad použitia:
```php 
$api->sendInvoicePost(array(
	'invoice_id' => 123456, // povinné
	
	// pokial nasledujúce parametre nie sú vyplnené vytiahnú sa automaticky z konkrétnej faktúry
    	'delivery_name' => 'SuperFaktura',
	'delivery_address' => 'Adresa 123',
    	'delivery_city' => 'Mesto',
    	'delivery_zip' => 12345,
    	'delivery_state' => 'Slovensko',
));
```
Zoznam možných nastavení: 
* **invoice_id** *integer*, id faktúry, ktorú chcete odoslať (povinné)

### 26. stockItemEdit
Aktualizuje skladovú položku.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/stock.md#edit-stock-item))

##### Parametre 
* **$item** *array*, povinné.

Príklad použitia:
```php 
$api->stockItemEdit(array(
    'id' => 123456, // povinné
    'name' => '*New stock item name', // novy nazov skladovej polozky
    'sku' => 'NEWST06K1T3M1D' // nove SKU
));
```  

Zoznam možných nastavení:
* **id** *integer*, id skladovej položky
* **name** *string*, názov skladovej položky
* **description** *string*, popis skladovej položky
* **sku** *string*, skladové číslo
* **unit_price** *integer*, jednotková cena bez DPH
* **vat** *integer*, DPH v percentách
* **stock** *integer*, počet kusov na sklade. Pokiaľ sa vynechá nebude sa sledovať stav zásob.
* **unit** *string*, jednotka napr. ks, mm, m2, dm3, l.

### 27. addStockItem
Pridá skladovú položku.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/stock.md#add-stock-item))

##### Parametre 
* **$item** *array*, povinné.

Príklad použitia:
```php 
$api->addStockItem(array(
    'name' => 'Stock item example', // nazov skladovej polozky
	'description' => 'Stock item description', // popis
	'sku' => 'SKU12345REF', // skladove cislo
	'unit_price' => 10, // jednotkova cena bez DPH
	'vat' => 20, // DPH v percentach
	'stock' => 100 // pocet kusov na sklade, ak nie je definovane nebudu sa sledovat pohyby
));
```  

Zoznam možných nastavení:
* **name** *string*, názov skladovej položky
* **description** *string*, popis skladovej položky
* **sku** *string*, skladové číslo
* **unit_price** *integer*, jednotková cena bez DPH
* **vat** *integer*, DPH v percentách
* **stock** *integer*, počet kusov na sklade. Pokiaľ sa vynechá nebude sa sledovať stav zásob.
* **unit** *string*, jednotka napr. ks, mm, m2, dm3, l.

### 28. addStockMovement
Pridá pohyb na sklade.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/stock.md#add-stock-movement))

##### Parametre 
* **$item** *array*, povinné.

Príklad použitia:
```php
$api->addStockMovement(array(
	'stock_item_id' => 0, // id skladovej polozky
	'quantity' => 10, // pocet kusov
	'note' => 'Stock movement description', // popis
	'created' => '2018-08-13', // datum pohybu
));
```

Zoznam možných nastavení:
* **stock_item_id** *iteger*, id skladovej položky, ku ktorej chceme pridať pohyb
* **quantity** *integer*, pohyb - záporné číslo je výdaj, kladné príjem
* **note** *string*, popis pohybu
* **created** *date* 'YEAR-MONTH-DAY' formát, dátum

### 29. setClient
Nastaví hodnoty pre klienta.
##### Parametre
Zhodné so setInvoice.

Zoznam možných vlastností klienta:
* **address** - adresa
* **bank_account** - bankový účet
* **city** - mesto
* **comment** - komentár
* **country_id** - ID krajiny, číselník krajín je možné získať metódou getCountries
* **country_iso_id** ISO 3166-1 (Alpha-2) kod krajiny
* **country** - vlastný názov krajiny
* **delivery_address** - dodacia adresa
* **delivery_city** - dodacie mesto
* **delivery_country** - vlastná dodacia krajina
* **delivery_country_id** - ID dodacej krajiny
* **delivery_country_iso_id** ISO 3166-1 (Alpha-2) kod krajiny
* **delivery_name** - názov klienta pre dodanie
* **delivery_zip** - dodacie PSČ
* **dic** - DIČ
* **email** - email
* **fax** - fax
* **ic_dph** - IČ DPH
* **ico** - IČO
* **name** - názov klienta *(údaj je povinný)*
* **phone** - telefón
* **delivery_phone** - telefónne číslo pre dodanie
* **zip** - PSČ
* **match_address** (boolean) - pokiaľ je tento parameter nastavený, do hľadania klienta vstupuje aj adresa.
* **update_addressbook** (boolean) - pri vystavení faktúry aktualizuje údaje klienta !!!

V prípade zahraničného klienta je potrebné správne vyplnenie country_id. AK country_id ostane prázdne, použije sa preddefinovaná hodnota pre Slovensko. Na zistenie country_id dannej krajiny použite funkciu [getCountries()](#12-getcountries).

### 30. stockItems
Vráti zoznam skladových položiek.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/stock.md#get-list-of-stock-items))

##### Parametre
* **$params** pole povinné. Parametre pre filtrovanie a stránkovanie.
* **$list_info** bool nepovinné. Určuje, či vrátené dáta budú obsahovať aj údaje o zozname (celkový počet položiek, počet strán...)

##### Možné parametre pre filtrovanie
```php
array(
	'page'          => 1, //Strana
	'per_page'      => 10, //Počet položiek na stranu
	'price_from'    => 0, //Cena od
	'price_to'      => 0, //Cena do
	'sku'           => '' //Unikátne číslo skladovej položky pomôže položku jednoducho identifikovať
	'search'        => '', //Hľadaný výraz. Prehľadáva všetky polia.
) 
``` 

##### Formát vrátených dát
```php  
{
    "itemCount": 67,
    "pageCount": 7,
    "perPage": 10,
    "page": 1,
    "items": [{
        "StockItem": {...},
    },...]
}
``` 
 
### 31. stockItem
Vráti detaily skladovej položky.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/stock.md#view-stock-item-details))

##### Parametre
* **$stock_item_id** int povinné. Získané z StockItem->id.

### 32. addContactPerson($data)
Pridá novú kontaktnú osobu k existujúcemu klientovi. Návratová hodnota je objekt (JSON). Pokiaľ operácia prebehla bez problémov je nastavený atribút status na hodnotu (string) 'SUCCESS'.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/contact-persons.md#add-contact-person))

```php
$result = $api->addContactPerson(array(
	'client_id' => ID_KLIENTA,  // ID existujuceho klienta
	'name' => 'Contact Person',  // Nazov kotaktnej osoby
	'email' => 'email@example.com'  // Email pre kontaktnu osobu
));
if ($result->status === 'SUCCESS')
	...;
```

### 33. getLogos()
Vráti detaily všetkých lôg. Návratová hodnota je objekt (JSON).

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/value-lists.md#logos))

## 34. getExpenseCategories()
Vráti zoznam všetkých kategórií nákladov. Návratová hodnota je objekt (JSON). 

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/value-lists.md#expense-categories))

## 35. setInvoiceSettings($settings)
nastaví vlastnosti pri zobrazovaní faktúry

##### Parametre 
* **$settings** *array*, povinné.

Zoznam možných vlastností faktúry:
* **language** *string*, nastaví jazyk faktúry.
* **signature** *boolean*, zobrazovať podpis.
* **payment_info** *boolean*, zobrazovať informáciu o úhrade.
* **online_payment** *boolean*, zobrazovať online platby.
* **bysquare** *boolean*, zobrazovať pay by square    
* **paypal** *boolean*, zobrazovať PayPal
* **show_prices** *boolean*, zobrazovať ceny (toto nastavenie sa týka výhradne dodacích listov)
* **callback_payment** *string*, URL, ktorá sa automaticky zavolá po pridaní úhrady k faktúre. GET parameter `invoice_id` bude pridaný k URL. Pre poslanú URL `https://example.com/callback/`, výsledná URL pre invoice_id 123 bude `https://example.com/callback/?invoice_id=123` alebo `https://example.com/callback/?invoice_id=123&secret_key={KEY}`. Parameter `secret_key` môže byť súčasťou poslanej URL (`https://example.com/callback/?secret_key=SECRET-KEY`) alebo byť nastavený v profile.

```php
	$api->setInvoiceSettings(array(
            'language' => 'slo',
            'signature' => true,
            'payment_info' => true,
            'online_payment' => true,
            'bysquare'     => true,
            'paypal' => true,
	));
```

## 36. setInvoiceExtras($extras)
rozšírené parametre faktúry
##### Parametre 
* **$extras** *array*, povinné.

Zoznam možných parametrov:
* **pickup_point_id** *int*, ID odberného miesta pre Zásielkovňu
* **weight** *float*, váha balíku,
* **parcel_count** *int*, počet balíkov,
* **insurance**, *float*, hodnota poistenia,
* **dimension**, *string*, rozmer balíku v cm (d x š x v)
* **tracking_number** *string*, podacie číslo
* **uuid** *string*, volitelný jednoznačný identifikátor faktúry

### 37. deleteInvoicePayment
Zmaže  úhradu k faktúre.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#delete-invoice-payment))

##### Parametre
* **$id** int povinné. 

### 38. deleteExpensePayment
Zmaže  úhradu k nákladu.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/expenses.md#delete-expense-payment))

##### Parametre
* **$id** int povinné. 

### 39. cashRegister
Vrati detail pokladne vrátane pohybov v pokladni.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/cash-register-item.md#get-cash-register-items))

##### Parametre
* **$id** int povinné. 

### 40. sendSMS($data)
odošle SMS.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/other.md#send-sms-reminder))

##### Parametre 
* **$data** *array*, povinné.

Zoznam možných parametrov:
* **$invoice_id** *int*, povinné.
* **$text** *string*, povinné  Text SMS.
* **$phone** *string*, nepovinné  ak nie je zadané tel. číslo použije  sa  tel. číslo klienta z faktúry

### 41. setMyData
Nastaví hodnoty pre údaje dodávateľa na faktúre
##### Parametre
* *$key* mixed povinné. Môže byť string, alebo pole. Ak je string, nastaví sa konkrétna hodnota v $data['MyData'][$key]. Ak je pole, nastaví sa viacero hodnôt naraz.
* *$value* mixed nepovinné. Ak je $key string, hodnota $value sa nastaví v $data['MyData'][$key]. Ak je $key pole, $value sa ignoruje.

Príklad použitia:
```php 
$api->setMyData('ic_dph', 'SK1234567890');
```
```php
$api->setMyData(array(
	'company_name' => 'Testovacia firma',
	'ic_dph' => 'SK1234567890',
	'country_id' => '57'
));
  ```
  
Zoznam možných úprav v údajoch dodávateľa:
* **country_id** - ID krajiny, číselník krajín je možné získať metódou getCountries
* **company_name** - Názov spoločnosti
* **dic** - DIČ
* **ic_dph** - IČ DPH
* **business_register** - Zápis v obchodnom registri
* **address** - Adresa spoločnosti - ulica a popisné číslo
* **city** - Adresa spoločnosti - mesto
* **zip** - Adresa spoločnosti - PSČ
* **update_profile** - Boolean hodnota. Ak sa pošle s hodnotou true, aktualizujú sa aj údaje v profile. Ak sa pošle s hodnotou false alebo sa nepošle vôbec, údaje sa zmenia len na faktúre, ale profilové údaje zostanú nezmenené

### 42. getInvoiceDetails($ids)
vráti detaily faktúr

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/invoice.md#get-invoice-details))

##### Parametre 
* **$ids** *array*, limit 100

Príklad použitia:
```php
$api->getInvoiceDetails(array(1, 2, 3, 4, 5, 6, 7, 8));
  ```
  
### 43. getUserCompaniesData($getAllCompanies)
Vráti údaje o firme, do ktorej ste momentálne prihlásený, prípadne o všetkých firmách, ku ktorým má účet prístup.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/other.md#get-user-companies-data))

##### Parametre 
* **$getAllCompanies** bool nepovinné. Ak je parameter true, vracia údaje o všetkých firmách, ku ktorým má účet prístup. Default je false - vráti údaje len o firme, v ktorej je účet momentálne prihlásený.

Príklad použitia:
```php
$api->getUserCompaniesData();
$api->getUserCompaniesData(true);
  ```

### 44. createRegularFromProforma($proforma_id)
vystaví ostrú faktúru zo zálohovej faktúry

##### Parametre 
* **$proforma_id** *int* povinné. Id zálohovej faktúry

Príklad použitia:
```php
$api->createRegularFromProforma(123);
  ```
  
### 45. setEstimateStatus($estimate_id, $status)
zmení stav cenovej ponuky

##### Parametre 
* **$estimate_id** *int* povinné. Id cenovej ponuky
* **$status** *int* povinné. Id stavu objednávky

Zoznam možných stavov cenovej ponuky:
*  1 => neschválená
*  2 => schválená
*  3 => zamietnutá



### 46. getBankAccounts()

Vráti zoznam bankových účtov.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/bank-account.md#get-list-of-bank-accounts))

```php
$api->getBankAccounts();
```

### 47. addBankAccount(array $data)

Pridá bankový účet.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/bank-account.md#add-bank-account))

##### Parametre

* **$data** *array* povinné. Údaje o bankovom účte

Zoznam možných vlastností bankového účtu:
* **bank_name** - názov banky
* **default** - je účet prednastavený?
* **iban** - IBAN
* **show** - zobrazuj účet na dokumentoch
* **swift** - SWIFT
* **bank_account** - číslo účtu
* **bank_code** - čislo banky

```php
$api->addBankAccount([
    'iban' => 'SK0000000123',
    'swift' => 'ABCDE12',
    'bank_name' => 'Prvá Fiktívna Banka',
    'default' => 1,
]);
```

### 48. updateBankAccount(int $id, array $data)

Uprav bankový účet.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/bank-account.md#update-bank-account))

##### Parametre

* **$id** *int* povinné. ID bankového účtu
* **$data** *array* povinné. Údaje o bankovom účte

Zoznam možných vlastností bankového účtu:
* **bank_name** - názov banky
* **default** - je účet prednastavený?
* **iban** - IBAN
* **show** - zobrazuj účet na dokumentoch
* **swift** - SWIFT
* **bank_account** - číslo účtu
* **bank_code** - čislo banky

```php
$api->updateBankAccount(123, [
    'bank_name' => 'Originálna Fiktívna Banka',
    'default' => 0,
]);
```


### 49. deleteBankAccount(int $id)

Zmaže bankový účet.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/bank-account.md#delete-bank-account))

##### Parametre

* **$id** *int* povinné. ID bankového účtu

```php
$api->deleteBankAccount(123);
```



### 50. addTag(array $data)

Pridá nový tag.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/tags.md#add-tag))

##### Parametre

* **$data** *array* povinné. Údaje o tagu

Zoznam možných vlastností tagu:
* **name** - názov tagu

```php
$api->addTag(['name' => 'nový tag',]);
```


### 51. updateTag(int $id, array $data)

Upraví existujúci tag.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/tags.md#edit-tag))

##### Parametre

* **$id** *int* povinné. ID tagu
* **$data** *array* povinné. Údaje o tagu

Zoznam možných vlastností tagu:
* **name** - názov tagu

```php
$api->editTag(123, ['name' => 'starý tag',]);
```

### 52. deleteTag(int $id)

Zmaže tag.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/tags.md#delete-tag))

##### Parametre

* **$id** *int* povinné. ID tagu

```php
$api->deleteTag(123);
```

### 53. client(int $id)

Vráti detail klienta.

([všeobecná REST API dokumentácia](https://github.com/superfaktura/docs/blob/master/clients.md#view-client-detail))

##### Parametre

* **$id** *int* povinné. ID klienta

```php
$api->client(123);
```



### Autorizácia
Pre prihlásenie sa do API je potrebný email, na ktorý je konto zaregistrované a API Token, ktorý je možné nájsť v Nástrojoch > API.
Samotná autorizácia sa vykonáva pomocou hlavičky "Authorization", ktorá ma nasledujúci tvar:

 ```php
"Authorization: SFAPI email=EMAIL&apikey=APITOKEN&company_id=COMPANYID"
 ```
company_id je nepovinný údaj, uvádza sa iba v prípade, že máte pod vašim emailov vytvorených viac spoločností a potrebujete určiť, s ktorou chcete pracovať

> **Túto hlavičku musí obsahovať každý request na SF API!**
 
### Vystavenie faktúry
Pokiaľ sa Vám nepáči náš SF API klient a chcete si faktúry vystavovať posvojom, tak nech sa páči:
Endpoint pre vystavenie faktúry sa nachádza na adrese https://moja.superfaktura.sk/invoices/create
Dáta pre vystavenie faktúry očakáva vo formáte JSON v $POST['data'] v nasledujúcej forme:
```php
$data = array(
	'Invoice' => array(
		//vsetky polozky su nepovinne, v pripade ze nie su uvedene, budu doplnene automaticky
		'name'                 => 'nazov faktury',
		'variable'             => '123456',
		'constant'             => '0308',
		'specific'             => '2015', //specificky symbol
		'already_paid'         => true, // bola uz faktura uhradena?
		'invoice_no_formatted' => '2015001', //ak nie je uvedene, SF ho doplni podla ciselnika
		'created'              => '2015-08-31', //datum vystavenia
		'delivery'             => '2015-08-31', //datum dodania
		'due'                  => '2015-08-31', //datum splatnosti
		'comment'              => 'komentar',
	),
	'Client' => array(
		'name'    => 'Janko Hrasko',
		'ico'     => '12345678',
		'dic'     => '12345678',
		'ic_dph'  => 'SK12345678',
		'email'   => 'janko@hrasko.sk',
		'address' => 'adresa',
		'city'    => 'mesto',
		'zip'     => 'psc',
		'phone'   => 'telefon',
	),
	'InvoiceItem' => array(
		array(
			'name'        => 'Superfaktura.sk',
			'description' => 'Členstvo',
			'quantity'    => 1,
			'unit'        => 'ks',
			'unit_price'  => 40.83,
			'tax'         => 20
		),
		array(
			'name'        => 'Druhá položka',
			'description' => '',
			'quantity'    => 10,
			'unit'        => 'ks',
			'unit_price'  => 5,
			'tax'         => 10
		)
	)
);

// Samotný request s použitím napr. Requests knižnice potom môže vyzerať nasledovne:
Requests::register_autoloader();
$response = Requests::post('https://moja.superfaktura.sk/invoices/create',
	$headers,
	array('data' => json_encode($data))
);
$response_data = json_decode($response->body, true);
//výsledkom tohto volania je JSON odpoveď v nasledujúcej forme
$response_data = array(
	'error'         => 0,
	'error_message' => 'Chybova hlaska',
	'data'          => array(),
);
 ```
 V prípade, ak došlo k nejakej chybe, bude error = 1 a error_message bude obsahovať popis chyby, ktorá nastala. V prípade, že chýb nastalo viac, bude error_message obsahovať pole s chybovými hláškami.
 
Ak bola faktúra úspešne vytvorená, budú v kľúči data uložené kompletné informácie o vytvorenej faktúre.

### PDF faktúry
Po vytvorení faktúry je možné stiahnuť jej PDF na adrese
 ```php
https://moja.superfaktura.sk/invoices/pdf/ID_FAKTURY/token:TOKEN
 ```
 kde ID FAKTURY sa nachádza v $data['Invoice']['id'] a token v $data['Invoice']['token'].


### Pre výpadok neprišiel response

V prípade, že volanie vystavenia faktúry nevrátilo response - vypadol internet, neprišla odpoveď do timeoutu a pod. - je možné dodatočne zistiť response pre toto volanie a zamedziť tak napr. duplicitnému vystaveniu rovnakej faktúry.

K faktúre je možné získať unikátny identifikátor (nie `invoice_id`).
Nakoľko ide o checksum, odporúčame v dátach faktúry uvádzať unikátny údaj objednávky (napr. číslo objednávky).

Postup:
1. štandardné vystavenie faktúry
2. `$api->save()`
3. `$checksum = $api->getChecksum()`
4. vybuchli internety a nemáme odpoveď do timeoutu
5. `$response = $api->getResponseByChecksum($checksum)`

`$response` obsahuje pôvodný response faktúry, ktorý ste prvýkrat nedostali.
Response je možné získať pre volanie nie staršie ako 3 mesiace.
Samozrejme volanie `getResponseByChecksum` je tiež potrebné kontrolovať na prípadnú chybovú odpoveď.
