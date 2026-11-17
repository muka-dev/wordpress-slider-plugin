---
id: T07
title: "Slider: Server-Rendering"
phase: 1
status: open
owner:
depends_on: [T05, T06]
---

# T07 – Slider: Server-Rendering

## Ziel

`render.php` des Sliders erzeugt exakt das Markup aus [contracts.md §5](../contracts.md#5-frontend-markup-öffentlich-klassen--struktur-intern-direktiven),
inklusive Interactivity-Direktiven, Kontext, globalem State und ARIA. Damit ist das Markup der Vertrag, an
dem CSS (T08) und JS (T09) arbeiten.

## Umfang (Dateien)

- `src/slider/render.php`, `src/slider/save.js` (nur prüfen: `<InnerBlocks.Content />`)
- `includes/class-slide-markup.php`: `Kadenz\Slider\Slide_Markup`
- `includes/class-plugin.php` (Registrierung von `wp_interactivity_state` und Filter-Dokumentation)
- `includes/icons.php` (Funktion, die die SVG-Strings liefert) – optional, sonst in `render.php`
- `tests/php/integration/SliderRenderTest.php`
- `tests/php/unit/SlideMarkupTest.php`

## Umsetzung

1. **Ablauf in `render.php`**:
   1. `$attrs = Attributes::normalize( $attributes );`
   2. Slides zählen: `$slides = $block->inner_blocks` und nur Blöcke mit dem Namen `kadenz/slide` berücksichtigen. `N = count`.
   3. `N === 0` → `return ''` (im Frontend; im Editor-Preview ebenso).
   4. Jeden Slide mit `$inner->render()` rendern und durch `Slide_Markup::prepare( $html, $i, $N, $ctx )` schicken.
      **`$content` wird nicht verwendet**, weil er die Slides ohne Index enthält.
   5. Kontext nach §6.1 bauen, durch den Filter `kadenz_slider_render_context` laufen lassen und per
      `wp_interactivity_data_wp_context( $context )` ausgeben.
   6. Wrapper-Attribute per `get_block_wrapper_attributes( array( 'id' => …, 'class' => …, 'style' => Style_Vars::from_attributes( $attrs ), 'aria-roledescription' => …, 'aria-label' => … ) )`.
      Hinweis: `get_block_wrapper_attributes` mergt `style` und `class` mit denen der Block Supports.
   7. Steuerelemente nach §5.1 rendern; die Bedingungen (N > 1, `showArrows`, `pagination`, `autoplay`) gelten
      exakt wie im Vertrag. Den Autoplay-Toggle **schon hier** mit rendern (Verhalten folgt in T12).
2. **Globaler State** (§6.2): `wp_interactivity_state()` beim ersten Rendern eines Sliders pro Request aufrufen
   (statisches Flag in `Plugin`), nicht global auf `init`.
3. **`Slide_Markup::prepare()`**: mit `WP_HTML_Tag_Processor`, `next_tag()` auf das erste Tag, Attribute nach §5.2.
   Den Slide-`label` liest der Slider aus `$inner->attributes['label']` und übergibt ihn in `$ctx['label']`.
4. **i18n**: alle Strings mit Text-Domain `kadenz-slider`, Platzhalter mit `/* translators: */`-Kommentaren:
   „carousel“ (`_x`, Kontext „aria-roledescription“), „slide“ (ebenso), „Slider“ (Fallback-Label),
   „%1$d of %2$d“, „Previous slide“, „Next slide“, „Go to slide %d“.
5. **Escaping**: `esc_attr` für alle Attributwerte, die nicht über `get_block_wrapper_attributes` laufen.
   Die SVGs sind Konstanten.

## Testfälle

Integration (`do_blocks()` auf serialisiertem Block-Markup):
- 3 Slides mit Defaults: Wrapper hat `aria-roledescription`, das Default-Label, `data-wp-interactive`, Kontext mit
  `count: 3`; 3 Slides mit `role="group"` und `aria-label="1 of 3"` … `"3 of 3"`; 2 Pfeile, 3 Dots,
  1 Status-Region; **kein** Toggle.
- `autoplay: true` → Toggle vorhanden, Klasse `has-autoplay`, `--kadenz-interval` nur bei abweichendem Wert.
- `pagination: "numbers"` → Buttons enthalten `<span class="kadenz-slider__page-label">2</span>`.
- `pagination: "progress"` → Progress statt Buttons.
- `showArrows: false` → keine Pfeile.
- 1 Slide → `is-single`, keine Steuerelemente, keine Status-Region.
- 0 Slides → leerer String.
- Slide mit `label: "Intro"` → `aria-label="Intro"`.
- Fremder Block als Inner Block (manipuliertes Markup) wird ignoriert.
- Filter `kadenz_slider_render_context` fügt einen Schlüssel hinzu → der Schlüssel taucht im Kontext auf.
- Zwei Slider auf einer Seite → unterschiedliche IDs, `aria-controls` zeigt jeweils auf den eigenen Track.

Unit: `Slide_Markup::prepare` mit `<div class="x">…`, mit führendem Whitespace und mit Kommentar vor dem ersten Tag.

## Akzeptanzkriterien

- [ ] Alle Testfälle grün.
- [ ] HTML-Validierung: Das gerenderte Markup eines Beispiel-Sliders ist valides HTML5 (z. B. per `html-validate`
  im Test oder manuell mit dem W3C-Validator).
- [ ] Im Frontend lädt `view.js` als Modul, und die Interactivity API meldet keine Fehler in der Konsole.
- [ ] Ohne JS (im Browser deaktiviert) sind alle Slide-Inhalte im DOM, und die Steuerelemente sind (nach T08) unsichtbar.

## Verifikation

```bash
composer test:unit && npm run test:php && composer lint && npm run build
```
