# Verträge (verbindlich)

Diese Datei legt alle Schnittstellen fest, über die sich parallel arbeitende Tasks verständigen. Wer davon
abweicht, bricht die Arbeit anderer. Änderungen nur per PR mit Begründung (siehe [Plan](README.md)).

Kennzeichnung: **[öffentlich]** = semver-relevante API für Themes/Dritte. **[intern]** = darf sich zwischen
Versionen ändern, aber nur, wenn alle Tasks gleichzeitig angepasst werden.

---

## 1. Dateistruktur

```
kadenz-slider.php                 Plugin-Header, Konstanten, require_once, Hooks
includes/
  class-plugin.php                Kadenz\Slider\Plugin – registriert Blöcke, State, Filter
  class-attributes.php            Kadenz\Slider\Attributes – Defaults & Normalisierung (reines PHP)
  class-style-vars.php            Kadenz\Slider\Style_Vars – Attribute → CSS Custom Properties (reines PHP)
  class-slide-markup.php          Kadenz\Slider\Slide_Markup – ARIA/Index/Bild-Attribute auf Slide-HTML
src/
  slider/  block.json edit.js save.js index.js render.php view.js style.scss editor.scss
           variations.js (T15) icons.js lib/ (reine JS-Helfer, Jest-getestet)
  slide/   block.json edit.js save.js index.js render.php style.scss editor.scss
build/                            Build-Ausgabe (nicht eingecheckt)
tests/
  php/unit/                       PHPUnit ohne WordPress
  php/integration/                PHPUnit mit WordPress (wp-env)
  js/                             Jest
  e2e/                            Playwright
languages/
```

`register_block_type()` wird mit `build/slider` und `build/slide` aufgerufen.

## 2. Konstanten & Bootstrap [intern]

```php
define( 'KADENZ_SLIDER_VERSION', '0.1.0' );
define( 'KADENZ_SLIDER_FILE', __FILE__ );
define( 'KADENZ_SLIDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'KADENZ_SLIDER_URL', plugin_dir_url( __FILE__ ) );
```

Plugin-Header: `Requires at least: 6.6`, `Requires PHP: 7.4`, `License: GPL-2.0-or-later`,
`Text Domain: kadenz-slider`.

## 3. Block `kadenz/slider` [öffentlich]

