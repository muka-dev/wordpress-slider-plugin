---
id: T09
title: "Interactivity-Store: Navigation"
phase: 1
status: open
owner:
depends_on: [T03, T07]
---

# T09 – Interactivity-Store: Navigation

## Ziel

Das Frontend-Verhalten ohne Autoplay und ohne Fade: Pfeile, Pagination, Tastatur, Synchronisation mit
nativem Wischen, Sichtbarkeit (`inert`), Live-Ansagen und DOM-Events. Budget: `view.js` ≤ 6 KB gzip
**inklusive** späterem Autoplay und Fade. Ziel für diesen Task sind deshalb ≤ 3,5 KB.

## Umfang (Dateien)

- `src/slider/view.js`
- `src/slider/lib/navigation.js`: reine Funktionen
- `src/slider/lib/dom.js`: kleine DOM-Helfer (Scroll-Ziel berechnen, Snap ermitteln)
- `tests/js/navigation.test.js`
- `tests/e2e/navigation.spec.js`

## Umsetzung

Verbindlich sind [contracts.md §6](../contracts.md#6-interactivity-api-intern-außer-64). Konkret:

1. **`lib/navigation.js`** (rein, ohne DOM):
   - `maxIndex( { count, perView, effect } )`
   - `clampIndex( i, ctx )`
   - `nextIndex( ctx )`, `prevIndex( ctx )`: berücksichtigen `endBehavior` (`stop` | `rewind`)
   - `nearestIndex( offsets: number[], scrollPos: number )`: Index des Snap-Punkts, der `scrollPos` am nächsten liegt
   - `formatAnnouncement( template, index, count )`
2. **`callbacks.init`**:
   - Wurzel = `getElement().ref`. Track und Slides (`:scope > .kadenz-slider__track > .kadenz-slide`) in der WeakMap ablegen.
   - `perView` initial und per `ResizeObserver` (auf der Wurzel) aus `--_kadenz-per-view` lesen
     (`parseInt`, Fallback 1). Ändert sich `perView`, wird `index` auf `maxIndex` geclampt.
   - Scroll-Sync: `scrollend`-Listener, sonst entprellter `scroll`-Listener (120 ms). Daraus `nearestIndex`
     berechnen. Unterscheidet sich das Ergebnis vom aktuellen Index → `setIndex( i, 'scroll' )`.
     Ein laufendes programmatisches Scrollen (eigenes Flag) löst **keine** doppelten `change`-Events aus.
   - Eingehende Events `kadenz:goto`, `kadenz:next` und `kadenz:prev` auf der Wurzel registrieren (`source: 'api'`).
   - `applyVisibility()` aufrufen, `context.ready = true`, `kadenz:ready` dispatchen.
   - Cleanup-Funktion zurückgeben, die Observer und Listener entfernt.
3. **`setIndex( ctx, i, source )`** (interne Funktion):
   - Clampt, bricht ab, wenn sich nichts ändert, setzt `context.index`, scrollt den Track nach §6.5
     (`behavior` je nach `prefers-reduced-motion`), außer bei `source === 'scroll'`.
   - `applyVisibility()`: `is-active` und `inert` nach §6.5.
   - Bei `source === 'user'` wird `context.announcement` gesetzt.
   - Dispatcht `kadenz:change`.
4. **Actions** `next`, `prev`, `goToPage`, `onKeydown` nach §6.3. Für die Tastatur RTL über
   `getComputedStyle( root ).direction` ermitteln. Ein `onKeydown` außerhalb des Tracks (z. B. auf einem
   Button) tut nichts.
5. **Getter** `maxIndex`, `isAtStart`, `isAtEnd`, `isCurrentPage`, `isPageHidden`, `progressSize` nach §6.3.
   Getter lesen den Kontext über `getContext()`.
6. **Fokus-Management**: Wenn der fokussierte Slide durch `inert` unerreichbar würde (z. B. Tab in einem
   Slide und dann Pfeil-Klick), wandert der Fokus nicht automatisch. Ausnahme: Der Fokus lag *in* einem Slide,
   der jetzt `inert` wird. Dann geht er auf den Track.
7. Kein `console.log` im Build. Das Modul hat keine Seiteneffekte außer `store()`.

## Testfälle

Jest (`lib/`): `maxIndex` für (count 5, perView 1 | 3 | 6), fade; `next`/`prev` an beiden Rändern für
`stop` und `rewind`; `nearestIndex` mit Offsets `[0, 300, 600]` und Positionen `0, 149, 151, 599, 10000`;
Ansage-Format.

E2E (`navigation.spec.js`, Slider mit 5 Slides):
- Klick auf „Next“ → Slide 2 sichtbar, Dot 2 hat `aria-current="true"`, die Status-Region enthält „2 of 5“.
- Bei `stop`: Am Anfang hat „Previous“ `aria-disabled="true"`. Bei `rewind`: „Previous“ am Anfang führt zu Slide 5.
- Dot 4 klicken → Slide 4.
- Fokus auf dem Track, `ArrowRight` → Slide 2; `End` → letzter erreichbarer Index; `Home` → 0.
- Mobile-Projekt: horizontal wischen (`page.mouse` bzw. Touch-Emulation oder `track.evaluate(el => el.scrollBy(...))`)
  → Index und Dots folgen; die Status-Region wird **nicht** befüllt (`source: scroll`).
- perView 3 (Container ≥ 1000 px): Dots 4 und 5 sind `hidden`, „Next“ am Ende `aria-disabled`.
- Nicht sichtbare Slides haben `inert`, sichtbare `is-active`.
- Ein Event `kadenz:goto { index: 2 }` wird ausgeführt, `kadenz:change` wird mit `source: 'api'` gefeuert.
- Zwei Slider auf einer Seite beeinflussen sich nicht.

## Akzeptanzkriterien

- [ ] Alle Testfälle grün.
- [ ] `view.js` ≤ 3,5 KB gzip (`npm run size` zeigt den Wert).
- [ ] Keine Long Tasks > 50 ms beim Init von 10 Slidern (Performance-Panel oder `PerformanceObserver` im E2E).
- [ ] Funktioniert mit RTL (`dir="rtl"` auf `<html>` im E2E per `page.evaluate`).

## Verifikation

```bash
npm run build && npm run size && npm run test:js && npm run test:e2e -- navigation
```
