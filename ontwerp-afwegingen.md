# Ontwerpkeuzes: haalbaarheid, privacy en security

> **TL;DR**
> Ik heb dit ontwerp zo opgezet dat het binnen de tijd haalbaar blijft, zo min mogelijk persoonsgegevens gebruikt, en technisch sterk staat tegen misbruik van trades en rechten.

---

## Context in 30 seconden
Deze app draait om spelers die items verzamelen en traden, met admins die kunnen bijsturen.
Dus mijn prioriteiten zijn:

| Focus | Waarom dit belangrijk is in deze app |
| --- | --- |
| Haalbaarheid | De basisflow moet af zijn: registreren, inventory, trades, admin-moderatie. |
| Privacy | We willen geen onnodige persoonlijke data verzamelen voor een game-feature. |
| Security | Trades en admin-acties zijn gevoelig; daar mag niks "half" geregeld zijn. |

---

## 1) Haalbaarheid: dit kan echt af
### Waarom dit realistisch is
Ik bouw op dingen die al stevig zijn in de stack:

- Filament resources/pages/widgets zijn al aanwezig.
- Rollen/rechten lopen via Shield + Spatie permissions.
- Trade-logica zit centraal in `TradeResource` (dus geen losse business-logica overal).
- Database-structuur is al duidelijk opgesplitst (`users`, `inventory_items`, `trades`, `trade_items`, `audit_logs`).

### Plan B als de tijd tegenzit
Ik snij dan niet in de kern, maar in extra's:

| Onderdeel | Gewone versie | Tijd-krap versie |
| --- | --- | --- |
| Notificaties | Database + optionele e-mail | Alleen database-notificaties |
| Admin insights | Uitgebreide filters en widgets | Basisstats en kernoverzicht |
| Trade UX | Extra comfort in berichten/flows | Simpel maar stabiel formulier |
| UI polish | Meer widgets/variaties | Alleen hoofdflows netjes af |

Kort gezegd: core eerst waterdicht, daarna pas luxe.

---

## 2) Privacy: alleen pakken wat nodig is
### Welke gegevens ik gebruik (en waarom)

| Gegeven | Waarom nodig | Waarom niet meer |
| --- | --- | --- |
| `name`, `username` | Herkenbare accountidentiteit | Geen adres/telefoon nodig |
| `email` | Login + verificatie + notificaties | Geen marketingprofiel nodig |
| `password` (hash) | Veilige authenticatie | Wachtwoord nooit plaintext |
| `notification_preferences` | User kiest zelf meldingen | Geen gedragsprofilering |
| Trade-data | Nodig voor tradeflow en historie | Geen extra persoonsvelden |
| Audit-log data | Controle op admin-acties | Alleen acties die echt gevoelig zijn |

### Hoe ik data-minimalisatie toepas
- Username mag leeg blijven en wordt dan automatisch gegenereerd.
- Tradebericht is optioneel en begrensd (max 1000 tekens).
- Notificatievoorkeuren zijn simpele booleans (aan/uit).
- Spelers zien geen overbodige privédata van andere spelers.

### Hoe privacy technisch beschermd is
- Wachtwoorden worden gehasht opgeslagen.
- Sessies draaien server-side en CSRF-bescherming staat aan.
- Inventory voor spelers is scoped op eigen `user_id`.
- Gevoelige admin-acties worden vastgelegd in `audit_logs`.

### Hoe ik users duidelijk informeer
- Bij registratie: kort waarom e-mail/username nodig zijn.
- In profiel: duidelijke uitleg per notificatie-optie.
- Op profiel/trade-gerelateerde schermen: korte, heldere privacy-uitleg in normale taal.

---

## 3) Security: meerdere lagen, niet gokken
### Toegang en rechten
- Authenticatie via Filament login + e-mailverificatie.
- Autorisatie via roles/permissions + policies.
- Resource checks (`canViewAny`, `canCreate`, `canView`) en query-scoping.
- Admin-acties zoals lock/unlock/transfer/force-cancel zijn echt admin-only.

### Belangrijkste risico's en aanpak
| Risico | Wat er mis kan gaan | Maatregel in ontwerp |
| --- | --- | --- |
| Race condition bij trade | Items dubbel gebruikt of fout overgezet | `DB::transaction(...)` + `lockForUpdate()` |
| Open trade misbruik | Item toch wijzigen tijdens trade | `locked` status op inventory-items |
| Rechten lekken | Speler ziet/kan admin-dingen | Policy checks + resource checks + scoped queries |
| Slechte traceability | Onzichtbare admin-ingrepen | Logging in `audit_logs` |

### Auth-keuzes
- Login met username + password (praktisch voor spelers).
- E-mailverificatie voor registratie en e-mailwijziging.
- Niet alleen UI verbergen, maar altijd backend-validatie/rechten afdwingen.

---

## 4) Volgende verbeterstappen
Voor een volgende iteratie zijn dit de slimste upgrades:

1. 2FA verplicht maken voor admin-accounts.
2. `canAccessPanel()` strakker maken op rolbasis.
3. Retentiebeleid toevoegen voor audit logs en notificaties.
4. Productie-hardening afdwingen (`SESSION_SECURE_COOKIE=true`, HTTPS-only, extra rate limiting).
5. Meer security regression tests voor admin-acties vanuit spelercontext.

---

## Eindconclusie
Dit ontwerp is haalbaar omdat ik leun op bestaande bouwblokken, privacy-vriendelijk omdat ik alleen noodzakelijke data verwerk, en security-sterk omdat rechten, transacties en logging vanaf de basis zijn meegenomen.
