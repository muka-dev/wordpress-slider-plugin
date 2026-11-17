---
id: T13
title: Fade-Effekt
phase: 2
status: open
owner:
depends_on: [T08, T09]
---

# T13 – Fade-Effekt

## Ziel

Überblenden statt Schieben, mit Progressive Enhancement: Ohne JS bleibt der Scroll-Snap-Track aktiv.

## Umfang (Dateien)

- `src/slider/style.scss` (bzw. Partial `_fade.scss`)
- `src/slider/view.js` (Abzweigungen für `effect === 'fade'`)
- `tests/e2e/fade.spec.js`

## Umsetzung

1. **CSS**, nur unter `.is-effect-fade.is-ready`:
   - Track: `display: grid; overflow: hidden; scroll-snap-type: none;`. Es gilt `perView = 1`.
   - Slides: `grid-area: 1 / 1; opacity: 0; visibility: hidden;`
     `transition: opacity var(--kadenz-transition-duration) ease, visibility 0s linear var(--kadenz-transition-duration);`
   - `.is-active`: `opacity: 1; visibility: visible; transition-delay: 0s; z-index: 1;`
   - Unter `prefers-reduced-motion: reduce`: `--kadenz-transition-duration: 0ms`.
   - Höhe: Weil alle Slides in einer Zelle liegen, bestimmt der höchste Slide die Höhe (bei `heightMode=auto`).
     Bei `aspect` und `min` passt das ohnehin.
   - Ohne `.is-ready` (kein JS) gilt der normale Slide-Track aus T08.
2. **JS**:
   - `setIndex` scrollt bei Fade nicht, sondern ruft nur `applyVisibility()` auf (setzt `is-active` und `inert`).
   - Kein Scroll-Sync-Listener im Fade-Modus.
   - Wischgesten im Fade-Modus: einfache Pointer-Swipe-Erkennung auf dem Track (`pointerdown`/`pointerup`,
     horizontale Distanz > 40 px und größer als die vertikale, RTL beachten) → `next`/`prev` mit `source: 'user'`.
     Kein Dragging-Feedback. Die Erkennung ist eine kleine Funktion in `lib/swipe.js` mit Jest-Test.
3. `maxIndex` bei Fade ist `count - 1` (steht schon in `lib/navigation.js`).

## Akzeptanzkriterien

- [ ] E2E: `effect: fade` → nach „Next“ hat nur Slide 2 `is-active`, ist sichtbar (`toBeVisible`), und alle
  anderen sind `hidden`/`inert`.
- [ ] E2E: ohne JS → horizontal scrollbarer Track mit allen Slides.
- [ ] E2E (Mobile): Swipe nach links → nächster Slide; vertikales Scrollen der Seite bleibt möglich (`touch-action: pan-y`).
- [ ] reduced-motion → keine Transition (berechnete `transition-duration` ist `0s`).
- [ ] Mit Autoplay (T12, falls schon gemerged) funktioniert Fade ebenfalls.
- [ ] Budget eingehalten.

## Verifikation

```bash
npm run build && npm run size && npm run test:js && npm run test:e2e -- fade
```
