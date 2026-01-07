# Stav projektu Organon – 2026-01-06

Tento soubor dokumentuje aktuální stav projektu Organon v PHP pro zajištění kontinuity a konzistence.

## 1. Cíl Projektu

Hlavním cílem je vytvořit lehký, přenositelný „organizační operační systém“ v PHP. Aplikace má za cíl spravovat organizační strukturu, uživatelské role a klíčové manažerské interakce, jako je zadávání cílů, sledování úkolů a udělování uznání.

Projekt vychází z principů definovaných v souborech `trojuhelnikctverec.txt` (komplexní vize) a `nevrhresenitrojuhelnikctverec.txt` (MVP – minimální životaschopný produkt).

Ziskal jsem popis co musi byt definovano nez se zacne plnit definice podniku je to vypis z chatgpt kde jsem nechal vydefinovat co to ma presne delat je to v souboru upresneni.txt

## 2. Použité Technologie

- **Jazyk:** PHP 8.3+
- **Databáze:** SQLite (souborová, umístěná v `data/organon.db`)
- **Stylování:** Pico.css (přes CDN) pro čisté a minimalistické UI.

## 3. Aktuálně Implementované Funkce (POC v1)

Bylo vytvořeno funkční jádro (Proof of Concept) s následujícími vlastnostmi:

- **Vytvoření databáze:** Pomocí skriptu `setup.php` se vytvoří soubor databáze a všechny potřebné tabulky (`users`, `departments`, `goals`, `action_items`, `recognitions`).
- **Výchozí data:** Skript `seed.php` vytvoří administrátorský účet **admin** s heslem **admin**.
- **Autentizace:** Plně funkční systém přihlašování a odhlašování založený na sessions.
- **Správa uživatelů (CRUD):** Stránka `?page=users` umožňuje vytvářet, číst, upravovat a mazat uživatele.
- **Správa oddělení (CRUD):** Stránka `?page=departments` umožňuje spravovat organizační hierarchii, včetně nastavování nadřazených oddělení a manažerů.
- **Routing:** Jednoduchý front-controller (`public/index.php`) obsluhuje všechny požadavky na stránky.
- **Základní UI:** Je implementován základní layout s hlavičkou, patičkou a navigací.
- **Příprava pro manažerské funkce:** V navigaci jsou odkazy a prázdné stránky pro budoucí funkce Cíle, Úkoly a Pochvaly.

## 4. Struktura Projektu

```
/
├── data/
│   └── organon.db       # Soubor s SQLite databází
├── public/
│   └── index.php        # Hlavní vstupní bod a router
├── src/
│   ├── Repository/
│   │   ├── DepartmentRepository.php
│   │   └── UserRepository.php
│   ├── Auth.php         # Logika pro autentizaci
│   └── Database.php     # Třída pro připojení k databázi
├── templates/
│   ├── action_items.php
│   ├── dashboard.php
│   ├── departments.php
│   ├── footer.php
│   ├── goals.php
│   ├── header.php
│   ├── login.php
│   ├── recognitions.php
│   └── users.php
├── nevrhresenitrojuhelnikctverec.txt
├── PROMPT_Organon_MVP.txt
├── seed.php             # Skript pro vložení výchozích dat
├── setup.php            # Skript pro vytvoření DB schématu
└── trojuhelnikctverec.txt
```

## 5. Jak Aplikaci Spustit

1.  Otevřete terminál v kořenovém adresáři projektu (`/Users/tonda/work/organomphp`).
2.  Spusťte vestavěný PHP server příkazem: `php -S localhost:8000 -t public`
3.  Otevřete v prohlížeči adresu: `http://localhost:8000`

## 6. Další Kroky

Logickým pokračováním je implementace klíčových manažerských funkcí dle MVP dokumentu:

1.  **Implementovat správu cílů (CRUD):** Vytvořit UI a backend pro zadávání, přiřazování a aktualizaci cílů.
2.  **Implementovat správu úkolů (CRUD):** Vytvořit UI a backend pro sledování akčních kroků a dohod.
3.  **Implementovat pochvaly:** Vytvořit UI a backend pro dávání a zobrazování uznání.

pojdme udelat imaginarni organizaci se 3mi oddelenimi dohromady 10 lidi i s CEO 
CEO (1)

├─ Oddělení 1: Obchod (3)
│  ├─ Head of Sales (manažer)
│  ├─ Sales Rep A
│  └─ Sales Rep B
│
├─ Oddělení 2: Produkt & IT (3)
│  ├─ Head of Product/IT (manažer)
│  ├─ Developer / Engineer
│  └─ QA / Analyst
│
└─ Oddělení 3: Finance & HR (3)
   ├─ Head of Finance/HR (manažer)
   ├─ Accountant
   └─ HR Generalist
