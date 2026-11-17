---
id: T03
title: "JS-Tooling: Jest, Playwright, size-limit"
phase: 0
status: open
owner:
depends_on: [T01]
---

# T03 – JS-Tooling

## Ziel

Infrastruktur für JS-Unit-Tests, E2E-Tests im Editor und im Frontend (inklusive axe) und ein hartes
Größenbudget.

## Umfang (Dateien)

- `package.json` (devDependencies, Skripte), `package-lock.json`
- `jest.config.js` (falls die Defaults aus `wp-scripts test-unit-js` nicht reichen)
- `playwright.config.js`
- `tests/js/smoke.test.js`
- `tests/e2e/smoke.spec.js`, `tests/e2e/utils/` (Hilfsfunktionen, s. u.)
- `.size-limit.json`

## Umsetzung

1. **Jest** über `wp-scripts test-unit-js`, Testpfad `tests/js`. Skript `"test:js": "wp-scripts test-unit-js tests/js"`.
2. **Playwright**: devDependencies `@playwright/test`, `@wordpress/e2e-test-utils-playwright`, `@axe-core/playwright`.
   - `playwright.config.js` auf Basis der Konfiguration aus `@wordpress/scripts` (`wp-scripts test-playwright`),
     `baseURL` aus `WP_BASE_URL` (Default `http://localhost:8889`, die Test-Instanz von wp-env).
   - Projekte: `chromium` (Desktop) und `mobile` (Pixel 7, Chromium). Weitere Browser optional, nicht in CI.
   - Skript: `"test:e2e": "wp-scripts test-playwright --trace off"`.
3. **Hilfsfunktionen** in `tests/e2e/utils/`:
   - `insertSlider( editor, { slides, attributes } )`: fügt einen Slider mit n Slides ein, jeder mit einem
     Absatz „Slide n“.
   - `publishAndVisit( editor, page )`: veröffentlicht und öffnet das Frontend.
   - `expectNoAxeViolations( page, selector = '.kadenz-slider' )`: AxeBuilder mit Tags
     `wcag2a, wcag2aa, wcag21a, wcag21aa, wcag22aa`, beschränkt auf den Selektor.
4. **Smoke-Tests**:
   - Jest: trivialer Test, damit die Pipeline steht.
   - E2E: Slider im Editor einfügen, veröffentlichen, im Frontend sind 2 Elemente `.wp-block-kadenz-slide` sichtbar.
5. **size-limit**: devDependencies `size-limit` und `@size-limit/file`. Einträge:
   - `build/slider/view.js`: Limit `6 KB` (gzip)
   - `build/slider/style-index.css` + `build/slide/style-index.css`: Limit `4 KB` (gzip), als ein Eintrag mit zwei Pfaden
   Skript: `"size": "size-limit"`.
6. ESLint so konfigurieren, dass es `tests/e2e` mit den Playwright-Globals akzeptiert (über die Overrides
   von `@wordpress/eslint-plugin`).

## Akzeptanzkriterien

- [ ] `npm run test:js` ist grün.
- [ ] `npm run test:e2e` ist gegen wp-env grün (Smoke-Test).
- [ ] `npm run size` nach `npm run build` ist grün und zeigt beide Einträge.
- [ ] `npm run lint` ist grün, auch für `tests/`.
- [ ] Die Hilfsfunktionen haben JSDoc, damit spätere Tasks sie ohne Lesen des Codes nutzen können.

## Verifikation

```bash
npm ci && npm run build && npm run size && npm run test:js
npm run env:start && npm run test:e2e
```
