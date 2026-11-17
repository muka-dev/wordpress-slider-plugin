---
id: T06
title: Slide-Block
phase: 1
status: open
owner:
depends_on: [T01]
---

# T06 – Slide-Block `kadenz/slide`

## Ziel

Ein schlanker Container-Block, der beliebige Blöcke aufnimmt und Farbe, Hintergrundbild, Abstände und
Inhaltsausrichtung über **Core Block Supports** anbietet. Eigene Medienlogik gibt es nicht.

## Umfang (Dateien)

- `src/slide/block.json`, `index.js`, `edit.js`, `save.js`, `render.php`, `style.scss`, `editor.scss`
- `tests/php/integration/SlideRenderTest.php`

## Umsetzung

1. `block.json` exakt nach [contracts.md §4](../contracts.md#4-block-kadenzslide-öffentlich). Icon: eigenes
   SVG (Rechteck mit kleinem Punkt-Indikator) in `index.js`.
2. `edit.js`:
   - `useBlockProps( { className: 'kadenz-slide' } )` + `useInnerBlocksProps` mit Template
     `[ [ 'core/heading', { level: 2, placeholder: __( 'Slide title', 'kadenz-slider' ) } ], [ 'core/paragraph', { placeholder: __( 'Slide text…', 'kadenz-slider' ) } ] ]`,
     `templateLock: false`.
   - InspectorControls → Panel „Slide“ mit `TextControl` für `label`, Hilfetext: „Optional name for
     screen readers. If empty, ‘3 of 7’ is announced.“
   - `__experimentalLabel` bzw. `label` in `index.js` so setzen, dass die List View den `label` anzeigt,
     falls vorhanden.
3. `save.js`: `<InnerBlocks.Content />`.
4. `render.php`: wie im Vertrag. Den Block-Inhalt `$content` unverändert ausgeben.
5. `style.scss` (Frontend + Editor), minimal und mit niedriger Spezifität:
   ```scss
   :where(.kadenz-slide) {
     box-sizing: border-box;
     min-inline-size: 0;
     position: relative;
     overflow: hidden;
     background-size: cover;      // Fallback, falls kein backgroundSize gesetzt
     background-position: center;
   }
   ```
   Größe und Snap übernimmt der Slider (T08), nicht der Slide.
6. `editor.scss`: Mindesthöhe im Editor (`min-block-size: 8rem`), damit leere Slides anklickbar bleiben.

## Nicht im Umfang

ARIA-Attribute und Index (macht der Slider in T07), Bildoptimierung (T14).

## Akzeptanzkriterien

- [ ] Ein Slide lässt sich nur innerhalb von `kadenz/slider` einfügen.
- [ ] Hintergrundfarbe, Verlauf, Hintergrundbild (inkl. Größe), Padding, Block-Gap und Textfarbe funktionieren im
  Editor und im Frontend (server-seitig gerendert durch die Core-Supports).
- [ ] Integrationstest: Mit `style.color.background` und Layout-Attributen enthält das gerenderte HTML die Klasse
  `kadenz-slide`, die Inline-Hintergrundfarbe und die Layout-Klasse (`is-layout-flex`).
- [ ] Ein Slide mit `label` zeigt diesen Namen in der List View.
- [ ] Lint (JS, CSS, PHP) ist grün.

## Verifikation

```bash
npm run build && npm run lint && composer lint
npm run test:php -- --filter SlideRenderTest
```
Manuell: Slide einfügen, Hintergrundbild setzen, speichern, neu laden: Es erscheint keine Block-Validierungswarnung.
