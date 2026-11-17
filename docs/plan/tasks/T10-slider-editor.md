---
id: T10
title: "Slider: Editor-UI"
phase: 1
status: open
owner:
depends_on: [T06]
---

# T10 – Slider: Editor-UI

## Ziel

Eine Editor-Erfahrung, die sich wie Core anfühlt. Man sieht immer einen Slide groß, wechselt schnell zwischen
Slides und findet alle Einstellungen mit guten Defaults in logischen Panels.

## Umfang (Dateien)

- `src/slider/edit.js`, `src/slider/index.js`, `src/slider/icons.js`, `src/slider/editor.scss`
- `src/slider/components/` (z. B. `SlideNavigator.js`, `PerViewControl.js`)
- `tests/e2e/editor.spec.js`

## Umsetzung

1. **Darstellung im Editor**: Es gibt keinen Scroll-Snap-Track. Alle Slides liegen im DOM (für InnerBlocks
   nötig), aber nur der **aktive** ist sichtbar. Die anderen bekommen per CSS `display: none` über eine Klasse
   am Wrapper und `nth-child`. Der Index ist lokaler React-State (`useState`) und wird **nicht** gespeichert.
   Das Seitenverhältnis bzw. die Mindesthöhe gelten auch im Editor, damit das Layout stimmt.
   - Ausnahme: Ist der Slider **nicht** ausgewählt und `perView.lg > 1`, dürfen bis zu `perView.lg` Slides
     nebeneinander als Vorschau erscheinen (optional, nice-to-have).
2. **Slide-Navigator** (unter dem Slider, nur wenn der Slider oder ein Kind ausgewählt ist):
   `‹  Slide 2 of 5  ›` sowie die Buttons „Add slide“, „Duplicate“ und „Remove“.
   Hinzufügen und Duplizieren erfolgt per `insertBlock` / `duplicateBlocks` aus `core/block-editor`, danach
   springt der Navigator zum neuen Slide.
   Die Buttons sind Core-`Button`s mit Labels, alles ist per Tastatur bedienbar.
3. **Synchronisation mit der Auswahl**: Wählt der Nutzer einen Block innerhalb von Slide k aus (z. B. über
   die List View), springt der Index auf k. Umsetzung mit `useSelect` (`getSelectedBlockClientId`,
   `getBlockParents`).
4. **InspectorControls** in genau diesen Panels:
   - **Layout**: `effect` (ToggleGroupControl), Slides pro Ansicht (drei `RangeControl` 1–6, beschriftet
     „Small / Medium / Large container“, mit Hilfetext zu Container-Breiten; bei Fade deaktiviert),
     `gap` und `peek` (`UnitControl`), `heightMode` (ToggleGroupControl) plus abhängig davon `aspectRatio`
     (SelectControl: 16/9, 4/3, 3/2, 1/1, 21/9, 9/16, „Custom“ mit TextControl) bzw. `minHeight` (UnitControl).
   - **Behavior**: `autoplay` (ToggleControl), `interval` (RangeControl in Sekunden 2–30, gespeichert in ms),
     `pauseOnHover`, `endBehavior` (ToggleGroupControl „Stop“ / „Rewind“).
     Ist `autoplay` aktiv, erscheint der Hinweis: „A pause button is always shown for accessibility.“
   - **Navigation**: `showArrows`, `pagination` (SelectControl), `controlsPosition`.
   - **Accessibility**: `label` (TextControl). Ist er leer, erscheint ein `Notice` (Status `warning`, nicht
     dismissible) mit dem Text „Give the slider a name so screen reader users know what it contains.“
5. **Toolbar** (`BlockControls`): Button „Add slide“.
6. **Leerer Slider** (0 Slides): Platzhalter mit Button „Add first slide“.
7. Alle Strings laufen über `__( …, 'kadenz-slider' )`. Attribute werden **im Editor nicht** normalisiert:
   Die Controls erzeugen nur gültige Werte, den Rest erledigt der Server.
8. Block-Icon in `icons.js` (24×24, eigenes Motiv: drei Rechtecke mit Dots).

## Akzeptanzkriterien

- [ ] E2E: Slider einfügen → 2 Slides; „Add slide“ → 3 Slides und der Navigator zeigt „3 of 3“.
- [ ] E2E: In der List View einen Absatz in Slide 1 auswählen → der Navigator zeigt „1 of 3“, Slide 1 ist sichtbar.
- [ ] E2E: Duplizieren und Entfernen funktionieren; Entfernen des letzten Slides führt zum Platzhalter.
- [ ] E2E: Alle Attribute lassen sich setzen und landen korrekt im gespeicherten Block-Kommentar.
- [ ] Speichern und Neuladen erzeugen keine Block-Validierungsfehler.
- [ ] Bedienung nur mit Tastatur möglich: Navigator, alle Panels.
- [ ] axe ohne Verstöße im Editor-Canvas für den Slider-Bereich.

## Verifikation

```bash
npm run build && npm run lint && npm run test:e2e -- editor
```
