# SuperFaktúra PHP-API klient

## Overview
 API SuperFaktúry umožňuje prepojenie externých aplikácií so SuperFaktúrou a 
 dovoľuje tak vzdialene vytvárať doklady a získavať údaje o nich. 
 Umožňuje tiež odosielať faktúry emailom alebo poštou.
 
## Quickstart
 Aby ste sa nemusli trápiť s priamymi volaniami API funkcií a spôsobom prenosu dát, 
 pripravili sme pre Vás jednoduchého API klienta, vďaka ktorému môžete Vaše faktúry vystavovať nadiaľku s 
 minimálnym úsilím.
 
## Postup ako získať PHP-API klienta

### 1. spôsob (vyžaduje nainštalovaný systém Git)
  1. vytvorte si adresár, ktorý má obsahovať SuperFaktúra PHP-API napr. (*$> mkdir /var/www/myproject/libs*)
  2. vstúpte do novo vytvoreného adresára a spustite cez konzolu 
  príkaz *$> git clone https://github.com/superfaktura/apiclient.git*
  
### 2. spôsob (nevyžaduje nainštalovaný systém Git)
  1. Stiahnite si SuperFaktúra PHP-API kliknutím na tlačidlo "Stiahnuť ZIP", ktoré sa nachádza na 
  github stránke nášho API.

