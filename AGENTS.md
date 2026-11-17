# Leitfaden für Agenten und Mitwirkende

Dieses Repo ist das WordPress-Plugin **Kadenz Slider**: ein werbefreier, Gutenberg-nativer,
barrierefreier Slider. Das Repo-Root **ist** das Plugin-Root.

## Pflichtlektüre vor jeder Aufgabe

1. [`docs/plan/README.md`](docs/plan/README.md): Ablauf, Task-Liste, Definition of Done.
2. [`docs/plan/contracts.md`](docs/plan/contracts.md): **verbindliche** Namen, Attribute, Markup, Store, CSS-API.
   Weicht etwas davon ab, gilt `contracts.md`. Ändern darfst du sie nur in einem eigenen PR mit Begründung.
3. Deine Task-Datei in [`docs/plan/tasks/`](docs/plan/tasks/).
4. Bei Bedarf als Hintergrund: [`docs/system-design.md`](docs/system-design.md).

## Unverhandelbare Prinzipien

- **Keine Werbung, kein Upsell, keine Telemetrie, keine externen Requests** (auch keine Google Fonts, CDNs, Gravatare).
- **Keine Admin-Seite, keine `wp_options`-Einträge, keine eigenen Tabellen, kein Custom Post Type.**
- **Kein jQuery, keine Slider-Bibliotheken** (Swiper, Splide, Slick, Glide, Flickity …).
- Frontend-Budget: `build/slider/view.js` ≤ 6 KB gzip, Frontend-CSS ≤ 4 KB gzip. CI erzwingt das.
- Ohne JavaScript muss der Slider benutzbar bleiben (Scroll-Snap-Fallback).
- WCAG 2.2 AA. Jede UI-Änderung braucht einen axe-Check in E2E.

## Namenskonventionen (Kurzfassung, Details in contracts.md)

| Was | Wert |
|---|---|
| Plugin-Slug / Text-Domain | `kadenz-slider` |
| Blöcke | `kadenz/slider`, `kadenz/slide` |
| PHP-Namespace | `Kadenz\Slider` |
| PHP-Funktions-/Hook-Präfix | `kadenz_slider_` |
| PHP-Konstanten | `KADENZ_SLIDER_` |
| CSS-Klassen | `.kadenz-slider`, `.kadenz-slider__*`, `.kadenz-slide` (BEM) |
| CSS Custom Properties (öffentlich) | `--kadenz-*`; intern `--_kadenz-*` |
| Interactivity-Store | `kadenz/slider` |
| DOM-Events | `kadenz:*` |

## Technische Leitplanken

- **WordPress ≥ 6.6, PHP ≥ 7.4.** Also in PHP **kein** `match`, keine Union-Types, kein Nullsafe-Operator `?->`,
  keine Named Arguments, keine `readonly`-Properties, keine Enums.
- Build: `@wordpress/scripts` mit `--experimental-modules` (für `viewScriptModule`). Keine weiteren Bundler.
- JavaScript: ES-Module, kein TypeScript, Typen per JSDoc. Editor-Code nutzt `@wordpress/*`-Pakete.
- Frontend-JS **nur** über die Interactivity API (`@wordpress/interactivity`), keine React-Hydration.
- Strings im Frontend-JS **nicht** über `@wordpress/i18n`, sondern serverseitig übersetzt via
  `wp_interactivity_state()` übergeben (siehe contracts.md, i18n).
- HTML in PHP über `WP_HTML_Tag_Processor` / `get_block_wrapper_attributes()` / `esc_*` erzeugen und verändern.
  Keine Regex-Manipulation von HTML.
- CSS: niedrige Spezifität (`:where()` für Defaults), damit Themes leicht überschreiben können. Logische
  Properties (`inline-start` statt `left`) für RTL.
- Composer nur für Dev-Werkzeuge. Zur Laufzeit gibt es keinen Composer-Autoloader: Dateien werden in
  `kadenz-slider.php` per `require_once` geladen.

## Befehle

```bash
npm ci                 # Abhängigkeiten
npm run build          # Produktions-Build nach build/
npm run start          # Watch-Modus
npm run lint           # JS + CSS + package.json
composer install
composer lint          # PHPCS (WordPress Coding Standards)
composer test:unit     # PHPUnit ohne WordPress (reine Helfer)
npm run env:start      # wp-env (Docker): http://localhost:8888, admin/password
npm run test:php       # PHPUnit-Integrationstests in wp-env
npm run test:js        # Jest
npm run test:e2e       # Playwright gegen wp-env
npm run size           # size-limit-Budget
```

Ohne Docker kannst du WordPress Playground nutzen:
`npx @wp-playground/cli@latest server --mount=.:/wordpress/wp-content/plugins/kadenz-slider --login`
und dann für E2E-Tests `WP_BASE_URL` auf die ausgegebene URL setzen.
Einige dieser Skripte entstehen erst mit T02 bzw. T03. Gibt es ein Skript noch nicht, gehört es
zur Aufgabe, die es einführt.

## Arbeitsablauf

- Ein Task = ein Branch = ein PR. Branch: `task/<ID>-<kurzname>`, z. B. `task/T05-slide-block`.
- Setze vor Beginn `status: in-progress` und `owner` im Frontmatter deiner Task-Datei und am Ende `status: done`.
  Der Statuswechsel gehört in den PR dieses Tasks.
- Arbeite nur an den Dateien, die deine Task-Datei nennt. Brauchst du mehr, begründe es im PR.
- Vor dem PR: `npm run lint`, `composer lint`, alle Tests deines Bereichs und `npm run build` müssen grün sein.
- Commit-Messages auf Englisch im Imperativ („Add slide block render callback“). Doku ist auf Deutsch,
  Code-Kommentare und Bezeichner auf Englisch. UI-Strings sind englische Quellstrings und übersetzbar.
