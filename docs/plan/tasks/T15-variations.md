---
id: T15
title: Block-Variationen
phase: 3
status: open
owner:
depends_on: [T10]
---

# T15 – Block-Variationen

## Ziel

Fünf durchdachte Startpunkte im Inserter, damit niemand bei null anfängt. Sie bestehen ausschließlich aus
Core-Blöcken in den Slides, sehen in jedem Theme ordentlich aus und nutzen die Theme-Presets.

## Umfang (Dateien)

- `src/slider/style.scss` (nur die Hero-Regel, s. u.)
- `src/slider/variations.js`, `src/slider/index.js` (Registrierung)
- `src/slider/icons.js` (ein Icon je Variation)
- `tests/e2e/variations.spec.js`

## Variationen

Alle mit `scope: [ 'inserter', 'block' ]`, `isActive` über die Unterscheidungsmerkmale in den Attributen.
Platzhaltertexte sind übersetzbar. **Keine Bild-URLs**: Bilder kommen als leere `core/image` bzw. `core/cover`
mit Platzhalter, sonst wären externe Requests oder gebündelte Medien nötig.

| Name | Titel | Attribute | Slides (Anzahl × Inhalt) |
|---|---|---|---|
| `hero` | Hero Slider | `align: full`, `heightMode: min`, `minHeight: 70svh`, `pagination: dots`, `controlsPosition: overlay`, `label: ""` | 3 × `core/cover` (`dimRatio: 40`, Inhalt: Heading H2, Paragraph, Buttons mit 1 Button) |
| `carousel` | Card Carousel | `perView {base:1, md:2, lg:3}`, `peek: 2rem`, `heightMode: auto`, `pagination: none`, `controlsPosition: below`, `align: wide` | 6 × (Image `aspectRatio 4/3`, Heading H3, Paragraph, Buttons) |
| `testimonials` | Testimonials | `heightMode: auto`, `effect: fade`, `pagination: dots`, `controlsPosition: below` | 3 × `core/quote` (mit Citation) + `core/image` (rund, 64 px, `className: is-style-rounded`) |
| `logos` | Logo Strip | `perView {base:2, md:4, lg:6}`, `heightMode: auto`, `showArrows: false`, `pagination: none`, `autoplay: true`, `interval: 3000`, `endBehavior: rewind` | 8 × `core/image` (zentriert, `width: 160px`) |
| `gallery` | Image Gallery | `perView {base:1, md:1, lg:1}`, `heightMode: aspect`, `aspectRatio: 3/2`, `pagination: numbers`, `endBehavior: rewind` | 5 × `core/image` (`sizeSlug: large`, `scale: cover`, `style.dimensions.aspectRatio` 3/2) |

Keine Variation ist `isDefault`: Der nackte Slider bleibt neutral (2 einfache Slides), die Variationen
erscheinen zusätzlich im Inserter.

**Hero**: Das Cover muss den ganzen Slide ausfüllen. Dafür kommt eine Regel in `src/slider/style.scss`:
`:where(.kadenz-slide) > .wp-block-cover:only-child { min-block-size: 100%; margin: 0; }` sowie für den Slide
`padding: 0`. Das `minHeight` des Covers wird nicht gesetzt.

Hinweis „Logo Strip“: Autoplay mit Logos ist der einzige Default-Autoplay-Fall. Er ist wegen des Pause-Buttons
und reduced-motion vertretbar. Den echten Ticker-Modus liefert Backlog B04.

## Akzeptanzkriterien

- [ ] Jede Variation ist im Inserter mit eigenem Icon und eigener Beschreibung auffindbar (Suche „slider“, „carousel“, „logo“, „testimonial“, „gallery“).
- [ ] E2E: Jede Variation einfügen, veröffentlichen, im Frontend ohne Konsolenfehler; `expectNoAxeViolations`
  auf dem Frontend. Leere Bilder sind im Frontend nicht vorhanden, weil Core leere Image-Blöcke nicht rendert.
  Der Test füllt dafür vorher je ein Bild per Media-Upload eines Test-PNG aus `tests/e2e/assets/`.
- [ ] Die Block-Transform-Ansicht zeigt bei ausgewähltem Slider die Variationen (scope `block`).
- [ ] Screenshots aller fünf Variationen im PR (Twenty Twenty-Five).

## Verifikation

```bash
npm run build && npm run lint && npm run test:e2e -- variations
```