## Ukážky kódu
  Aby sme vám uľahčili prácu pri implementácii nášho API, vytvorili sme ukážky kódu 
  ([sample.php](https://github.com/superfaktura/apiclient/blob/master/sample.php) a [sample2.php](https://github.com/superfaktura/apiclient/blob/master/sample2.php)),
  ktoré demonštrujú jeho funkcionalitu a dpĺňajú našu dokumentáciu o fungujúce príklady.

## Začíname používať SuperFaktúra PHP-API
  Na to, aby ste mohli začať API na plno využívať, je potrebné:
### 1. Zaregistrovať sa v SuperFaktúre
  * Na stránke https://moja.superfaktura.sk/registracia vykonajte registráciu. Automaticky získate 30 dní zadarmo.
  * Po prihlásení vystavte skúšobnú faktúru cez GUI SuperFaktúry
  
### 2. Urobiť základné nastavenia v kóde
  * Vytvoriť novú inštanciu triedy *SFAPIclient*
  * Poskytnúť konštruktoru prihlasovacie údaje do API
    + **Email** - prihlasovací email do SuperFaktúry
    + **Token** - API token, ktorý nájdete v SuperFaktúre po prihlásení do svojho účtu "*Nástroje > API prístup*"
  ```php
require_once('SFAPIclient/SFAPIclient.php');  // inc. SuperFaktúra PHP-API
$login_email = 'login@example.com';  // moja.superfaktura.sk login email
$api_token = 'abcd1234';  // token from my account
$sf_api = new SFAPIclient($login_email, $api_token);  // create SF PHP-API object
  ```
  
### 3. Používanie PHP-API volaní
  Nižšie je uvedený zoznam všetkých možných volaní, ktoré obsahuje najnovšia verzia nášho API.
  *Všetky PHP funkcie nášho API sú verejné členské funkcie triedy SFAPIclient*.
  Príklad vystavenia jednoduchej faktúry (pokračovanie predch. príkladu)
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
## Zoznam volaní (verejných členských funkcií vrátane konštruktora triedy SFAPIclient)
  * *__construct($email, $apikey)*
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
 
### 1. __construct
 Konštruktor. Nastaví email a API token pre autorizáciu.
##### Parametre
  * *$email* string povinný
  * *$token* string povinný

### 2. addItem
 Pridá položku na faktúru.
##### Parametre
 * *$item* pole povinné

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
	'load_data_from_stock' => true //Načíta nevyplnené údaje položky zo skladu
)
 ```
### 3. addTags
 Pridá faktúre tagy podľa číselníka
##### Parametre
* *$tags_ids* pole povinné, pole ID požadovaných tagov

### 4. clients
 Vráti zoznam klientov
##### Parametre
* *$params* pole povinné. Parametre pre filtrovanie a stránkovanie.
* *$list_info* bool nepovinné. Určuje, či vrátené dáta budú obsahovať aj údaje o zozname (celkový počet položiek, počet strán...)

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
##### Parametre
* *$id int* povinné. Získané z Invoice->id.

### 6. deleteInvoiceItem
Zmaže položku na faktúre.
##### Parametre
* *$invoice_id* int povinné. Získané z Invoice->id.
* *$id* int povinné. Získané z InvoiceItem->id.

### 7. deleteExpense
Zmaže náklad.
##### Parametre
* *$id* int povinné. Získané z Expense->id.

### 8. deleteStockItem
Zmaže skladovú položku.
##### Parametre
* *$id* int povinné. Získané z StockItem->id.

### 9. edit
Uloží nastavené dáta a aktualizuje faktúru.
##### Parametre: žiadne
##### Návratová hodnota: objekt
##### Kódy chýb
* **1** Id dokladu má nesprávny formát
* **2** Neexistujúce id dokladu
* **3** Chyba pri editácii faktúry. Volanie treba opakovať.
* **6** Chyba pri validácii úrajov. Povinné údaje chýbajú alebo nemajú správny formát.

### 10. expenses
Vráti zoznam nákladov.
##### Parametre
* *$params* pole povinné. Parametre pre filtrovanie a stránkovanie.
* *$list_info* bool nepovinné. Určuje, či vrátené dáta budú obsahovať aj údaje o zozname (celkový počet položiek, počet strán...)

### 11. expense
Vráti detaily nákladu.
##### Parametre
* *$expense_id* int povinné. Získané z Expense->id.

### 12. getCountries
Vráti číselník krajín.

### 13. getSequences
Vráti číselník číselných radov podľa typov dokumentov.

### 14. getPDF
Vráti PDF súbor s faktúrou.
##### Parametre
* *$invoice_id* int povinné. Získané z Invoice->id.
* *$token* string povinné. Získané z Invoice->token.
* *$language* string nepovinné. Jazyk požadovaného PDF. Možné hodnoty sú {slo, cze, eng}

### 15. getTags
Vráti číselník existujúcich tagov.

### 16. invoice
Vráti detaily faktúry.
##### Parametre
* *$invoice_id* int povinné. Získané z Invoice->id.

### 17. invoices
Vráti zoznam vystavených faktúr.

##### Parametre
* *$params* pole povinné. Parametre pre filtrovanie a stránkovanie.
* *$list_info* bool nepovinné. Určuje, či vrátené dáta budú obsahovať aj údaje o zozname (celkový počet položiek, počet strán...)

##### Možné parametre pre filtrovanie, číselníky hodnôt sa nachádzajú pod zoznamom parametrov  

```php
Array(
	'page'          => 1, //Strana
	'per_page'      => 10, //Počet položiek na stranu
	'created'       => 0, //Dátum vystavenia.
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
  Typ faktúry
```php
Array
(
	[regular]  => Bežná
	[proforma] => Zálohová faktúra
	[estimate] => Cenová ponuka
	[cancel]   => Dobropis
)
  ```
  Spôsob dodania
  ```php  
Array
(
	[mail]     => Poštou
	[courier]  => Kuriérom
	[personal] => Osobný odber
	[haulage]  => Nákladná doprava
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
  
### 18. markAsSent
Označí faktúru ako odoslanú emailom. Užitočné, pokiaľ vytvorené faktúry odosielate vlastným systémom, avšak chcete toto odoslanie evidovať aj v SuperFaktúre.
##### Parametre
* *$invoice_id* int povinné. Získané z Invoice->id
* *$email* string povinné. Emailová adresa, kam bola faktúra odoslaná.
* *$subject* string nepovinné. Predmet emailu.
* *$message* string nepovinné. Text emailu.
##### Návratová hodnota: objekt

### 19. payInvoice
Dodatočne pridá úhradu ku faktúre.
##### Parametre
* *$invoice_id* int povinné. Získané z Invoice->id
* *$amount* float povinné. Uhradená suma.
* *$currency* string nepovinné. Mena úhrady, predvolené EUR.
* *$date* string nepovinné. Dátum úhrady, predvolený aktuálny dátum.
* *$payment_type* string nepovinné. Spôsob úhrady, predvolený typ transfer. Možné hodnoty {cash,transfer,credit,paypal,cod}

### 20. payExpense
Dodatočne pridá úhradu k nákladu.
##### Parametre
* *$expense_id* int povinné. Získané z Expense->id
* *$amount* float povinné. Uhradená suma.
* *$currency* string nepovinné. Mena úhrady, predvolené EUR.
* *$date* string nepovinné. Dátum úhrady, predvolený aktuálny dátum.
* *$payment_type* string nepovinné. Spôsob úhrady, predvolený typ transfer. Možné hodnoty {cash,transfer,credit,paypal,cod}

### 21. save
Uloží nastavené dáta a vystaví faktúru.
##### Paramete: žiadne
##### Návratová hodnota: objekt
##### Kódy chýb
* **2** Dáta neboli odoslané metódou POST.
* **3** Nesprávne dáta. Odoslané dáta nemajú správny formát.
* **5** Validačný error. Povinné údaje chýbajú alebo sú nesprávne vyplnené.

### 22. setExpense
Nastaví hodnoty pre náklad.
##### Paramete
* *$key* mixed povinné. Môže byť string, alebo pole. Ak je string, nastaví sa konkrétna hodnota v $data['Expense'][$key]. Ak je pole, nastaví sa viacero hodmôt naraz.
* *$value* mixed nepovinné. Ak je $key string, hodnota $value sa nastaví v $data['Expense'][$key]. Ak je $key pole, $value sa ignoruje.

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
		'constant' => '0308' // konštantný symbol
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

### 23. setInvoice
Nastaví hodnoty pre faktúru
##### Parametre
* *$key* mixed povinné. Môže byť string, alebo pole. Ak je string, nastaví sa konkrétna hodnota v $data['Invoice'][$key]. Ak je pole, nastaví sa viacero hodmôt naraz.
* *$value* mixed nepovinné. Ak je $key string, hodnota $value sa nastaví v $data['Invoice'][$key]. Ak je $key pole, $value sa ignoruje.

Príklad použitia:
  ```php 
$api->setInvoice('name', 'nazov faktury');
  ```
  ```php   
$api->setInvoice(array(
		'name' => 'nazov faktury',
		'variable' => '123456',
		'constant' => '0308'
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
* **due** - dátum splatnosti
* **estimate_id** - ID cenovej ponuky, na základe ktorej je faktúra vystavená
* **header_comment** - Text nad položkami faktúry
* **internal_comment** - Interná poznánka, nezobrazuje sa klientovi
* **invoice_currency** - mena, v ktorej je faktúra vystavená. Možnosti: EUR, USD, GBP, HUF, CZK, PLN, CHF, RUB
* **invoice_no_formatted** - číslo faktúry
* **issued_by** - faktúru vystavil
* **issued_by_phone** - faktúru vystavil telefón
* **issued_by_email** - faktúru vystavil email
* **name** - názov faktúry
* **payment_type** - Spôsob úhrady, číselník hodnôt
* **proforma_id** - ID proforma faktúry, na základe ktorej sa vystavuje ostrá faktúra. Ostrá faktúra tak preberie údaje o uhradenej zálohe
* **rounding** - Spôsob zaokrúhľovania DPH. 'document' => za celý dokument, 'item' => po položkaćh (predvolená hodnota)
* **specific** - špecifický symbol
* **sequence_id** - ID číselníka, zoznam číselníkov je možné získať metódou getSequences
* **type** - typ faktúry. Možnosti: regular - bežná faktúra, proforma - zálohová faktúra, cancel - dobropis, estimate - cenová ponuka, order - prijatá objednávka
* **variable** - variabilný symbol

### 24. sendInvoiceEmail
Odošle faktúru emailom.
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
			// 'subject' => 'Predmet', // pokial nie je nastaveny subject nastavi sa automaticky podla nastaveni
			// 'body' => 'Sprava' // pokial nie je nastaveny body nastavi sa automaticky podla nastaveni
		));
 ```
 
Zoznam možných nastavení:
* **invoice_id** *integer*, id faktúry, ktorú chcete odoslať (povinné)
* **to** *string*, na akú emailovú adresu sa má faktúra odoslať (povinné)
* **cc** *array*, cc
* **bcc** *array*, bcc
* **subject** *string*, predmet
* **body** *string*, telo správy

### 25. sendInvoicePost
Odošle faktúru poštou.
##### Parametre
* **$options** *array*, povinné.

Príklad použitia:
  ```php 
$api->sendInvoicePost(array(
	'invoice_id' => 123456, // povinné
	/** > POKIAL NIE SU NASTAVENE VYTIAHNU SA Z FAKTURY < *
	 ******************************************************
	 'delivery_address' => 'Adresa 123',
	 'delivery_city' => 'Mesto',
	 'delivery_state' => 'Slovenská republika'
	 ******************************************************/
));
  ```
  
