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

## Príklad odoslania faktúry POŠTOU prostredníctvom API
URL volania */invoices/post*. Popis štruktúry údajov nájdete v README.md.
```shell
curl https:/moja.superfaktura.sk/invoices/post -H "Authorization: SFAPI email=your@email.com&apikey=yourtoken" \
-d "data={\"Post\":{\"invoice_id\":INVOICE_ID}}"
```
Odošle faktúru s ID "INVOICE_ID" poštou. Pri odoslaní sa kontroluje správnosť údajov príjemcu (ulica, PSC, mesto). Tiež je potrebné mať zakúpené poštové známky v SuperFaktúre. Pre viac info prosím navštívte stránku https://moja.superfaktura.sk/post_stamps alebo *Nástroje > Pošta*.

