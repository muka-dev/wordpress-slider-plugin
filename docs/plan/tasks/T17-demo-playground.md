---
id: T17
title: Demo, Playground-Blueprint, Lighthouse CI
phase: 3
status: open
owner:
depends_on: [T11, T15]
---

# T17 – Demo, Playground-Blueprint, Lighthouse CI

## Ziel

Jeder kann das Plugin mit einem Klick im Browser ausprobieren (WordPress Playground), ohne Installation.
Gleichzeitig dient die Demo-Seite als Messobjekt für Performance-Regressionstests.

## Umfang (Dateien)

- `.wordpress-org/blueprints/blueprint.json` (wp.org nutzt diesen Pfad für den „Live Preview“-Button)
- `demo/demo-content.xml` (WXR) **oder** `demo/demo-page.html` (Block-Markup), dazu `demo/media/*` (eigene, freie Bilder)
- `.github/workflows/lighthouse.yml`, `lighthouserc.json`
- `README.md` (Button „Try it in Playground“)

## Umsetzung

1. **Demo-Inhalt**: eine Seite „Kadenz Slider Demo“ mit allen fünf Variationen und je einem kurzen Absatz
   davor, der die Variation erklärt. Bilder: **eigene** oder CC0-Bilder, max. 6 Stück, je ≤ 150 KB (WebP),
   abgelegt in `demo/media/`, mit Herkunftsangabe in `demo/media/CREDITS.md`. Kein Hotlinking.
2. **Blueprint**: aktuelle WP-Version, PHP 8.3, Plugin aus dem GitHub-Release-ZIP (bis zum ersten Release aus
   dem Build-Artefakt via `git:directory`-Resource, sofern möglich), Theme Twenty Twenty-Five, Demo-Inhalt
   importieren, die Demo-Seite als Startseite, `landingPage` auf die Demo-Seite, Login als Admin.
3. **README-Button**: `https://playground.wordpress.net/?blueprint-url=<raw-URL des Blueprints>`.
4. **Lighthouse CI**: im Workflow wp-env starten, Demo-Inhalt importieren (WP-CLI `wp import` bzw. ein
   `wp eval-file`-Skript, das die Seite anlegt), `@lhci/cli` gegen die Demo-Seite laufen lassen (Mobile, 3 Runs).
   Assertions (`lighthouserc.json`):
   - `categories:accessibility` ≥ 1.0
   - `categories:performance` ≥ 0.9 (Warnung, kein Fehler: wp-env ist nicht repräsentativ)
   - `cumulative-layout-shift` ≤ 0.02 (Fehler)
   - `total-blocking-time` ≤ 150 ms (Warnung)
   - `unused-javascript`: Slider-Dateien nicht gelistet (Warnung)
   Berichte als Artefakt hochladen, kein Upload zu externen Diensten (`upload.target: filesystem`).

## Akzeptanzkriterien

- [ ] Der Playground-Link öffnet direkt die Demo mit funktionierendem Plugin.
- [ ] Der Lighthouse-Workflow läuft auf PRs gegen `main` grün.
- [ ] Die Bildrechte sind dokumentiert.

## Verifikation

Blueprint lokal testen: `npx @wp-playground/cli@latest server --blueprint=.wordpress-org/blueprints/blueprint.json`.
