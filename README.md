# FantaScarrupat

> Il sito della lega privata di fantacalcio "FantaScarrupat", fondata nel 2016.

---

## Indice

- [Panoramica](#panoramica)
- [Funzionalità](#funzionalità)
- [Struttura del repository](#struttura-del-repository)
- [Stack tecnologico](#stack-tecnologico)
- [Sicurezza](#sicurezza)
- [Database](#database)
- [Installazione locale](#installazione-locale)
- [Configurazione](#configurazione)
- [Autore](#autore)
- [Licenza](#licenza)

---

## Panoramica

FantaScarrupat è una lega amatoriale e privata di fantacalcio tra amici, attiva dal 2016. Ogni stagione si disputano un campionato a calendario (Serie A interna), coppe a eliminazione diretta, gironi (Champions League interna), una NBA a fasi miste e tornei speciali (Formula 1, Battle Royale...).

Il sito pubblico raccoglie la storia della lega, l'albo d'oro dei vincitori, i partecipanti, il regolamento ufficiale, le classifiche, i tabelloni delle competizioni e la galleria fotografica. Un'area riservata permette ai partecipanti di consultare dashboard e profilo personale, mentre un pannello admin gestisce l'intero ciclo di vita di ogni stagione: import del listone giocatori, formazioni, calendari e rose, calcolo automatico dei vincitori (inclusi supplementari e rigori nelle sfide a eliminazione diretta, secondo l'art. 9.5 del regolamento).

Sito live: **[fantascarrupat.altervista.org](http://www.fantascarrupat.altervista.org)**

---

## Funzionalità

**Area pubblica** (`public/`)
- Home, storia della lega, albo d'oro, elenco partecipanti
- Dettagli di ogni competizione disputata (classifiche, tabellone a eliminazione, calendario) e di ogni rosa
- Regolamento ufficiale, renderizzato dinamicamente a partire dalla sorgente LaTeX (`public/file/regolamento.tex`)
- Galleria fotografica, pagina contatti, privacy

**Area riservata** (`app/`, richiede login)
- Dashboard personale, hall of fame, profilo utente
- Sorteggio ("ruota della fortuna") per la composizione dei gironi

**Area amministrativa** (`app/admin/`, richiede ruolo admin)
- Gestione competizioni, calciatori/listone, partecipanti, rose, galleria
- Import da Excel/CSV/ZIP: calendario competizione, formazioni schierate, listone giocatori, rose d'asta
- Calcolo automatico del vincitore di un'edizione (classifica per il campionato; tempi regolamentari → supplementari → rigori per le eliminazioni dirette, leggendo le formazioni schierate dal DB)
- Export del database

---

## Struttura del repository

```
FantaScarrupat/
│
├── app/                     # Area riservata + admin (richiede login)
│   ├── _layout.php           # Layout condiviso dell'area riservata
│   ├── dashboard.php  hall-of-fame.php  profilo.php  sorteggio.php
│   └── admin/
│       ├── _admin_init.php    # Bootstrap condiviso (auth, DB, stagione corrente)
│       ├── competizioni.php  giocatori.php  partecipanti.php  rose.php  gallery.php
│       ├── import_competizione.php  import_formazioni.php
│       ├── import_listone.php  import_rose.php
│       ├── export_db.php
│       ├── lib/                # Logica di dominio (tie-break, import formazioni/xlsx)
│       └── sections/           # Partial HTML riusati dalle pagine admin
│
├── api/                     # Endpoint JSON/POST (fetch dal frontend)
│   ├── auth.php  formazioni.php  log_activity.php  profilo.php  ruota.php
│   └── admin/                # Endpoint riservati all'area admin
│
├── public/                  # Pagine pubbliche, nessun login richiesto
│   ├── _layout.php
│   ├── index.php  storia.php  albo-doro.php  partecipanti.php
│   ├── dettagli-competizione.php  dettagli-rosa.php
│   ├── regolamento.php  galleria.php  contattaci.php  privacy.php
│   └── file/                 # Regolamento (LaTeX + PDF), allegati scaricabili
│
├── auth/                    # Autenticazione e sessioni
│   ├── require_login.php  require_admin.php
│   ├── login_rate_limit.php  # Rate limiting persistente su DB, IP pseudonimizzato
│   └── logout.php
│
├── config/                  # Configurazione, non accessibile dal web (.htaccess deny-all)
│   ├── config.php            # Costanti globali, rilevamento ambiente WAMP/Altervista
│   ├── database.php          # Connessione PDO singleton
│   └── archive.php           # Archiviazione dei file caricati dall'admin
│
├── assets/                  # css/ js/ fonts/ img/ vendor/ (Bootstrap incluso self-hosted)
│
├── caricamenti_admin/        # Staging protetto dei file importati (competizioni, formazioni, rose)
├── uploads/                  # Contenuti caricati da admin (loghi squadre, foto coach, galleria)
├── logs/                     # Log applicativi, non accessibile dal web
│
├── index.php                # Serve public/index.php sulla root "/" (SEO/branding)
├── login.php
├── sitemap.php  robots.txt  llms.txt  site.webmanifest
└── .htaccess                 # Hardening web server (vedi sezione Sicurezza)
```

---

## Stack tecnologico

| Componente | Dettaglio |
|---|---|
| Linguaggio | PHP 8+ (`declare(strict_types=1)` ovunque), nessuna dipendenza Composer |
| Database | MySQL, accesso via PDO (prepared statement ovunque) |
| Frontend | HTML/CSS/JS vanilla, Bootstrap self-hosted (`assets/vendor/`) |
| Hosting | Altervista (produzione), WAMP (sviluppo locale) — `config/config.php` rileva l'ambiente automaticamente dal `DOCUMENT_ROOT` |
| Export dati | Import/export Excel (xlsx) e CSV per listone, formazioni e rose |

---

## Sicurezza

- **Header di sicurezza** su tutte le risposte (`.htaccess`): CSP, HSTS, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`; `X-Powered-By` rimosso.
- **File sensibili non serviti**: estensioni come `.sql`, `.log`, `.ini`, `.bak`, file nascosti (`.git*`, `.env`...) bloccati a livello di `.htaccess`; le cartelle `config/`, `caricamenti_admin/` e `logs/` hanno un proprio `.htaccess` deny-all.
- **Sessioni hardenate**: `session.use_strict_mode`, cookie `HttpOnly`/`SameSite=Lax`/`Secure` (automatico su HTTPS), rigenerazione periodica dell'ID, TTL sia di inattività che assoluto.
- **CSRF token** su tutte le azioni che modificano stato (form admin, import, cambi profilo).
- **Rate limiting login persistente**: i tentativi falliti sono registrati su DB indicizzati per hash SHA-256 dell'IP (mai l'IP in chiaro, GDPR-friendly); il rate limiter degrada in modo silenzioso se la tabella non esiste ancora.
- **Query parametrizzate ovunque** tramite PDO, nessuna concatenazione di input utente in SQL.

---

## Database

Non è incluso nel repository un dump SQL dello schema. Le tabelle principali usate dall'applicazione (ricostruite dal codice) sono:

`users`, `fantasquadra`, `stagione`, `competizione`, `competizione_disputata`, `tipologia_competizione`, `partita_avvessario`, `partita_solitaria`, `tipologia_partita`, `formazione`, `formazione_giocatore`, `rosa`, `dettagli_rosa`, `crediti_pagati`, `giocatore`, `giocatore_stagione`, `ruoli`, `ruoli_giocatori`, `parametri_rosa`, `user_parametro_assegnato`, `immagine`, `storia_timeline`, `login_attempts`.

---

## Installazione locale

1. **Prerequisiti**: PHP 8+, MySQL, un server con supporto `.htaccess` (es. [WAMP](https://www.wampserver.com/en/)).
2. **Clona il repository:**

    ```bash
    git clone https://github.com/AntonioDG30/FantaScarrupat.git
    ```

3. **Database**: crea uno schema MySQL con le tabelle elencate sopra (nessun dump incluso) e configura le credenziali in `config/config.php`.
4. **Deploy locale**: posiziona il progetto nella root del server WAMP (es. `C:\wamp64\www\FantaScarrupat`). `config/config.php` rileva automaticamente il base path confrontando `DOCUMENT_ROOT` con la cartella del progetto — nessuna configurazione manuale di percorsi richiesta.
5. **Nota**: alcune funzionalità (consenso cookie, header HSTS reali) dipendono da un ambiente con HTTPS attivo e non sono pienamente verificabili in locale.

---

## Configurazione

Le costanti applicative vivono in `config/config.php` e non vanno mai esposte lato web (cartella protetta da `.htaccess`):

- `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` — credenziali di connessione (in produzione su Altervista l'autenticazione MySQL non richiede password lato applicativo)
- `CURRENT_ANNO` / `LAST_ANNO` — stagione corrente, calcolata automaticamente con cambio al 1° luglio
- `SESSION_IDLE_TTL` / `SESSION_ABSOLUTE_TTL` / `SESSION_REGEN_EVERY` — parametri di hardening sessione
- `ASSET_VER` — cache-busting manuale per CSS/JS, da incrementare a ogni modifica degli asset

---

## Autore

**Antonio Di Giorgio** — Developer
GitHub: [@AntonioDG30](https://github.com/AntonioDG30)

---

## Licenza

Progetto privato a uso della lega FantaScarrupat. Questo recupero del codice non include un file `LICENSE`; in assenza di una licenza esplicita, il codice non è da considerarsi open-source riutilizzabile.