### 3.1 block.json (Kernfelder)

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "kadenz/slider",
  "title": "Slider",
  "category": "media",
  "textdomain": "kadenz-slider",
  "allowedBlocks": [ "kadenz/slide" ],
  "supports": {
    "align": [ "wide", "full" ],
    "html": false,
    "color": { "background": true, "text": true },
    "spacing": { "margin": true, "padding": true },
    "shadow": true,
    "interactivity": true
  },
  "editorScript": "file:./index.js",
  "editorStyle": "file:./index.css",
  "style": "file:./style-index.css",
  "viewScriptModule": "file:./view.js",
  "render": "file:./render.php"
}
```

### 3.2 Attribute

| Attribut | Typ | Default | Erlaubte Werte / Normalisierung |
|---|---|---|---|
| `label` | string | `""` | Freitext, `sanitize_text_field`; leer → übersetztes „Slider“ beim Rendern |
| `effect` | string | `"slide"` | `slide` \| `fade`; sonst Default |
| `perView` | object | `{"base":1,"md":1,"lg":1}` | Je Schlüssel int 1–6; fehlender Schlüssel erbt vom kleineren (`md`←`base`, `lg`←`md`). Bei `effect=fade` immer 1 |
| `gap` | string | `"1.5rem"` | CSS-Länge (s. 3.3), sonst Default |
| `peek` | string | `"0px"` | CSS-Länge, sonst Default |
| `heightMode` | string | `"aspect"` | `aspect` \| `min` \| `auto` |
| `aspectRatio` | string | `"16/9"` | Regex `^\d+(\.\d+)?\s*/\s*\d+(\.\d+)?$`, sonst Default |
| `minHeight` | string | `""` | CSS-Länge oder leer |
| `autoplay` | boolean | `false` | |
| `interval` | number | `6000` | int, geclampt auf 2000–30000 (ms) |
| `pauseOnHover` | boolean | `true` | |
| `endBehavior` | string | `"stop"` | `stop` \| `rewind` |
| `showArrows` | boolean | `true` | |
| `pagination` | string | `"dots"` | `none` \| `dots` \| `numbers` \| `progress` |
| `controlsPosition` | string | `"overlay"` | `overlay` \| `below` |

### 3.3 CSS-Länge

Gültig ist: `^(0|\d+(\.\d+)?(px|rem|em|%|vw|vh|svh|dvh))$` oder ein Preset in der Form `var:preset|spacing|<slug>`.
Ein Preset wird zu `var(--wp--preset--spacing--<slug>)` umgeschrieben (`<slug>` nur `[a-z0-9-]`).
`peek` erlaubt kein `vh`/`svh`/`dvh`.

### 3.4 Normalisierung [intern]

`Kadenz\Slider\Attributes::normalize( array $raw ): array` liefert **immer** alle Schlüssel aus 3.2 mit
gültigen Werten. Die Defaults kommen aus `Attributes::defaults()`, gefiltert durch
`kadenz_slider_default_attributes`. Die Methode ist reines PHP ohne WordPress-Aufrufe, abgesehen von
`apply_filters` in `defaults()`, das in Unit-Tests gestubbt wird.

## 4. Block `kadenz/slide` [öffentlich]

```json
{
  "apiVersion": 3,
  "name": "kadenz/slide",
  "title": "Slide",
  "category": "media",
  "parent": [ "kadenz/slider" ],
  "textdomain": "kadenz-slider",
  "attributes": { "label": { "type": "string", "default": "" } },
  "supports": {
    "html": false,
    "reusable": false,
    "color": { "background": true, "text": true, "link": true, "gradients": true },
    "background": { "backgroundImage": true, "backgroundSize": true },
    "spacing": { "padding": true, "blockGap": true },
    "layout": { "default": { "type": "flex", "orientation": "vertical", "justifyContent": "center", "verticalAlignment": "center" }, "allowSwitching": false }
  },
  "editorScript": "file:./index.js",
  "editorStyle": "file:./index.css",
  "style": "file:./style-index.css",
  "render": "file:./render.php"
}
```

`save` liefert `<InnerBlocks.Content />`. `render.php` gibt aus:
`<div {get_block_wrapper_attributes(['class' => 'kadenz-slide'])}>{$content}</div>`.
Index und ARIA-Attribute setzt **nicht** der Slide, sondern der Slider (5.2).

## 5. Frontend-Markup [öffentlich: Klassen & Struktur; intern: Direktiven]

### 5.1 Struktur

`{id}` = `wp_unique_id( 'kadenz-slider-' )`. `N` = Anzahl der Slides.

> **Kein `anchor`-Support:** Beide Blöcke speichern keinen eigenen Wrapper (`save` liefert nur
> `<InnerBlocks.Content />`). Ein über das HTML-`id` gespeichertes Anker-Attribut ginge damit verloren.
> Wenn Anker gewünscht sind, kommen sie als eigenes String-Attribut per Vertragsänderung.

```html
<section id="{id}"
  class="wp-block-kadenz-slider kadenz-slider is-effect-{effect} is-height-{heightMode} has-controls-{controlsPosition} [is-single] [has-autoplay]"
  style="{Style_Vars}"
  aria-roledescription="{i18n: carousel}"
  aria-label="{label}"
  data-wp-interactive="kadenz/slider"
  data-wp-context='{Kontext, 6.1}'
  data-wp-init="callbacks.init"
  data-wp-class--is-ready="context.ready"
  data-wp-class--is-playing="context.playing">

  <!-- nur wenn autoplay && N > 1 (T12) -->
  <button type="button" class="kadenz-slider__toggle" aria-controls="{id}-track"
    data-wp-on--click="actions.toggleAutoplay"
    data-wp-bind--aria-label="state.toggleLabel">
    <svg class="kadenz-slider__icon kadenz-slider__icon--pause" aria-hidden="true" focusable="false">…</svg>
    <svg class="kadenz-slider__icon kadenz-slider__icon--play" aria-hidden="true" focusable="false">…</svg>
  </button>

  <div class="kadenz-slider__viewport">
    <!-- nur wenn showArrows && N > 1 -->
    <button type="button" class="kadenz-slider__arrow kadenz-slider__arrow--prev"
      aria-controls="{id}-track" aria-label="{i18n: Previous slide}"
      data-wp-on--click="actions.prev" data-wp-bind--aria-disabled="state.isAtStart">
      <svg …aria-hidden="true" focusable="false">…</svg>
    </button>
    <button type="button" class="kadenz-slider__arrow kadenz-slider__arrow--next"
      aria-controls="{id}-track" aria-label="{i18n: Next slide}"
      data-wp-on--click="actions.next" data-wp-bind--aria-disabled="state.isAtEnd">
      <svg …aria-hidden="true" focusable="false">…</svg>
    </button>

    <div class="kadenz-slider__track" id="{id}-track" tabindex="0"
      data-wp-on--keydown="actions.onKeydown">
      <!-- je Slide, siehe 5.2 -->
    </div>
  </div>

  <!-- pagination = dots | numbers, nur wenn N > 1 -->
  <div class="kadenz-slider__pagination kadenz-slider__pagination--{dots|numbers}">
    <button type="button" class="kadenz-slider__page" aria-controls="{id}-track"
      aria-label="{i18n: Go to slide %d}"
      data-wp-context='{"page":0}'
      data-wp-on--click="actions.goToPage"
      data-wp-bind--aria-current="state.isCurrentPage"
      data-wp-bind--hidden="state.isPageHidden">
      <span class="kadenz-slider__page-label">1</span>   <!-- nur bei numbers -->
    </button>
    … (N Buttons)
  </div>

  <!-- pagination = progress, nur wenn N > 1 -->
  <div class="kadenz-slider__progress" aria-hidden="true">
    <span class="kadenz-slider__progress-bar" data-wp-style--inline-size="state.progressSize"></span>
  </div>

  <div class="kadenz-slider__status screen-reader-text" aria-live="polite" aria-atomic="true"
    data-wp-text="context.announcement"></div>
