# Príklady použitia API SuperFaktúry pomocou knižnice CURL - command line tool
Tento dokument popisuje najčastejšie API volania SuperFaktúry pomocou knižnice CURL (command line tool).
Texty sú doplnené o fungujúce príklady.

## Základná URL pre API
* Základná URL pre SK https://moja.superfaktura.sk
* Základná URL pre CZ https://moje.superfaktura.cz
* Základná URL pre AT https://meine.superfaktura.at

## Autorizačná hlavička
Každý dotaz musí obsahovať autorizačné pole v hlavičke vo formáte:
```http
Authorization: SFAPI email=your@email.com&apikey=yourtoken
```
Autorizačné pole obsahuje prihlasovací email do SuperFaktúry a token, ktorý môžete nájsť v SuperFaktúre po prihlásení v 
'*Nástroje - API prístup*'

## Zoznam všetkých endpointov 
### Faktúry:
  *  [vystavenie faktúry - POST: /invoices/create](#príklad-vystavenia-novej-faktúry)
  *  zmazanie faktúry - GET: /invoices/delete/id
  *  zmazanie položky z faktúry - GET: /invoice_items/delete/item_id/invoice_id:id_faktury
  *  [editácia faktúry - POST:/invoices/edit](#príklad-editovania-faktúry-prostredníctvom-api)
  *  pdf faktúry - GET: /language/invoices/pdf/id_faktúry/token:token_faktúry
  *  [vrátenie detailu faktúry v json formáte - GET: /invoices/view/id.json](#príklad-načítania-faktúry)
  *  [zoznam faktúr v json formáte - GET: /invoices/index.json](#príklad-vrátenia-zoznamu-vystavených-faktúr)
  *  označ faktúru ako odoslanú - POST: /invoices/mark_as_sent
  *  [pridanie úhradu k faktúre - POST: /invoice_payments/add](#príklad-dodatočnej-úhrady-faktúry)
  *  [pošli faktúru emailom - POST: /invoices/send](#príklad-odoslania-faktúry-na-email-prostredníctvom-api)
  *  [pošli faktúru poštou - POST: /invoices/post](#príklad-odoslania-faktúry-poštou-prostredníctvom-api)

### Náklady:
  *  vytvorenie nákladu - POST: /expenses/add
  *  zmazanie nákladu - GET: /expenses/delete/id
  *  editácia nákladu - POST: /expense/edit
  *  vratenie detailu nákladu v json formáte - GET: /expenses/edit/id.json
  *  zoznam nákladov v json formáte - GET: /expenses/index.json
  *  pridanie úhrady k nákladu - POST: /expense_payments/add
  
### Sklad:
  *  pridanie skladovej položky - POST: /stock_items/add
  *  pridanie pohybu v sklade - POST: /stock_items/addstockmovement
  *  zmazanie položky zo skladu - GET: /stock_items/delete/id
  *  editacia skladovej položky - POST: /stock_items/edit
  *  zoznam všetkých skladových položiek v json formáte - GET: /stock_items/index.json
  *  detail skladovej položky v json formáte - GET: /stock_items/edit/id.json

### Kontakty:
  *  vytvorenie klienta - POST: /clients/create
  *  zoznam klientov v json formáte - GET: /clients/index.json
  *  pridanie kontaktnej osoby - POST: /contact_people/add

### Číselníky:
  *  zoznam číselníkov v json formáte - GET: /sequences/index.json

### Krajiny:  
  *  zoznam krajín v json formáte - GET: /countries

### Tagy: 
  *  zoznam tagov v json formáte - GET: /tags/index.json
 
### Logo:
 * vráti zoznam všetkých lôg v json formáte - GET: /users/logo 
  
## Príklad vystavenia novej faktúry
URL volania */invoices/create*. Popis štruktúry údajov nájdete v README.md.
```shell
curl https://moja.superfaktura.sk/invoices/create -H"Authorization: SFAPI email=your@email.com&apikey=yourtoken" -d "data={\"Invoice\":{\"name\":\"Invoice Sample\",\"bank_accounts\":[{\"bank_name\":\"MyBank\",\"account\":\"0123456789\",\"bank_code\":8855,\"iban\":\"SK0000000000000\",\"swift\":\"xxxx\"}]},\"Client\":{\"name\":\"Sample Client\",\"ico\":\"12345678\"},\"InvoiceItem\":[{\"name\":\"Sample Item\",\"unit_price\":3.14,\"quantity\":2,\"tax\":20}]}"
```
Volaním vystavíme novú faktúru s názvom 'Sample Invoice' pre klienta 'Sample Client' s IČO '12345678'. Faktúra bude obsahovať jednu fakturačnú položku s názvom 'Sample Item', jednotkovou cenou 3.14 EUR za kus, v počte 2 kusy a s 20% DPH. Nastavia sa aj údaje o banke (atribút "bank_accounts").

## Príklad dodatočnej úhrady faktúry
URL volania */invoice_payments/add*. Popis štruktúry údajov nájdete v README.md.
```shell
curl https://moja.superfaktura.sk/invoice_payments/add -H"Authorization: SFAPI email=your@email.com&apikey=yourtoken" \
-d "data={\"InvoicePayment\":{\"invoice_id\":1067146,\"amount\":12.00}}"
```
Volaním pridáte úhradu 12 EUR k faktúre s ID '1067146'.

## Príklad načítania faktúry
URL volania */invoices/view/ID*. Popis štruktúry údajov nájdete v README.md.
```shell
curl https://moja.superfaktura.sk/invoices/view/1068009.json -H"Authorization: SFAPI email=your@email.com&apikey=yourtoken" 
```
Volaním načítate faktúru s ID '1068009'

## Príklad vrátenia zoznamu vystavených faktúr
URL volania */invoices/index*. Popis štruktúry údajov nájdete v README.md.
```shell
curl https://moja.superfaktura.sk/invoices/index.json/page:1/per_page:20/delivery:1 -H"Authorization: SFAPI email=email@email.com&apikey=yourtoken" 
```
Volanie vráti zoznam vystavených faktúr v počte 20 faktúr na stránku, s dnešným dátumom dodania.

## Príklad odoslania faktúry na email prostredníctvom API
URL volania */invoices/send*. Popis štruktúry údajov nájdete v README.md.
```shell
curl https://moja.superfaktura.sk/invoices/send -H "Authorization: SFAPI email=your@email.com&apikey=yourtoken" -d "data={\"Email\":{\"invoice_id\":1068288,\"to\":\"example@example.com\"}}"
```
SuperFaktúra odošle email s faktúrou ID 1068288 (v prílohe) na adresu example@example.com (o celú komunikáciu sa postará SuperFaktúra). Vykoná sa tá istá akcia, ako pri odoslaní faktúry cez používateľské prostredie. Týmto jednoduchým volaním si môžete odosielanie faktúr vo svojom systéme úplne automatizovať.

## Príklad odoslania faktúry poštou prostredníctvom API
URL volania */invoices/post*. Popis štruktúry údajov nájdete v README.md.
```shell
curl https:/moja.superfaktura.sk/invoices/post -H "Authorization: SFAPI email=your@email.com&apikey=yourtoken" \
-d "data={\"Post\":{\"invoice_id\":INVOICE_ID}}"
```
Odošle faktúru s ID "INVOICE_ID" poštou. Pri odoslaní sa kontroluje správnosť údajov príjemcu (ulica, PSC, mesto). Tiež je potrebné mať zakúpené poštové známky v SuperFaktúre. Pre viac info prosím navštívte stránku https://moja.superfaktura.sk/post_stamps alebo *Nástroje > Pošta*.

## Príklad editovania faktúry prostredníctvom API
URL volania */invoices/edit*. Popis štruktúry údajov nájdete v README.md.
```shell
curl https:/moja.superfaktura.sk/invoices/post -H "Authorization: SFAPI email=your@email.com&apikey=yourtoken" \
-d "data={\"Invoice\":{\"id\":\"INVOICE_ID\"},\"InvoiceItem\":[{\"id\":\"INVOICE_ITEM_ID\",\"name\":\"novy nazov\"}]}"
```
Upraví na faktúre s ID "INVOICE_ID" názov položky s ID INVOICE_ITEM_ID na "novy nazov"
