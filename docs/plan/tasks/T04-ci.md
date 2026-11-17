---
id: T04
title: CI-Workflows (GitHub Actions)
phase: 0
status: open
owner:
depends_on: [T02, T03]
---

# T04 – CI-Workflows

## Ziel

Jeder PR wird automatisch auf Lint, Tests, Budget und wp.org-Konformität geprüft.

## Umfang (Dateien)

- `.github/workflows/ci.yml`
- `.github/workflows/e2e.yml`
- `.github/workflows/plugin-check.yml`
- `.github/dependabot.yml`
- `.github/pull_request_template.md`

## Umsetzung

1. **ci.yml** (Trigger `pull_request` und `push` auf `main`, `concurrency` pro Ref mit `cancel-in-progress`):
   - Job `lint-js`: Node aus `.nvmrc`, `npm ci`, `npm run lint`
   - Job `lint-php`: PHP 7.4, `composer install`, `composer lint`
   - Job `unit`: `npm run test:js` und `composer test:unit`, PHP-Matrix `7.4`, `8.1`, `8.3`
   - Job `build-size`: `npm run build`, `npm run size`; das Build als Artefakt hochladen (für andere Jobs)
   - Job `integration`: wp-env starten, `npm run test:php`. Matrix: WP `6.6` und `latest`
     (über `WP_ENV_CORE` bzw. eine überschriebene `.wp-env.override.json`), PHP `7.4` und `8.3`
2. **e2e.yml**: wp-env starten, `npx playwright install --with-deps chromium`, `npm run test:e2e`.
   Bei Fehlschlag den Playwright-Report als Artefakt hochladen.
3. **plugin-check.yml**: `npm run build` und `npm run plugin-zip`, ZIP entpacken, dann
   `wordpress/plugin-check-action@v1` auf das entpackte Verzeichnis. Der Job muss grün sein.
4. **dependabot.yml**: wöchentlich für `npm`, `composer` und `github-actions`; `@wordpress/*`-Updates gruppiert.
5. **pull_request_template.md**: Felder „Task-ID“, „Was / Warum“, „Akzeptanzkriterien“ (zum Abhaken),
   „Getestet mit“, „Screenshots (bei UI)“, „Vertragsänderung? (ja/nein, welche)“.
6. Actions auf Major-Version pinnen (`actions/checkout@v4` usw.). Secrets werden keine benötigt.

## Akzeptanzkriterien

- [ ] Auf einem Test-PR laufen alle Jobs grün durch.
- [ ] Ein absichtlich zu großes `view.js` (lokal getestet) lässt `build-size` fehlschlagen.
- [ ] Die Gesamtlaufzeit für einen PR liegt unter 15 Minuten.
- [ ] Die README zeigt ein CI-Badge.

## Verifikation

PR öffnen und die Checks beobachten. Workflows vorher lokal mit `actionlint` prüfen, falls verfügbar.