</section>
```

Regeln:

- Bei `N === 0` gibt `render.php` einen leeren String zurück.
- Bei `N === 1` bekommt der Wrapper `is-single`, und es werden weder Steuerelemente noch Status noch Kontext-Interaktion gerendert.
- Für `pagination` werden **N** Buttons gerendert; welche davon gerade nicht erreichbar sind
  (Index > `maxIndex`), blendet das JS per `hidden` aus.
- Alle SVG-Icons stehen inline in PHP (`render.php`), es gibt keine Icon-Fonts und keine externen Dateien.
  Sie sind 24×24-Viewbox-Chevrons bzw. Play/Pause mit `fill="currentColor"`.

### 5.2 Slides

Der Slider rendert seine Slides **selbst** aus `$block->inner_blocks` und nicht aus `$content`. Danach setzt
`Slide_Markup::prepare( string $html, int $index, int $count, array $ctx ): string` per
`WP_HTML_Tag_Processor` auf dem **ersten** Tag:

| Attribut | Wert |
|---|---|
| `role` | `group` |
| `aria-roledescription` | i18n „slide“ |
| `aria-label` | Slide-Attribut `label`, falls gesetzt, sonst i18n „%1$d of %2$d“ (1-basiert) |
| `data-kadenz-index` | 0-basierter Index |

T14 erweitert `prepare()` um die Bild-Attribute.

## 6. Interactivity API [intern, außer 6.4]

### 6.1 Kontext pro Instanz (`data-wp-context`)

```json
{
  "id": "kadenz-slider-1",
  "count": 5,
  "index": 0,
  "perView": 1,
  "effect": "slide",
  "endBehavior": "stop",
  "autoplay": false,
  "interval": 6000,
  "pauseOnHover": true,
  "playing": false,
  "userStopped": false,
  "ready": false,
  "announcement": ""
}
```

Erweiterungen durch `kadenz_slider_render_context` (Filter, `array $context, array $attributes, WP_Block $block`)
sind erlaubt, solange die obigen Schlüssel erhalten bleiben.

### 6.2 Globaler State (serverseitig, einmal pro Request)

```php
wp_interactivity_state( 'kadenz/slider', array(
  'i18n' => array(
    'slideOf' => __( 'Slide %1$d of %2$d', 'kadenz-slider' ), // Live-Ansage
    'play'    => __( 'Start automatic slide show', 'kadenz-slider' ),
    'pause'   => __( 'Stop automatic slide show', 'kadenz-slider' ),
  ),
) );
```

JS ersetzt `%1$d`/`%2$d` per String-Replace (kein sprintf-Paket).

### 6.3 Store `kadenz/slider` (`src/slider/view.js`)

| Name | Art | Verhalten |
|---|---|---|
| `state.maxIndex` | Getter | `effect=fade ? count-1 : max(0, count - perView)` |
| `state.isAtStart` | Getter | `endBehavior=stop && index===0` |
| `state.isAtEnd` | Getter | `endBehavior=stop && index===maxIndex` |
| `state.isCurrentPage` | Getter | `context.page === context.index` |
| `state.isPageHidden` | Getter | `context.page > maxIndex` |
| `state.progressSize` | Getter | `((index+1)/(maxIndex+1)*100)%`, als String mit `%` |
| `state.toggleLabel` | Getter | `playing` → `i18n.pause`, sonst `i18n.play` (T12) |
| `actions.next` / `actions.prev` | Action | ±1; an den Rändern bei `stop` keine Aktion, bei `rewind` Sprung ans andere Ende; setzt Ansage |
| `actions.goToPage` | Action | `goTo(context.page)`; setzt Ansage |
| `actions.onKeydown` | Action | Auf dem Track: `ArrowLeft`/`ArrowRight` (RTL-gespiegelt) → prev/next, `Home`/`End` → 0/maxIndex; dann `preventDefault` |
| `actions.toggleAutoplay` | Action | T12 |
| `callbacks.init` | Callback | Setup (6.5), danach `ready = true` und Event `kadenz:ready` |

DOM-Referenzen (Track, Slides, Observer, Timer) liegen in einer modulinternen `WeakMap`, deren Schlüssel
das Wurzelelement ist, und **nicht** im Kontext.

Reine Logik (Index-Berechnung, Clamping, Rewind, Ansage-Text) liegt in `src/slider/lib/*.js` und wird
mit Jest getestet. `view.js` enthält nur den Klebstoff zum DOM.

### 6.4 DOM-Events [öffentlich]

Ausgehend, auf dem Wurzelelement, `bubbles: true`:

| Event | `detail` |
|---|---|
| `kadenz:ready` | `{ count }` |
| `kadenz:change` | `{ index, previous, count, source }` mit `source` ∈ `user` \| `autoplay` \| `scroll` \| `api` |
| `kadenz:autoplay` | `{ playing }` (T12) |

Eingehend: Ein auf dem Wurzelelement dispatchtes Event steuert den Slider:

| Event | `detail` |
|---|---|
| `kadenz:goto` | `{ index }` |
| `kadenz:next`, `kadenz:prev` | – |
| `kadenz:play`, `kadenz:pause` | – (T12) |

### 6.5 Laufzeitverhalten (verbindlich für T08/T09/T12/T13)

- `perView` liest das JS aus `getComputedStyle(track).getPropertyValue('--_kadenz-per-view')` und
  aktualisiert es per `ResizeObserver` auf dem Wurzelelement.
- Der `index` bezeichnet den **ersten** sichtbaren Slide, Wertebereich `0…maxIndex`.
- **Slide-Modus:** Navigieren heißt `track.scrollTo({ left })` mit
  `left = slideRect.left - trackRect.left + track.scrollLeft` (RTL-sicher). Wischt der Nutzer, ermittelt
  das JS nach `scrollend` (Fallback: `scroll`, 120 ms entprellt) den Slide, dessen Start dem Track-Start am
  nächsten liegt, und setzt `index` (`source: scroll`).
- **Sichtbarkeit:** Nach jeder Indexänderung bekommen Slides im Bereich `index … index+perView-1` die Klasse
  `is-active`, alle anderen das Attribut `inert`. Vor `ready` setzt das JS kein `inert`.
- **Ansage:** `announcement` wird nur bei `source=user` gesetzt (Text: `i18n.slideOf`), niemals durch Autoplay.
- `prefers-reduced-motion: reduce` → `scrollTo` mit `behavior: 'instant'`.

## 7. CSS-API [öffentlich]

### 7.1 Custom Properties

Diese Properties setzt `Style_Vars` inline am Wrapper, und nur, wenn ihr Wert vom CSS-Default abweicht:

| Property | Quelle |
|---|---|
| `--kadenz-per-view-base`, `--kadenz-per-view-md`, `--kadenz-per-view-lg` | `perView` |
| `--kadenz-gap` | `gap` |
| `--kadenz-peek` | `peek` |
| `--kadenz-aspect-ratio` | `aspectRatio` (nur bei `heightMode=aspect`) |
| `--kadenz-min-height` | `minHeight` (nur bei `heightMode=min`) |
| `--kadenz-interval` | `interval` + `ms` (nur bei `autoplay`) |

Folgende Properties sind Theme-Hooks. Das Plugin setzt sie nie inline, sondern nur als Defaults in `:where()`:

| Property | Default |
|---|---|
| `--kadenz-control-size` | `2.75rem` (44 px) |
| `--kadenz-control-color` | `currentColor` |
| `--kadenz-control-background` | `color-mix(in srgb, Canvas 85%, transparent)` |
| `--kadenz-control-radius` | `999px` |
| `--kadenz-dot-size` | `0.75rem` |
| `--kadenz-focus-color` | `currentColor` |
| `--kadenz-transition-duration` | `600ms` (bei reduced-motion `0ms`) |

Intern (`--_kadenz-*`): `--_kadenz-per-view` ist der aktuell gültige Wert, gesetzt über Container Queries.

### 7.2 Container-Breakpoints

Der Wrapper hat `container-type: inline-size; container-name: kadenz-slider`.
`md` gilt ab `@container kadenz-slider (min-width: 600px)`, `lg` ab `(min-width: 1000px)`.

### 7.3 Zustandsklassen

`is-effect-slide|fade`, `is-height-aspect|min|auto`, `has-controls-overlay|below`, `has-autoplay`,
`is-single`, `is-ready` (JS aktiv), `is-playing`; auf Slides `is-active`.
Steuerelemente sind ohne `is-ready` per `display: none` versteckt.

## 8. PHP-Hooks [öffentlich]

| Hook | Typ | Signatur |
|---|---|---|
| `kadenz_slider_default_attributes` | Filter | `array $defaults` → `array` |
| `kadenz_slider_render_context` | Filter | `array $context, array $attributes, WP_Block $block` → `array` |

Weitere öffentliche Hooks kommen nur per Vertragsänderung hinzu.

## 9. i18n

- Quellstrings sind Englisch, die Text-Domain ist `kadenz-slider`.
- Editor-Strings laufen über `@wordpress/i18n`; Übersetzungen lädt `block.json` (`textdomain`) automatisch.
- Frontend-JS-Strings kommen ausschließlich über den globalen State (6.2).
- Strings in PHP: `__()` / `_x()` mit Übersetzer-Kommentaren bei Platzhaltern.

## 10. Browser-Unterstützung

Grundlage ist `@wordpress/browserslist-config`. Ohne Polyfills müssen funktionieren: Scroll-Snap,
Container Queries, `inert` und `ResizeObserver`. `scrollend` ist optional und hat den Fallback aus 6.5.
Fehlen Container Queries, gilt `--kadenz-per-view-base`.
