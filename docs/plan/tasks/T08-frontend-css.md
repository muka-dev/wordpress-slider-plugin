---
id: T08
title: Frontend-CSS
phase: 1
status: open
owner:
depends_on: [T07]
---

# T08 – Frontend-CSS

## Ziel

Das gesamte Frontend-Styling: Scroll-Snap-Track, responsives „Slides pro Ansicht“ per Container Queries,
Höhenmodi, Steuerelemente und Barrierefreiheit. Es soll schön aussehen, ohne das Theme zu überstimmen.
Budget: zusammen mit dem Slide-CSS **≤ 4 KB gzip**.

## Umfang (Dateien)

- `src/slider/style.scss` (ggf. aufgeteilt in Partials `src/slider/styles/_*.scss`)
- `src/slider/editor.scss` (nur, was der Editor zusätzlich braucht)

## Umsetzung

Grundlage sind [contracts.md §5, §7](../contracts.md). Konkret:

1. **Defaults** aller Theme-Hooks (§7.1) in `:where(.kadenz-slider) { … }`.
2. **Wrapper**: `container: kadenz-slider / inline-size; position: relative;`.
3. **Per-View**:
   ```scss
   :where(.kadenz-slider__track) { --_kadenz-per-view: var(--kadenz-per-view-base, 1); }
   @container kadenz-slider (min-width: 600px)  { :where(.kadenz-slider__track) { --_kadenz-per-view: var(--kadenz-per-view-md, var(--kadenz-per-view-base, 1)); } }
   @container kadenz-slider (min-width: 1000px) { :where(.kadenz-slider__track) { --_kadenz-per-view: var(--kadenz-per-view-lg, var(--kadenz-per-view-md, var(--kadenz-per-view-base, 1))); } }
   ```
   Achtung: Weil das JS (T09) `--_kadenz-per-view` per `getComputedStyle` ausliest, muss die Property auf dem
   **Track** gesetzt sein.
4. **Track** (Effekt `slide`): `display: grid; grid-auto-flow: column;`
   `grid-auto-columns: calc((100% - var(--kadenz-peek, 0px) - (var(--_kadenz-per-view) - 1) * var(--kadenz-gap, 1.5rem)) / var(--_kadenz-per-view));`
   `gap`, `overflow-x: auto`, `overscroll-behavior-x: contain`, `scroll-snap-type: x mandatory`,
   versteckte Scrollbar (`scrollbar-width: none` + `::-webkit-scrollbar`). Slides: `scroll-snap-align: start`.
   `scroll-behavior: smooth` nur innerhalb von `@media (prefers-reduced-motion: no-preference)`.
5. **Höhenmodi** auf den Slides: `is-height-aspect` → `aspect-ratio: var(--kadenz-aspect-ratio, 16/9)`;
   `is-height-min` → `min-block-size: var(--kadenz-min-height)`; `is-height-auto` → nichts (Grid streckt auf
   den höchsten Slide).
6. **Fokus**: Der Track bekommt `:focus-visible` mit Outline `2px solid var(--kadenz-focus-color)` und
   `outline-offset: 2px`. Alle Buttons erhalten einen sichtbaren Fokus.
7. **Pfeile** (`has-controls-overlay`): absolut, vertikal zentriert im Viewport, `inset-inline-start/end`;
   Größe `--kadenz-control-size`, runde Plate mit `--kadenz-control-background`, Icon in
   `--kadenz-control-color`. Bei `aria-disabled="true"`: `opacity: .4; cursor: default`.
   `has-controls-below`: Pfeile und Pagination in einer Zeile unter dem Track (Flexbox, zentriert).
   In RTL werden die Chevron-Icons per `:dir(rtl)` oder `[dir="rtl"]` mit `scale: -1 1` gespiegelt.
8. **Pagination**: Dots (rund, `--kadenz-dot-size`, aktiver Dot über `[aria-current="true"]` breiter oder
   ausgefüllt). Der klickbare Bereich ist mindestens 24×24 px (WCAG 2.5.8), umgesetzt mit Padding oder
   Pseudo-Element. Numbers-Variante als kleine Pills. Progress als dünne Leiste mit
   `transition: inline-size var(--kadenz-transition-duration)`.
9. **Toggle** (Autoplay): oben in der Ecke (`inset-block-start`/`inset-inline-end`). Das Play- oder
   Pause-Icon wird abhängig von `.is-playing` angezeigt.
10. **Progressive Enhancement**: `.kadenz-slider:not(.is-ready)` versteckt `__arrow`, `__pagination`,
    `__progress` und `__toggle` mit `display: none`. `.is-single` versteckt ebenfalls alles und deaktiviert den Scroll.
11. **`.screen-reader-text`**: Diese Klasse hat nicht jedes Theme. Deshalb bekommt `.kadenz-slider__status`
    die Visually-Hidden-Regeln zusätzlich selbst.
12. **Forced Colors** (`@media (forced-colors: active)`): Steuerelemente bekommen `border: 1px solid ButtonText`,
    der aktive Dot `background: Highlight`.
13. **Fade-Effekt** gehört **nicht** hierher, sondern zu T13.

## Akzeptanzkriterien

- [ ] Bei 1, 2 und 3 Slides pro Ansicht, mit Gap und Peek stimmt die Geometrie: Der letzte Snap-Punkt
  zeigt die letzten `perView` Slides bündig, ohne Lücke.
- [ ] Ein Slider in einer schmalen Spalte (< 600 px Containerbreite) nutzt `base`, auch wenn der Viewport breit ist.
- [ ] Höhenmodi `aspect`, `min` und `auto` funktionieren, und Bilder in Slides verursachen kein CLS.
- [ ] RTL: Snap, Pfeil-Positionen und Icons sind gespiegelt.
- [ ] Ohne JS gibt es keine Steuerelemente, aber Wischen und Scrollen funktionieren.
- [ ] Kontrast der Steuerelemente im Twenty-Twenty-Five-Theme (hell und dunkel) mindestens 3:1 (WCAG 1.4.11).
- [ ] `npm run size` ist grün (CSS ≤ 4 KB gzip), `npm run lint:css` ist grün.
- [ ] Screenshots (Desktop und Mobile, LTR und RTL) hängen am PR.

## Verifikation

```bash
npm run build && npm run size && npm run lint:css
```
Manuell im Frontend mit Twenty Twenty-Five und einem Classic Theme (z. B. Twenty Twenty-One).
