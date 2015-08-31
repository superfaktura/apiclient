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
  2. prepnite sa do novo vytvoreného adresára a spustite cez konzolu 
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
  * Poskytnúť v konštruktore prihlasovacie údaje do API
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



 
  


 
