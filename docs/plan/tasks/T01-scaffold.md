---
id: T01
title: Projekt-Scaffold & Build
phase: 0
status: review
owner: subagent-sonnet
depends_on: []
---

# T01 – Projekt-Scaffold & Build

## Ziel

Ein installierbares, leeres Plugin, das zwei Blöcke registriert und mit `@wordpress/scripts` baut.
Alle folgenden Tasks setzen auf dieser Struktur auf.

## Umfang (Dateien)

- `kadenz-slider.php`, `includes/class-plugin.php`
- `package.json`, `package-lock.json`, `.nvmrc` (`22`), `.editorconfig`, `.gitignore`, `.distignore`
- `.wp-env.json`
- `src/slider/{block.json,index.js,edit.js,save.js,render.php,view.js,style.scss,editor.scss}` (Platzhalter)
- `src/slide/{block.json,index.js,edit.js,save.js,render.php,style.scss}` (Platzhalter)
- `readme.txt` (Gerüst mit wp.org-Header)

## Umsetzung

1. **Plugin-Header** in `kadenz-slider.php` nach [contracts.md §2](../contracts.md#2-konstanten--bootstrap-intern),
   mit `defined( 'ABSPATH' ) || exit;`, den Konstanten und `require_once` für `includes/class-plugin.php`.
   Dazu `Kadenz\Slider\Plugin::init()` auf `plugins_loaded`.
2. `Plugin::init()` hängt `register_blocks()` an `init`, das `register_block_type( KADENZ_SLIDER_DIR . 'build/slider' )`
   und `… 'build/slide'` aufruft. Fehlt `build/`, gibt es keinen Fatal Error: Der Aufruf wird übersprungen
   und unter `WP_DEBUG` per `_doing_it_wrong` gemeldet.
3. **package.json**
   - devDependency: `@wordpress/scripts` (aktuelle Version). Laufzeitabhängigkeiten gibt es keine.
     `@wordpress/*`-Pakete sind Externals und kommen über WordPress.
   - Skripte:
     ```json
     "build": "wp-scripts build --experimental-modules",
     "start": "wp-scripts start --experimental-modules",
     "lint": "npm run lint:js && npm run lint:css && npm run lint:pkg",
     "lint:js": "wp-scripts lint-js src tests",
     "lint:css": "wp-scripts lint-style \"src/**/*.scss\"",
     "lint:pkg": "wp-scripts lint-pkg-json",
     "format": "wp-scripts format",
     "plugin-zip": "wp-scripts plugin-zip",
     "env:start": "wp-env start",
     "env:stop": "wp-env stop"
     ```
   - devDependency `@wordpress/env`.
   - `"files"`-Feld für `plugin-zip`: `kadenz-slider.php`, `includes`, `build`, `languages`, `readme.txt`, `LICENSE`.
4. **block.json-Dateien** exakt mit den Feldern aus [contracts.md §3.1 und §4](../contracts.md). Die Attribute
   aus §3.2 sind vollständig mit Typ und Default eingetragen, auch wenn sie noch nicht genutzt werden.
5. **Platzhalter-Implementierung**:
   - Slider: `edit.js` rendert `useBlockProps` + `useInnerBlocksProps` mit `allowedBlocks` aus block.json und
     einem Template aus zwei leeren Slides. `save.js` gibt `<InnerBlocks.Content />` zurück.
     `render.php` gibt vorerst `<div {wrapper attrs}>$content</div>` aus.
   - Slide: wie in [contracts.md §4](../contracts.md#4-block-kadenzslide-öffentlich), `edit.js` mit
     `useInnerBlocksProps` und einem Template `[['core/paragraph', { placeholder: 'Slide content…' }]]`.
   - `view.js`: `import { store } from '@wordpress/interactivity'; store( 'kadenz/slider', {} );`
6. **.wp-env.json**: `"core": null` (aktuelle Version), `"phpVersion": "7.4"`, `"plugins": ["."]`,
   `"config": { "WP_DEBUG": true, "SCRIPT_DEBUG": true }`.
7. **.gitignore**: `node_modules/`, `build/`, `vendor/`, `*.zip`, `.wp-env/`, `test-results/`, `playwright-report/`, `artifacts/`.
8. **readme.txt**: Header (`Contributors`, `Tags: slider, carousel, block, accessibility, gallery`,
   `Requires at least: 6.6`, `Tested up to:` aktuelle WP-Version, `Requires PHP: 7.4`, `Stable tag: 0.1.0`,
   `License: GPLv2 or later`) und Kurzbeschreibung. Die Inhalte füllt T20.

## Nicht im Umfang

Echte Slider-Logik, Styles, Tests, CI.

## Akzeptanzkriterien

- [ ] `npm ci && npm run build` erzeugt `build/slider/` (inkl. `view.js` als Modul, `render.php`) und `build/slide/`.
- [ ] `npm run lint` ist grün.
- [ ] In wp-env (oder Playground) lässt sich das Plugin ohne Notices aktivieren, und PHP 7.4 erzeugt keine Fehler.
- [ ] Im Editor taucht „Slider“ im Inserter auf. Er lässt sich einfügen und enthält zwei Slides. „Slide“
  erscheint nicht auf oberster Ebene im Inserter.
- [ ] Eine gespeicherte Seite mit Slider rendert im Frontend die Slide-Inhalte, und die Konsole bleibt fehlerfrei.
- [ ] `npm run plugin-zip` erzeugt ein ZIP ohne `src/`, `node_modules/` und `tests/`.

## Verifikation

```bash
npm ci && npm run build && npm run lint
npm run env:start   # oder Playground, siehe AGENTS.md
npm run plugin-zip && unzip -l kadenz-slider.zip
```
