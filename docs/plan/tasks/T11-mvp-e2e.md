---
id: T11
title: MVP-E2E- & Barrierefreiheits-Suite
phase: 1
status: open
owner:
depends_on: [T08, T09, T10]
---

# T11 – MVP-E2E- & Barrierefreiheits-Suite

## Ziel

Eine Regressionssuite, die das Gesamtverhalten des MVP absichert: Editor → Speichern → Frontend, mit
axe-Prüfung, Progressive Enhancement, reduced-motion und Theme-Kompatibilität. Danach ist **0.1** releasebar.

## Umfang (Dateien)

- `tests/e2e/frontend.spec.js`, `tests/e2e/a11y.spec.js`, `tests/e2e/no-js.spec.js`, `tests/e2e/themes.spec.js`
- `tests/e2e/fixtures/*.html`: serialisierte Block-Markups für typische Konfigurationen
- `tests/e2e/utils/` (Erweiterungen)
- `docs/testing/manual-a11y-checklist.md`

## Umsetzung

1. **Fixtures** als serialisiertes Block-Markup. Ein Helfer legt per REST-API (`requestUtils.createPost`) einen
   Beitrag damit an. Konfigurationen: `hero` (1 pro Ansicht, overlay, dots), `carousel` (1/2/3, below, numbers,
   gap, peek), `progress` (progress, rewind), `single` (1 Slide), `rich` (Slides mit Bild, Button, Link, Video).
2. **a11y.spec.js**: Für jede Fixture `expectNoAxeViolations`. Zusätzlich gilt:
   - Wrapper: `aria-roledescription` gesetzt, `aria-label` nicht leer.
   - Tab-Reihenfolge bei `rich`: Pfeile → Track → interaktive Elemente nur des **sichtbaren** Slides →
     Pagination. Elemente in nicht sichtbaren Slides sind nicht erreichbar.
   - Alle Buttons haben einen zugänglichen Namen und sind ≥ 24×24 px (per `boundingBox`).
3. **no-js.spec.js** (`javaScriptEnabled: false`): Alle Slide-Inhalte sind sichtbar oder per Scroll erreichbar,
   es gibt keine sichtbaren Buttons, und der Track ist scrollbar (`scrollWidth > clientWidth`).
4. **frontend.spec.js**: reduced-motion (`page.emulateMedia({ reducedMotion: 'reduce' })`): Navigieren springt
   sofort (`scrollLeft` direkt nach dem Klick am Ziel). Es gibt keine Konsolenfehler bei allen Fixtures
   (`page.on('console')`) und keinen CLS > 0,01 beim Laden der Fixture `rich`
   (per `PerformanceObserver` `layout-shift` im Seitenkontext).
5. **themes.spec.js**: Mit `twentytwentyfive` und einem Classic Theme (`twentytwentyone`, über
   `requestUtils.activateTheme`) wird die Fixture `carousel` gerendert. Die Navigation funktioniert, und von
   jedem Theme wird ein Screenshot als Testartefakt abgelegt (kein visueller Diff-Zwang).
6. **manual-a11y-checklist.md**: Schritte für die manuelle Prüfung mit NVDA + Firefox, VoiceOver + Safari
   (macOS und iOS) und TalkBack, jeweils mit erwarteten Ansagen. Ergebnisse werden pro Release in einer
   Tabelle eingetragen.

## Akzeptanzkriterien

- [ ] Alle Specs laufen in CI grün (T04-Workflow `e2e.yml`).
- [ ] Laufzeit der gesamten E2E-Suite in CI < 8 Minuten.
- [ ] Mindestens ein Durchlauf der manuellen Checkliste mit NVDA oder VoiceOver ist dokumentiert. Kann der
  Agent das nicht selbst, wird es im PR als offene Aufgabe für einen Menschen markiert.
- [ ] Gefundene Fehler werden in den betroffenen Bereichen behoben oder als Issues mit Verweis auf den Task angelegt.

## Verifikation

```bash
npm run build && npm run test:e2e
```
