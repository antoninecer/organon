# Organon - Organizační Operační Systém (PHP MVP)

Tento projekt představuje MVP (Minimum Viable Product) lehkého a přenositelného „organizačního operačního systému“ v PHP, inspirovaného konceptem „Trojúhelník-Čtverec“ od Pavla Procházky. Cílem je spravovat organizační strukturu, uživatelské role a klíčové manažerské interakce pro efektivní řízení lidí.

## Klíčové koncepty

Projekt vychází z principů definovaných v souborech `trojuhelnikctverec.txt` (komplexní vize) a `nevrhresenitrojuhelnikctverec.txt` (MVP – minimální životaschopný produkt). Jeho jádrem je podpora:
*   **Cílů:** Měřitelné cíle pro jednotlivce s detailním trackingem.
*   **Reportingu:** Průběžné sledování plnění cílů.
*   **Manažerského rytmu:** Podpora pro 1:1 schůzky, úkoly a pochvaly.

## Použité Technologie

*   **Jazyk:** PHP 8.3+
*   **Databáze:** SQLite (souborová, `data/organon.db`)
*   **Stylování:** Pico.css (minimalistické a responzivní UI)

## Instalace a Spuštění

Pro spuštění aplikace postupujte podle následujících kroků:

1.  **Naklonujte repozitář:**
    ```bash
    git clone [URL_VAŠEHO_REPOZITÁŘE] organonphp
    cd organonphp
    ```

2.  **Inicializace databáze:**
    *   Odstraňte stávající databázi (pokud existuje a chcete začít znova):
        ```bash
        rm data/organon.db
        ```
    *   Vytvořte schéma databáze:
        ```bash
        php setup.php
        ```
    *   Naplňte databázi výchozími daty (organizační struktura, uživatelé):
        ```bash
        php seed.php
        ```

3.  **Spusťte PHP vývojový server:**
    ```bash
    php -S localhost:8000 -t public
    ```

4.  **Otevřete aplikaci v prohlížeči:**
    ```
    http://localhost:8000
    ```

5.  **Přihlašovací údaje:**
    *   **Uživatelské jméno:** `admin` (nebo `alice`, `bara` atd., dle `seed.php`)
    *   **Heslo:** `password` (pro všechny výchozí uživatele)

## Aktuální Funkcionality

Aplikace v aktuálním stavu implementuje následující moduly:

*   **Autentizace:** Přihlašování/odhlášování uživatelů.
*   **Správa uživatelů (CRUD):** Vytváření, čtení, úpravy a mazání uživatelů s přiřazením do oddělení a manažerů.
*   **Správa oddělení (CRUD):** Vytváření a úpravy organizační hierarchie.
*   **Dashboard:** Hlavní stránka s vizualizací organizační struktury (karty oddělení) a přehledem posledních pochval.
*   **Můj tým:** Stránka pro manažery s přehledem jejich podřízených (přímých i nepřímých).
*   **Detail podřízeného:** Komplexní stránka pro manažery s detaily o konkrétním podřízeném (cíle, úkoly, pochvaly) a tlačítky pro rychlé zadávání.
*   **Správa strategických cílů:**
    *   CRUD pro cíle s rozšířeným datovým modelem (metrika, cílová hodnota, váha, pravidlo hodnocení, zdroj dat).
    *   Hierarchická kontrola oprávnění pro přiřazování cílů.
    *   Reportingový modul k cílům pro průběžné záznamy o plnění ("proč", "co udělám příště", riziko).
*   **Správa taktických úkolů:**
    *   CRUD pro úkoly (akční položky).
    *   Hierarchická kontrola oprávnění pro přiřazování úkolů.
*   **Správa pochval:**
    *   CRUD pro udílení nefinančního uznání.
    *   Otevřená oprávnění (každý může pochválit kohokoli).

## Další Kroky / Chybějící Funkcionality

Dle plánu MVP a dokumentace `upresneni.txt` jsou prioritní následující kroky:

1.  **Dokončení modulu 1:1 rozhovorů:**
    *   Implementovat `OneOnOneNoteRepository`.
    *   Akce pro správu poznámek z 1:1 schůzek v `public/index.php`.
    *   UI pro přidávání/zobrazování poznámek na stránce `subordinate_detail.php`. (Datová struktura je již připravena v `setup.php`).
2.  **Implementace bonusových pravidel a výpočtu bonusů** (propojení odměn s plněním cílů).
3.  **Implementace modulu Porad** (týdenní operativa, měsíční taktika).
4.  **Implementace modulu pro Roční hodnocení.**
5.  **Implementace notifikací** (např. chybějící reporting, zpožděné 1:1).
6.  **Pokročilé reporty a dashboardy** pro management.
7.  **Integrace s externími systémy** (AD, API).

---

_Tento README.md byl vygenerován agentem Gemini CLI na základě průběžného vývoje projektu Organon._