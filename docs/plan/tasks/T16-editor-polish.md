---
id: T16
title: Editor-Feinschliff
phase: 3
status: open
owner:
depends_on: [T10]
---

# T16 – Editor-Feinschliff

## Ziel

Die letzten 20 %, die den Unterschied zwischen „funktioniert“ und „macht Freude“ ausmachen.

## Umfang (Dateien)

- `src/slider/edit.js`, `src/slider/components/*`, `src/slider/editor.scss`
- `src/slide/edit.js`
- `tests/e2e/editor-polish.spec.js`

## Umsetzung

1. **Vorschau-Toggle** in der Block-Toolbar („Preview“): zeigt die Slides im Editor so, wie es `perView`,
   `gap` und `peek` vorgeben (statisch, ohne Autoplay). Gegenüber dem Einzel-Slide-Modus aus T10 ist das
   eine Ansichtsoption. Sie wird nicht gespeichert, sondern lebt im lokalen State.
2. **Thumbnail-Leiste im Navigator**: Ist der Slider ausgewählt, gibt es kleine Kacheln aller Slides
   (`BlockPreview` mit `viewportWidth`), per Klick wählbar und per Drag-and-Drop sortierbar. Für die
   Sortierung `moveBlockToPosition` verwenden. Alternativ, falls zu schwer: die Buttons „Move left“ und
   „Move right“, die `moveBlocksUp`/`moveBlocksDown` nutzen.
3. **Tastenkürzel** bei ausgewähltem Slider oder Kind: `Alt+Shift+→` / `Alt+Shift+←` wechseln den Slide im
   Editor. Registrierung über `@wordpress/keyboard-shortcuts`, sichtbar in der Shortcut-Hilfe.
4. **Slide-Toolbar**: Ist ein Slide ausgewählt, gibt es Buttons zum Verschieben (Core-Mover genügt, weil
   `orientation: horizontal` auf `useInnerBlocksProps` gesetzt ist) und zum Duplizieren.
5. **Leerer-Zustand-Design**: Der Platzhalter aus T10 zeigt eine kleine Auswahl der Variationen
   (`__experimentalBlockVariationPicker` bzw. das stabile Pendant).
6. **Warnungen im Inspector**:
   - Autoplay mit Intervall < 4 s und Slides mit viel Text → Hinweis auf Lesezeit (Heuristik: mehr als 40 Wörter
     im längsten Slide).
   - `perView > count` → Hinweis „More slides per view than slides“.

## Akzeptanzkriterien

- [ ] E2E für Vorschau-Toggle, Sortierung (Reihenfolge im gespeicherten Markup ändert sich) und Tastenkürzel.
- [ ] Keine Performance-Probleme bei 20 Slides im Editor: Tippen in einem Absatz bleibt flüssig, und
  `BlockPreview` rendert nur, wenn der Slider ausgewählt ist.
- [ ] Alle neuen Bedienelemente sind per Tastatur erreichbar und benannt.

## Verifikation

```bash
npm run build && npm run lint && npm run test:e2e -- editor
```
