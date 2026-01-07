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

---

## 7. Stav po 1. dni vývoje (dne 2026-01-07 21:00:00)

**Dosažený pokrok:**

*   **Správa uživatelů a oddělení:** Původní CRUD rozšířen o email, funkční přiřazení uživatelů k oddělením a správu manažerů. Implementována hierarchická logika pro oprávnění.
*   **Organizační diagram:** Implementována vizualizace organizační struktury formou stylizovaných HTML karet na Dashboardu.
*   **Správa cílů (Goals):** Plně funkční CRUD operace pro cíle. Implementován hierarchický model oprávnění (nadřízený -> podřízený, admin override, self-assignment). UI filtruje rozbalovací seznam řešitelů.
*   **Správa úkolů (Action Items):** Plně funkční CRUD operace pro úkoly. Stejný hierarchický model oprávnění jako u cílů. UI filtruje rozbalovací seznam majitelů.
*   **Pochvaly (Recognitions):** Plně funkční CRUD operace pro pochvaly. Otevřený model oprávnění (každý každému), s omezením editace/mazání na zadavatele.
*   **Dashboard rozšíření:** Zobrazení posledních pochval na Dashboardu.
*   **"Můj tým" a "Detail podřízeného":** Implementovány stránky pro manažery pro přehled a detailní zobrazení cílů, úkolů a pochval pro své podřízené.
*   **Rozlišení Cílů vs. Úkolů v UI:** Upraveny popisky na stránkách "Cíle" a "Úkoly" pro jasnější rozlišení strategických cílů a taktických úkolů/dohod.

**Další kroky dle `upresneni.txt` a diskuse (priorita pro další den vývoje):**

Abychom podpořili efektivní hodnocení a reporting manažerských interakcí, je klíčové:

1.  **Rozšíření modelu Cílů:** Doplnit datový model cíle o `metriky` (typ, cílová hodnota), `váhu`, `pravidlo vyhodnocení` a `zdroj dat`.
2.  **Implementace Reportingu k Cílům:** Vytvořit entitu `Report entry` pro pravidelné (např. týdenní) záznamy o plnění cílů, včetně hodnot a komentářů "proč" (pro zaznamenání objektivních důvodů).
3.  **Implementace modulu 1:1 rozhovorů:** Vytvořit systém pro plánování a záznamy z 1:1 schůzek, včetně agendy, překážek, rozvoje a závazků (kde se budou propojovat úkoly).

---

## 8. Stav po následném vývoji (dne 2026-01-08 21:30:00)

**Dosažený pokrok:**

*   **Rozšířený model cílů:**
    *   Rozšířena struktura tabulky `goals` o `metric_type`, `target_value`, `weight`, `evaluation_rule`, a `data_source`.
    *   Aktualizován `GoalRepository` a `templates/goals.php` (formulář včetně vysvětlivek) pro správu těchto nových atributů cíle.
    *   Integrována nová pole do akce `save_goal` v `public/index.php`.
*   **Modul pro reporting cílů:**
    *   Implementována tabulka `goal_reports` v `setup.php` pro sledování průběhu (hodnota, komentář, plán, riziko).
    *   Vytvořen `GoalReportRepository` pro CRUD operace s reporty.
    *   Přidána dedikovaná stránka `templates/goal_report.php` pro zobrazení a odesílání reportů.
    *   Integrován `GoalReportRepository` a akce (`save_goal_report`, `delete_goal_report`) do `public/index.php`.
    *   Přidán odkaz "Reporty" k cílům v `templates/goals.php`.
*   **Vylepšení interakce s podřízenými:**
    *   Přidána tlačítka rychlých akcí ("Přidat cíl", "Přidat úkol", "Udělit pochvalu") na `subordinate_detail.php` pro zefektivnění řízení během 1:1 schůzek. Tyto tlačítka automaticky předvyplňují ID cílového uživatele.
    *   Aktualizovány formuláře cílů, úkolů a pochval tak, aby přijímaly předvyplněná ID uživatelů z GET parametrů.
*   **Příprava pro poznámky z 1:1:**
    *   Přidána definice tabulky `one_on_one_notes` do `setup.php`, což připravuje půdu pro strukturované poznámky ze schůzek 1:1.

**Další kroky dle `upresneni.txt` (priorita pro další den vývoje):**

Nyní je dokončena fáze rozšíření modelu cílů a implementace reportingu. Dalším logickým krokem je:

1.  **Dokončení modulu 1:1 rozhovorů:** Implementovat `OneOnOneNoteRepository`, akce pro správu poznámek v `public/index.php` a UI pro přidávání/zobrazování poznámek na stránce `subordinate_detail.php`. Tím získáme strukturovaný prostor pro plánování a záznamy z 1:1 schůzek.

---

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
