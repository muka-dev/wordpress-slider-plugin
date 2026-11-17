---
id: T05
title: Attribut-Normalisierung & CSS-Variablen (PHP)
phase: 1
status: review
owner: subagent-sonnet
depends_on: [T02]
---

# T05 – Attribut-Normalisierung & CSS-Variablen

## Ziel

Die reine, vollständig unit-getestete PHP-Logik, die beliebige (auch manipulierte) Block-Attribute in gültige
Werte überführt und daraus das Inline-`style` des Sliders erzeugt. Daran hängt die Sicherheit des Renderings.

## Umfang (Dateien)

- `includes/class-attributes.php`: `Kadenz\Slider\Attributes`
- `includes/class-style-vars.php`: `Kadenz\Slider\Style_Vars`
- `kadenz-slider.php` (`require_once` ergänzen)
- `tests/php/unit/AttributesTest.php`, `tests/php/unit/StyleVarsTest.php`

## Umsetzung

1. `Attributes::defaults(): array` liefert die Defaults aus [contracts.md §3.2](../contracts.md#32-attribute),
   gefiltert durch `apply_filters( 'kadenz_slider_default_attributes', $defaults )`. Das Filterergebnis wird
   selbst wieder normalisiert, damit kaputte Filter nichts kaputt machen können.
2. `Attributes::normalize( array $raw ): array`: alle Regeln aus §3.2 und §3.3. Dabei gilt:
   - Unbekannte Schlüssel werden verworfen.
   - Typen werden hart konvertiert: Booleans per `(bool)` nur, wenn der Wert `bool` ist, sonst Default.
     Zahlen per `(int)` nur, wenn `is_numeric`.
   - `perView`: siehe Vererbungsregel in §3.2; bei `effect=fade` werden alle drei Werte auf 1 gesetzt.
   - `label`: `sanitize_text_field`. In Unit-Tests per Brain Monkey stubben (`trim( strip_tags() )`).
3. `Attributes::css_length( string $value, bool $allow_vh = true ): ?string`: öffentliche Helfermethode nach
   §3.3. Liefert den normalisierten CSS-Wert (inkl. Preset-Umschreibung) oder `null`.
4. `Style_Vars::from_attributes( array $normalized ): string` liefert einen String wie
   `--kadenz-per-view-base:1;--kadenz-gap:2rem;` nach [contracts.md §7.1](../contracts.md#71-custom-properties).
   - Ausgegeben werden nur Werte, die vom CSS-Default abweichen (CSS-Defaults: per-view 1, gap 1.5rem,
     peek 0px, aspect-ratio 16/9, min-height leer, interval 6000ms).
   - `aspect-ratio` wird nur bei `heightMode=aspect` ausgegeben, `min-height` nur bei `heightMode=min`,
     `interval` nur bei `autoplay=true`.
   - Feste, deterministische Reihenfolge (für Snapshot-Tests).
   - Weil alle Werte durch `normalize` gelaufen sind, kann der String nichts außer erlaubten Zeichen
     enthalten. Trotzdem gibt es eine abschließende Prüfung gegen `[;{}<>"']` und `url(`; schlägt sie an,
     wird der Eintrag verworfen.

## Testfälle (mindestens)

- Leeres Array → exakt `defaults()`.
- `perView: {"base": 0, "md": 99}` → `base 1`, `md 6`, `lg 6`.
- `perView: {"base": 2}` → `md 2`, `lg 2`.
- `perView` als String oder `null` → Default.
- `effect: "fade"` mit `perView.lg = 3` → alle 1.
- `interval: 100` → 2000; `"abc"` → 6000; `99999` → 30000.
- `gap: "2rem"` ok; `gap: "2rem;color:red"` → Default; `gap: "var:preset|spacing|40"` →
  `var(--wp--preset--spacing--40)`; `gap: "var:preset|spacing|4 0"` → Default.
- `peek: "10vh"` → Default.
- `aspectRatio: "4/3"` ok, `"4 / 3"` ok, `"4:3"` → Default, `"expression(1)"` → Default.
- `pagination: "foo"` → `dots`.
- `label: "<b>Hi</b>"` → `Hi`.
- Defaults-Filter liefert Unsinn (`'interval' => -5`) → normalisiert (2000).
- Style_Vars: Defaults → leerer String; `perView {base:1, md:2, lg:3}` → genau zwei Variablen (md, lg);
  `heightMode=auto` → kein aspect-ratio.

## Akzeptanzkriterien

- [ ] Alle Testfälle oben als PHPUnit-Unit-Tests (Data Provider) vorhanden und grün.
- [ ] Zeilenabdeckung der beiden Klassen ≥ 95 % (`--coverage-text`, falls Xdebug/PCOV verfügbar).
- [ ] PHPCS grün, PHP-7.4-kompatibel.
- [ ] Die Klassen rufen außer `apply_filters` und `sanitize_text_field` keine WordPress-Funktionen auf.

## Verifikation

```bash
composer test:unit && composer lint
```
