# Slovníček – Interaktivní jazykový slovník s překladačem

## Charakteristika
Webová aplikace určená pro učení cizích slovíček.

Obsahuje překladač postavený na MyMemory API, bezplatnou překladovou službu podporující mnoho
jazykových párů. Díky tomu může uživatel při přidávání nového slovíčka získat překlad automaticky,
bez nutnosti hledat jinde. Slovíčka jdou přidat i ručně.
Pro anglická slovíčka aplikace navíc využívá Free Dictionary API, která poskytuje definice.
Po vytvoření vlastního účtu si může uživatel slovíčka spravovat a procvičovat.

Aplikace umožňuje slovíčka procvičovat třemi způsoby:
– **Flashcards** – Klasické kartičky, otočí se karta a uživatel si zkontroluje svou odpověď sám.
– **Multiple choice** – Uživatel si vybere správný překlad ze čtyř možností.
– **Doplňování** – Uživatel napíše překlad slovíčka sám od sebe.

Aplikace sleduje statistiky úspěšnosti a používá metodu **Spaced repetition** (slovíčka, která uživateli
jdou hůř, se zobrazují častěji).

Aplikace umožňuje základní správu uživatelského účtu (hromadné mazání slovíček, smazání účtu).

## Použité technologie
– PHP + MySQL
– HTML, CSS, JavaScript
– [MyMemory API](https://mymemory.translated.net/)
– [Free Dictionary API](https://dictionaryapi.dev/)

## Externí služby
– **MyMemory Translate API** – Volně dostupná překladová služba, aplikace ji využívá pro překlad. Bezplatná, vyžaduje připojení k internetu.
– **Free Dictionary API** – Opět volně dostupná služba, poskytuje anglické definice. Bezplatná, vyžaduje připojení k internetu.

## Testovací účet
– Uživatelské jméno: **demo**
– Heslo: **Demo1234**
Testovací účet již obsahuje slovíčka.