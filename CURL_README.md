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


