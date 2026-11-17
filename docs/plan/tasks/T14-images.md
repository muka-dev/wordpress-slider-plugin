---
id: T14
title: Bild-Optimierung (lazy, fetchpriority, sizes)
phase: 2
status: open
owner:
depends_on: [T07]
---

# T14 – Bild-Optimierung

## Ziel

Slider sind oft das LCP-Element einer Seite und laden viele Bilder. Dieser Task sorgt dafür, dass genau das
richtige Bild sofort und in der richtigen Größe lädt und alle anderen später.

## Umfang (Dateien)

- `includes/class-slide-markup.php` (Erweiterung von `prepare()`)
- `includes/class-image-sizes.php`: `Kadenz\Slider\Image_Sizes` (reine Berechnung)
- `src/slider/render.php` (Übergabe zusätzlicher Infos in `$ctx`)
- `tests/php/unit/ImageSizesTest.php`, `tests/php/integration/SlideImagesTest.php`

## Hintergrund

WordPress setzt `loading="lazy"` und `fetchpriority="high"` heuristisch (`wp_get_loading_optimization_attributes`).
In verschachtelten Blöcken trifft die Heuristik oft falsch, und `sizes` ist bei mehreren Slides pro Ansicht
viel zu groß, meist `100vw` oder die Bildbreite.

## Umsetzung

1. In `Slide_Markup::prepare()` alle `<img>` des Slides mit `WP_HTML_Tag_Processor` durchlaufen:
   - **Slide 0**: `loading="lazy"` entfernen, `decoding="async"` setzen. `fetchpriority="high"` nur, wenn
     `$ctx['is_first_block']` true ist (s. u.), und nur für das **erste** Bild des Slides. Ein bereits vorhandenes
     `fetchpriority="high"` in anderen Slides wird entfernt.
   - **Slides `1 … perView.lg-1`** können beim Laden sichtbar sein: kein `lazy`, `decoding="async"`,
     `fetchpriority` entfernen.
   - **Slides ≥ `perView.lg`**: `loading="lazy"`, `decoding="async"`, `fetchpriority` entfernen.
   - Hintergrundbilder aus der Block-Support-Inline-Style (`background-image`) lassen sich nicht lazy laden.
     Das wird in der Doku (T19) erwähnt; bei Bedarf empfiehlt die Doku `core/cover` (der `<img>` nutzt).
2. **`is_first_block`**: Ist der Slider der erste Block im Hauptinhalt? Ermittlung über einen statischen Zähler
   im Filter `render_block` mit Prüfung `in_the_loop()` und `is_main_query()`, oder einfacher über den
   Core-Filter `wp_get_loading_optimization_attributes` bzw. die Funktion
   `wp_increase_content_media_count()`. Halte dich an das, was Core in 6.6+ anbietet, und dokumentiere die
   Entscheidung im PR.
   Zusätzlich gibt es den Filter `kadenz_slider_is_lcp_candidate` (bool, `$attributes`, `$block`) – das ist
   eine **Vertragsänderung**: Der Hook kommt im selben PR in contracts.md §8.
3. **`sizes`**: `Image_Sizes::sizes_attr( array $per_view, string $align ): string`. Beispiel für
   `perView {base:1, md:2, lg:3}` und `align=full`:
   `(min-width: 1000px) 34vw, (min-width: 600px) 50vw, 100vw`. Grundlage sind **Viewport**-Breakpoints als
   Näherung an die Container-Breite. Für `align` leer/`wide` werden die Breiten aus `wp_get_global_settings( array( 'layout' ) )`
   (`contentSize`/`wideSize`) als Obergrenze genutzt: `min( Xvw, contentSize / perView )`.
   Gilt nur für `<img>` mit `srcset`. Ein vorhandenes `sizes="auto, …"` (Core 6.7+) wird respektiert, und es
   wird nur der Fallback-Teil ersetzt.
4. Alle Manipulationen ausschließlich mit `WP_HTML_Tag_Processor`.

## Akzeptanzkriterien

- [ ] Unit: `sizes_attr` für (1,1,1), (1,2,3), (2,2,2) × (full, wide, none).
- [ ] Integration: Slider mit 4 Bild-Slides als erster Block → Bild 1 ohne `lazy`, mit `fetchpriority="high"`;
  Bilder 2–4 `lazy`, ohne `fetchpriority`. Als zweiter Block (Absatz davor mit großem Bild) → Bild 1 ohne
  `fetchpriority`.
- [ ] Integration: perView 3 → `sizes` enthält `34vw` bzw. den Wert aus `contentSize`.
- [ ] Lighthouse (lokal, Fixture `hero`): kein Hinweis „Largest Contentful Paint image was lazily loaded“,
  kein „Properly size images“ für Slider-Bilder.

## Verifikation

```bash
composer test:unit && npm run test:php && composer lint
```
