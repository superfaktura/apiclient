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
curl https://moja.superfaktura.sk/invoices/create -H"Authorization: SFAPI email=your@email.com&apikey=yourtoken" \
-d 'data={"Invoice":{"name":"Invoice Sample"},"Client":{"name":"Sample Client","ico":"12345678"},"InvoiceItem":[{"name":"Sample Item","unit_price":3.14,"quantity":2,"tax":20}]}'
```
Volaním vystavíme novú faktúru s názvom 'Sample Invoice' pre klienta 'Sample Client' s IČO '12345678'. Faktúra bude obsahovať jednu fakturačnú položku s názvom 'Sample Item', jednotkovou cenou 3.14 EUR za kus, v počte 2 kusy a s 20% DPH.

