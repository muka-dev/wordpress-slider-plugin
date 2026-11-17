# System Design: Werbefreies Open-Source Slider-Plugin für WordPress

> Status: **Freigegeben v1.0** · Name: **Kadenz Slider** · Umsetzungsplanung: [`docs/plan/`](plan/README.md)
>
> Bei Abweichungen zwischen diesem Dokument und [`docs/plan/contracts.md`](plan/contracts.md) gilt **contracts.md**.

---

## 0. Warum noch ein Slider?

Die verbreiteten Slider-Plugins haben fast alle dasselbe Problem: Sie sind Werbeträger für ihre Pro-Version
(Admin-Notices, gesperrte Features, Upsell-Banner), laden jQuery plus 100 KB+ JavaScript auf jeder Seite,
speichern Inhalte in eigenen Tabellen oder Shortcode-Blobs und sind für Screenreader- und Tastaturnutzer
oft unbenutzbar.

Dieses Plugin soll das Gegenteil sein:

| Prinzip | Konkret |
|---|---|
| **Keine Werbung, kein Upsell** | Keine Admin-Notices, keine Pro-Version, keine gesperrten Features, keine Telemetrie, keine externen Requests. |
| **WordPress-nativ** | Gutenberg-Blöcke, Interactivity API, Block Supports, `theme.json`. Kein eigener Editor, keine eigene Datenbank. |
| **Performance zuerst** | CSS Scroll-Snap als Fundament, JS nur als Erweiterung. Budget: **≤ 6 KB JS / ≤ 4 KB CSS (gzip)**, nur geladen, wenn der Block auf der Seite ist. |
| **Barrierefrei per Default** | WAI-ARIA APG Carousel Pattern, WCAG 2.2 AA, `prefers-reduced-motion`, Pause-Button bei Autoplay ist Pflicht. |
| **Progressive Enhancement** | Ohne JavaScript bleibt ein wischbarer, scrollbarer Slider mit allen Inhalten. |
| **Design-Freiheit über das Theme** | Farben, Abstände, Typografie kommen aus dem Theme. Gestaltung über CSS Custom Properties, nicht über 200 Optionen. |

---

## 1. Anforderungen

### 1.1 Funktionale Anforderungen

**MVP (v0.x)**

- F1 – Slider-Block (`kadenz/slider`) mit beliebig vielen Slide-Blöcken (`kadenz/slide`).
- F2 – Jeder Slide ist ein Container für **beliebige Blöcke** (Bild, Cover, Überschrift, Buttons, Video …).
- F3 – Effekte: `slide` (horizontal) und `fade`.
- F4 – Slides pro Ansicht (1–6), responsiv nach **Container-Breite**, Abstand (Gap), optional „Peek“ des nächsten Slides.
- F5 – Navigation: Pfeile, Paginierung (Punkte / Zahlen / Fortschrittsbalken), Tastatur, Touch/Swipe, Trackpad.
- F6 – Autoplay mit Intervall, Pause bei Hover/Fokus, **sichtbarer Pause/Play-Button**.
- F7 – Ende-Verhalten: `stop` oder `rewind` (zurück zum Anfang).
- F8 – Höhenmodus: Seitenverhältnis (z. B. 16:9), Mindesthöhe oder „an Inhalt anpassen“.
- F9 – Block-Variationen als Startpunkte: *Hero*, *Karussell*, *Testimonials*, *Logo-Leiste*, *Galerie*.
- F10 – Wiederverwendbarkeit über **Synced Patterns** (`wp_block`), kein eigener Post-Type.

**v1.x**

- F11 – Dynamische Slides aus Beiträgen (Query-Loop-Integration: „Beitrags-Karussell“).
- F12 – Shortcode `[kadenz_slider pattern="123"]` für Classic Editor, Widgets und Page Builder.
- F13 – Thumbnail-Navigation (zweiter Slider als Steuerung des ersten).
- F14 – Datenschutzfreundliche Video-Embeds (Klick-zum-Laden-Fassade für YouTube/Vimeo).
- F15 – Ticker/Marquee-Modus für Logo-Leisten (kontinuierlich, pausierbar).

### 1.2 Nicht-funktionale Anforderungen

| Kategorie | Ziel |
|---|---|
| Payload | ≤ 6 KB JS + ≤ 4 KB CSS gzip, Frontend; **0 Byte** auf Seiten ohne Slider |
| Core Web Vitals | Kein CLS durch den Slider (Platz wird per CSS reserviert), LCP-Bild im ersten Slide wird nicht lazy geladen |
| Interaktion | Kein Long Task > 50 ms durch Initialisierung, INP < 100 ms bei Navigation |
| Barrierefreiheit | WCAG 2.2 AA, automatisiert (axe) + manuell (NVDA, VoiceOver, nur Tastatur) getestet |
| Kompatibilität | Block- und Classic-Themes, RTL, Multisite, Seiten-Caches/CDNs (keine Nonces, kein Server-State) |
| Datenschutz | Keine externen Requests, keine Cookies, kein Tracking → DSGVO-neutral |
| Wartbarkeit | Kein Block-Validation-Bruch bei Updates (dynamisches Rendering des Wrappers) |
| i18n | Vollständig übersetzbar via translate.wordpress.org, inklusive JS-Strings |

### 1.3 Rahmenbedingungen

- Kleines Kernteam / Community-Projekt → so wenig eigener Code wie möglich, maximal auf Core aufbauen.
- Minimum: **WordPress 6.6**, **PHP 7.4** (Interactivity API + `viewScriptModule` stabil; Block-Support `background` für Container verfügbar).
- Build mit `@wordpress/scripts`, keine weiteren Frontend-Frameworks.
- Lizenz: **GPL-2.0-or-later** (WordPress-Standard).

### 1.4 Bewusste Nicht-Ziele

- Kein Ebenen-/Timeline-Animationseditor à la „Revolution“. Wer Keyframe-Animationen pro Element will, ist hier falsch.
- Keine eigenen Datenbanktabellen, keine Optionsseite, kein Admin-Menü.
- Kein jQuery, keine Abhängigkeit zu Swiper/Slick/Splide.
- Keine Pro-Version. Nie.

---

## 2. High-Level-Design

### 2.1 Komponenten

```
┌───────────────────────────── Editor (wp-admin) ─────────────────────────────┐
│                                                                             │
│  kadenz/slider  (edit.js)                                                   │
│   ├─ InspectorControls: Layout · Verhalten · Navigation · Barrierefreiheit │
│   ├─ BlockControls: Slide hinzufügen / duplizieren / verschieben            │
│   ├─ Editor-Track: zeigt aktiven Slide, Autoplay im Editor AUS              │
│   └─ InnerBlocks (allowedBlocks: kadenz/slide)                              │
│        └─ kadenz/slide  (edit.js)  → InnerBlocks: beliebige Core-Blöcke     │
│                                                                             │
│  Variationen: Hero · Karussell · Testimonials · Logos · Galerie             │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                │ serialisierte Block-Markup in post_content
                                ▼
┌──────────────────────────── Server (PHP) ───────────────────────────────────┐
│  register_block_type( build/slider ) / ( build/slide )                      │
│  render.php (slider):                                                       │
│   ├─ Attribute validieren & clampen                                         │
│   ├─ Wrapper + Track + Steuerelemente rendern                               │
│   ├─ Interactivity-Direktiven + Kontext (wp_interactivity_data_wp_context)  │
│   └─ CSS Custom Properties (--kadenz-per-view, --kadenz-gap …)              │
│  render.php (slide):                                                        │
│   └─ role/aria via WP_HTML_Tag_Processor, Lazy-Loading-Strategie            │
│  (v1.x) Shortcode → do_blocks( synced pattern )                             │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                │ HTML + view.js (Script Module, nur bei Bedarf)
                                ▼
┌──────────────────────────── Browser ────────────────────────────────────────┐
│  Ebene 0 (ohne JS): CSS Scroll-Snap-Track → wischbar, scrollbar, vollständig │
│  Ebene 1 (Interactivity API Store "kadenz/slider"):                         │
│   ├─ Pfeile, Paginierung, Tastatur                                          │
│   ├─ Index-Sync via scrollend / IntersectionObserver                        │
│   ├─ Autoplay-Timer + Pause-Logik + reduced-motion                          │
│   ├─ inert/aria-hidden für nicht sichtbare Slides                           │
│   └─ Fade-Modus (schaltet Track per Klasse um)                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Datenfluss

1. Redakteur baut Slider im Editor → Gutenberg serialisiert Block-Markup in `post_content`.
2. Beim Seitenaufruf rendert `render.php` den **Wrapper dynamisch**; der Inhalt der Slides ist normales,
   statisch gespeichertes Core-Block-Markup.
3. WordPress reiht `style.css` und das Script Module `view.js` **nur ein, wenn der Block gerendert wird**
   (Standardverhalten von `block.json` – kein eigenes Conditional Loading nötig).
4. Die Interactivity API hydriert alle Slider-Instanzen mit **einem** gemeinsamen Store; jede Instanz hat ihren
   eigenen Kontext.

### 2.3 Speicher

| Was | Wo | Warum |
|---|---|---|
| Slider-Inhalt & Einstellungen | Block-Attribute in `post_content` | Portabel, versioniert über Revisionen, Export/Import gratis, keine Migration |
| Wiederverwendbare Slider | Synced Patterns (`wp_block`) | Core-Feature, eigene UI im Site Editor, kein eigener CPT |
| Globale Design-Defaults | `theme.json` → `settings.custom.kadenz` bzw. CSS Custom Properties | Theme-Autoren steuern Optik ohne Plugin-Optionen |
| Plugin-Optionen | **keine** | Nichts zu migrieren, nichts aufzuräumen, `uninstall.php` überflüssig |

### 2.4 Öffentliche „API“-Verträge

Ein Plugin ohne Server-Endpunkte hat trotzdem Verträge, die stabil bleiben müssen:

1. **Block-Namen & Attribute** (`kadenz/slider`, `kadenz/slide`) – Änderungen nur mit `deprecated`-Einträgen.
2. **CSS Custom Properties** (`--kadenz-*`) – öffentliche Theming-API, semver-relevant.
3. **CSS-Klassen** mit BEM-Präfix `.kadenz-slider__*` – dokumentiert, Theme-Overrides erlaubt.
4. **PHP-Filter/Actions** (klein halten):
   - `kadenz_slider_render_context` – Kontext vor dem Rendern anpassen.
   - `kadenz_slider_default_attributes` – Defaults site-weit ändern.
5. **DOM-Events** auf dem Wrapper: `kadenz:change` (`detail: { index, previous }`), `kadenz:autoplay` (`detail: { playing }`) – für Analytics oder Drittintegration, ohne dass wir selbst tracken.
6. **Interactivity-Store** `kadenz/slider` – Actions `next`, `prev`, `goTo`, `toggleAutoplay` für Erweiterungen (z. B. Thumbnail-Sync).

---

## 3. Deep Dive

### 3.1 Datenmodell (block.json-Attribute)

**`kadenz/slider`**

```jsonc
{
  "label":          { "type": "string", "default": "" },          // aria-label, Pflichtfeld-Hinweis im Editor
  "effect":         { "type": "string", "enum": ["slide", "fade"], "default": "slide" },
  "perView":        { "type": "object", "default": { "base": 1, "md": 1, "lg": 1 } },
  "gap":            { "type": "string", "default": "var:preset|spacing|40" },
  "peek":           { "type": "string", "default": "0px" },        // Anschnitt des nächsten Slides
  "heightMode":     { "type": "string", "enum": ["aspect", "min", "auto"], "default": "aspect" },
  "aspectRatio":    { "type": "string", "default": "16/9" },
  "minHeight":      { "type": "string", "default": "" },
  "autoplay":       { "type": "boolean", "default": false },
  "interval":       { "type": "number",  "default": 6000 },        // ms, server-seitig auf 2000–30000 geclampt
  "pauseOnHover":   { "type": "boolean", "default": true },
  "endBehavior":    { "type": "string", "enum": ["stop", "rewind"], "default": "stop" },
  "showArrows":     { "type": "boolean", "default": true },
  "pagination":     { "type": "string", "enum": ["none", "dots", "numbers", "progress"], "default": "dots" },
  "controlsPosition": { "type": "string", "enum": ["overlay", "below"], "default": "overlay" }
}
```

Zusätzlich über **Block Supports** (kein eigener Code): `align` (wide/full), `spacing`, `color`, `border`,
`shadow`, `anchor`, `className`.

**`kadenz/slide`**

```jsonc
{
  "label": { "type": "string", "default": "" }   // optionaler eigener Name, sonst „Slide 3 von 7“
}
```

Alles andere kommt aus **Block Supports**: `background` (Bild, Größe, Position – Core seit 6.5/6.6),
`color` (inkl. Gradient), `spacing`, `layout` (flex/constrained, inkl. Inhaltsposition),
`dimensions.minHeight`. Für echte Hero-Slides mit Overlay und Fokuspunkt prefillt die Variation
„Hero“ jeden Slide mit einem `core/cover`. **Wir bauen keinen zweiten Cover-Block.**

> **Trade-off:** Ein „fetter“ Slide-Block mit eigenem Hintergrund-Medien-Handling wäre für Einsteiger
> ein Klick weniger. Dafür müssten wir Fokuspunkt, Overlay, Video-Hintergrund, Duotone usw. selbst pflegen
> und würden bei jeder Core-Verbesserung hinterherhinken. Wiederverwendung von Core gewinnt.

### 3.2 Rendering-Strategie: Hybrid

- **Slider-Wrapper: dynamisch** (`render.php`, `save` liefert nur `<InnerBlocks.Content />`).
  Damit können wir Markup, ARIA und Steuerelemente in jeder Version verbessern, **ohne** dass bestehende
  Inhalte „Dieser Block enthält unerwarteten Inhalt“ melden.
- **Slide: dynamisch-dünn** (`render.php` rendert nur den Wrapper). Weil ein Slide seinen Index nicht kennt,
  rendert der **Slider** seine Slides selbst (`$block->inner_blocks`) und ergänzt dabei `role`,
  `aria-roledescription`, `aria-label` („3 von 7“) sowie Lazy-Loading-Hinweise via `WP_HTML_Tag_Processor`.

Gerenderte Struktur (vereinfacht):

```html
<section class="wp-block-kadenz-slider kadenz-slider is-effect-slide"
         aria-roledescription="carousel" aria-label="Neuigkeiten"
         data-wp-interactive="kadenz/slider"
         data-wp-context='{"index":0,"count":5,"autoplay":true,"playing":true,"interval":6000}'
         data-wp-init="callbacks.init"
         style="--kadenz-per-view-base:1;--kadenz-per-view-md:2;--kadenz-gap:1.5rem;--kadenz-aspect:16/9">

  <div class="kadenz-slider__controls">
    <button class="kadenz-slider__toggle" data-wp-on--click="actions.toggleAutoplay"
            data-wp-bind--aria-label="state.toggleLabel">…</button>
  </div>

  <div class="kadenz-slider__track" id="kadenz-1-track" aria-live="off"
       data-wp-bind--aria-live="state.liveMode" tabindex="0">
    <div class="kadenz-slide" role="group" aria-roledescription="slide" aria-label="1 von 5">…</div>
    …
  </div>

  <button class="kadenz-slider__arrow is-prev" aria-controls="kadenz-1-track"
          aria-label="Vorheriger Slide" data-wp-on--click="actions.prev"
          data-wp-bind--disabled="state.isFirst">…</button>
  <button class="kadenz-slider__arrow is-next" …>…</button>

  <div class="kadenz-slider__pagination">
    <button aria-label="Slide 1 von 5" data-wp-bind--aria-current="state.isCurrent"
            data-wp-on--click="actions.goTo">…</button>
    …
  </div>
</section>
```

Steuerelemente werden **erst nach Hydration sichtbar** (`.kadenz-slider:not(.is-ready) .kadenz-slider__arrow { display:none }`),
damit ohne JS keine toten Buttons herumstehen.

### 3.3 Frontend-Mechanik

**Fundament: CSS Scroll-Snap**

```css
.kadenz-slider { container-type: inline-size; }
.kadenz-slider__track {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: calc((100% - (var(--_per-view) - 1) * var(--kadenz-gap) - var(--kadenz-peek)) / var(--_per-view));
  gap: var(--kadenz-gap);
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  overscroll-behavior-x: contain;
  scrollbar-width: none;
}
.kadenz-slide { scroll-snap-align: start; aspect-ratio: var(--kadenz-aspect, auto); }

/* Responsiv nach Container, nicht nach Viewport */
.kadenz-slider { --_per-view: var(--kadenz-per-view-base, 1); }
@container (min-width: 600px)  { .kadenz-slider__track { --_per-view: var(--kadenz-per-view-md, …); } }
@container (min-width: 1000px) { .kadenz-slider__track { --_per-view: var(--kadenz-per-view-lg, …); } }

@media (prefers-reduced-motion: no-preference) {
  .kadenz-slider__track { scroll-behavior: smooth; }
}
```

Vorteile: native Touch-Physik, Trackpad- und Mausrad-Support, RTL ohne Sonderlogik, keine Layout-Berechnung in JS,
kein Jank, funktioniert ohne JS. Der Slider in einer schmalen Sidebar zeigt automatisch weniger Slides als
im Full-Width-Bereich – das schafft keine Viewport-Media-Query.

**Navigation (JS):** `goTo(i)` berechnet die Zielposition über
`slide.getBoundingClientRect().left - track.getBoundingClientRect().left + track.scrollLeft` und ruft
`track.scrollTo({ left })` auf. Das ist RTL-sicher und scrollt – anders als `scrollIntoView` – nie die Seite.

**Index-Sync:** Beim Wischen ist der Browser die Quelle der Wahrheit. `scrollend` (Fallback: debounced `scroll`)
ermittelt den nächsten Snap-Punkt und setzt `context.index`. Ein `IntersectionObserver` (threshold 0.6) markiert
sichtbare Slides; alle anderen bekommen `inert`, damit Tab-Fokus nicht in unsichtbare Slides springt.

**Fade-Effekt:** JS setzt `.is-effect-fade.is-ready`; CSS stapelt die Slides in **einer** Grid-Zelle
(`grid-area: 1/1`) und blendet über `opacity` + `visibility` (Transition, bei reduced-motion 0 ms).
Ohne JS greift der Scroll-Snap-Fallback – nichts ist versteckt.

**Autoplay:**

- Ein `setTimeout`-Zyklus pro Instanz (kein `setInterval`, kein Drift bei Tab-Wechsel).
- Pausiert bei: Hover (optional), Fokus innerhalb des Sliders, `document.hidden`, Slider außerhalb des
  Viewports (IntersectionObserver), manueller Interaktion (Wischen/Klick → Autoplay bleibt aus, bis der Nutzer
  Play drückt – APG-Empfehlung).
- `prefers-reduced-motion: reduce` → Autoplay startet **nicht** automatisch.
- Fortschrittsbalken über CSS-Animation mit `animation-duration: var(--kadenz-interval)` und
  `animation-play-state` – kein JS-Frame-Loop.

**„Endlos“-Schleife:** Bewusst nur `rewind`, keine geklonten Slides im MVP.

> **Trade-off:** Echte Endlos-Loops brauchen Klone (doppelte Inhalte für Screenreader, doppelte IDs,
> doppelte Videos/Formulare) oder DOM-Umsortierung während des Scrollens (zappelig mit Scroll-Snap).
> `rewind` ist zugänglich, robust und für 95 % der Fälle ausreichend. Für Logo-Leisten kommt in v1.x ein
> separater Ticker-Modus (CSS-Animation, Inhalte einmal + `aria-hidden`-Duplikat).

### 3.4 Barrierefreiheit (WAI-ARIA APG Carousel)

| Anforderung | Umsetzung |
|---|---|
| Container identifizierbar | `<section aria-roledescription="carousel" aria-label="…">`; Editor warnt bei leerem Label |
| Slides identifizierbar | `role="group" aria-roledescription="slide" aria-label="3 von 7"` (übersetzbar) |
| Autoplay steuerbar (WCAG 2.2.2) | Pause/Play-Button ist **erstes** fokussierbares Element im Slider, nicht abschaltbar, wenn Autoplay aktiv |
| Live-Region | Separate, visuell versteckte Status-Region (`aria-live="polite"`), wird nur bei manueller Navigation befüllt, nie durch Autoplay |
| Fokus-Pause | Autoplay stoppt, sobald Fokus im Slider ist |
| Unsichtbare Slides | `inert` → kein Tab-Fokus, für AT ausgeblendet |
| Tastatur | Track ist fokussierbar (`tabindex="0"`), Pfeiltasten scrollen nativ; Buttons per Tab erreichbar |
| Zielgröße (WCAG 2.5.8) | Alle Buttons ≥ 24×24 CSS-px, Default 44×44 |
| Kontrast | Pfeile/Punkte nutzen `currentColor` + Hintergrund-Plate; Theme-Farben über Custom Properties |
| Bewegung | `prefers-reduced-motion` → kein Smooth-Scroll, keine Fade-Dauer, kein Auto-Start |
| Paginierung | `<button aria-current="true">` statt Tablist (APG „Basic“-Variante; weniger Tastatur-Fallen) |

### 3.5 Performance-Details

- **Asset-Loading:** `viewScriptModule` + `style` in `block.json` → WordPress lädt nur bei gerendertem Block.
  `editorScript`/`editorStyle` landen nie im Frontend.
- **Bilder:** Core liefert `srcset`/`sizes`. Wir setzen für Slides ≥ 2 `loading="lazy"`; für Slide 1 entfernen
  wir `loading="lazy"` und setzen bei Hero-Slidern `fetchpriority="high"` (nur wenn der Slider der erste
  Block im Content ist – sonst ist es nicht das LCP-Element). Umsetzung über `WP_HTML_Tag_Processor` im Slide-Render.
- **`sizes` korrigieren:** Bei `perView > 1` ist das Core-`sizes` (100vw) zu groß. Wir berechnen ein
  passenderes `sizes` aus `perView` und Ausrichtung → spürbar weniger Bytes bei Karussells.
- **CLS:** Höhe steht durch `aspect-ratio`/`min-height` vor dem Laden der Bilder fest; Steuerelemente sind
  absolut positioniert (overlay) oder haben reservierte Höhe (below).
- **Mehrere Slider pro Seite:** Ein Store, n Kontexte, je ein IntersectionObserver pro Instanz;
  Slider außerhalb des Viewports haben keinen aktiven Timer.
- **Budget in CI:** `size-limit` schlägt fehl, wenn `view.js` > 6 KB oder `style.css` > 4 KB gzip.

### 3.6 Editor-UX

- Im Editor ist der Track **kein** Scroll-Snap-Karussell, sondern zeigt den **ausgewählten Slide**;
  Navigation über Mini-Toolbar („‹ Slide 2/5 ›“, Hinzufügen, Duplizieren, Löschen) und über die List View.
- Wird ein Block innerhalb eines Slides ausgewählt, springt die Ansicht automatisch zu diesem Slide.
- Autoplay läuft im Editor nie. Optionaler „Vorschau“-Toggle in der Toolbar.
- Inspector gruppiert in **Layout**, **Verhalten**, **Navigation**, **Barrierefreiheit** – mit vernünftigen Defaults,
  sodass ein frisch eingefügter Slider ohne Einstellung gut aussieht.
- Variationen erscheinen im Inserter mit eigenen Icons und Mustern (Hero mit Cover, Testimonials mit Quote,
  Logos mit Bildern ohne Pfeile und 5 pro Ansicht usw.).

### 3.7 Fehlerbehandlung & Robustheit

- Server: alle numerischen Attribute werden geclampt (`perView` 1–6, `interval` 2000–30000); unbekannte Enums
  fallen auf Default zurück. Alle Ausgaben via `esc_attr`/Tag Processor – keine String-Konkatenation von HTML.
- 0 Slides → Slider rendert nichts (im Editor Platzhalter). 1 Slide → keine Steuerelemente, kein Autoplay.
- JS-Fehler oder blockiertes Script → Ebene 0 (Scroll-Snap) bleibt voll funktionsfähig.
- Kein AJAX, keine REST-Endpunkte, keine Nonces → Seiten-Caching und CDN-Auslieferung problemlos.

### 3.8 Sicherheit

- Keine eigenen Endpunkte, keine Optionen, kein Datei-Upload → minimale Angriffsfläche.
- Inhalte der Slides durchlaufen die normale Core-Pipeline (`kses` für Nutzer ohne `unfiltered_html`).
- Kontext-JSON via `wp_interactivity_data_wp_context()` (korrekt escaped).
- `kadenz/slider`-Attribut `label` wird als Text behandelt, nie als HTML.

---

## 4. Skalierung & Zuverlässigkeit

„Skalierung“ heißt bei einem Plugin: **auf hunderttausenden sehr unterschiedlichen Sites zuverlässig laufen.**

| Szenario | Annahme | Design-Antwort |
|---|---|---|
| Viele Slides (50+) | Galerien, Produkt-Karussells | Lazy Images, kein Klonen, IntersectionObserver statt Scroll-Handler pro Slide |
| Viele Slider pro Seite (10+) | Landingpages | Ein Store, Timer nur für sichtbare Instanzen |
| Schwache Geräte | Low-End-Android | Scrollen ist nativ (Compositor-Thread), JS reagiert nur auf `scrollend` |
| Seiten-Caches/CDN | WP Rocket, Cloudflare, Varnish | Kein Server-State, keine Nonces, keine personalisierten Ausgaben |
| Aggressive Optimierungs-Plugins | JS-Defer/Delay, CSS-Combine | Ebene 0 funktioniert ohne JS; Module sind defer-sicher |
| Theme-Konflikte | `overflow:hidden`, eigene `button`-Styles | Spezifität niedrig halten (`:where()`), Reset nur auf eigene Elemente |
| Updates | Jahrelang bestehende Inhalte | Dynamischer Wrapper + `deprecated`-Pfade für Attributänderungen |

**Monitoring ohne Telemetrie:** Wir sammeln bewusst keine Nutzungsdaten. Qualitätssignale kommen aus
CI (Tests, Budgets, Lighthouse), GitHub-Issues, wp.org-Support-Forum und einer öffentlichen Demo-Seite,
die bei jedem Release automatisch mit Lighthouse/axe geprüft wird.

---

## 5. Projektstruktur & Tooling

```
kadenz-slider/
├── kadenz-slider.php          # Plugin-Header, register_block_type, (v1) Shortcode
├── readme.txt                 # wp.org
├── src/
│   ├── slider/
│   │   ├── block.json
│   │   ├── edit.js            # Editor-UI
│   │   ├── save.js            # nur <InnerBlocks.Content />
│   │   ├── render.php         # dynamischer Wrapper
│   │   ├── view.js            # Interactivity-Store (Script Module)
│   │   ├── variations.js
│   │   ├── style.scss         # Frontend + Editor
│   │   └── editor.scss
│   └── slide/
│       ├── block.json
│       ├── edit.js
│       ├── save.js
│       └── render.php
├── includes/
│   ├── class-render-helpers.php   # sizes-Berechnung, Lazy/Fetchpriority, Clamping
│   └── class-shortcode.php        # v1.x
├── languages/
├── tests/
│   ├── php/                   # PHPUnit (wp-env)
│   ├── js/                    # Jest: Store-Logik (Index, Autoplay-State)
│   └── e2e/                   # Playwright + axe
└── .github/workflows/         # lint, test, e2e, size-limit, plugin-check, release
```

**Qualitätssicherung**

- PHPCS mit WordPress Coding Standards, ESLint/Stylelint via `@wordpress/scripts`.
- PHPUnit für Render-Ausgabe (Snapshot des HTML, ARIA-Attribute, Clamping).
- Jest für reine Store-Logik.
- Playwright (`@wordpress/e2e-test-utils-playwright`): Einfügen im Editor, Frontend-Navigation per Maus,
  Tastatur und Touch-Emulation, reduced-motion, RTL; **axe-core** auf jeder Frontend-Seite.
- **Plugin Check (PCP)** in CI – Voraussetzung für wp.org.
- `size-limit` für Payload-Budget, Lighthouse CI auf Demo-Seite.
- Matrix: WP min/latest/nightly × PHP 7.4/8.x.

---

## 6. Trade-off-Übersicht

| Entscheidung | Gewählt | Alternative | Warum |
|---|---|---|---|
| Speicherort | Block-Markup + Synced Patterns | Eigener CPT + Shortcode | Core-nativ, Revisionen, Export, keine Migration. Nachteil: Slider nicht zentral „verwaltet“ wie in einer Bibliothek – Synced Patterns decken das ab. |
| Frontend-Engine | Scroll-Snap + Interactivity API | Swiper/Splide einbinden | 6 KB statt 40 KB+, native Physik, kein Fremd-Lifecycle. Nachteil: Effekte wie 3D-Cube/Coverflow fallen weg – gewollt. |
| Rendering | Dynamischer Wrapper | Rein statisches `save` | Keine Block-Validation-Fehler bei Updates. Nachteil: minimal PHP pro Request (vernachlässigbar, gecacht). |
| Slide-Hintergrund | Block Supports + `core/cover` in Variationen | Eigenes Medien-Handling | Weniger Code, Core-Verbesserungen gratis. Nachteil: für Hero ein Block mehr in der Hierarchie. |
| Loop | `rewind` | Klone für Endlos-Loop | Barrierefrei, robust. Nachteil: kein „nahtloses“ Weiterlaufen. |
| Responsivität | Container Queries | Viewport-Breakpoints | Slider passt sich seinem Platz an. Nachteil: sehr alte Browser (vor 2023) fallen auf `base` zurück – akzeptabel. |
| Paginierung | Buttons + `aria-current` | ARIA-Tabs | Einfacher, weniger fehleranfällig für AT. |
| Einstellungen | Pro Block + `theme.json`/CSS-Variablen | Globale Optionsseite | Kein Admin-UI, keine Optionen in der DB. |

---

## 7. Roadmap

| Version | Inhalt |
|---|---|
| **0.1** | Slider + Slide, Effekt `slide`, Pfeile, Punkte, Tastatur, Scroll-Snap-Fallback, ARIA, reduced-motion, CI-Grundgerüst |
| **0.2** | Autoplay inkl. Pause-Button & Fortschrittsbalken, `fade`, `rewind`, Höhenmodi, `sizes`-Optimierung |
| **0.3** | Variationen (Hero, Karussell, Testimonials, Logos, Galerie), Editor-Feinschliff, Demo-Seite |
| **1.0** | wp.org-Release, Übersetzungen, Doku (Nutzer + Theme-Entwickler), Plugin-Check sauber |
| **1.1** | Query-Loop/Beitrags-Karussell, Shortcode für Synced Patterns |
| **1.2** | Thumbnail-Navigation, Ticker-Modus, Video-Fassade |

---

## 8. Was wir beim Wachsen neu bewerten

- **Endlos-Loop:** Wenn die Nachfrage groß ist, prüfen, ob CSS `scroll-start`/Scroll-Driven Animations einen
  klonfreien Loop ermöglichen.
- **CSS-only-Steuerung:** Sobald `::scroll-button()` / `::scroll-marker` (CSS Overflow 5) breit unterstützt sind,
  können Pfeile und Punkte nativ aus CSS kommen – JS schrumpft dann auf Autoplay und Events.
- **Mindestversion WordPress:** jährlich auf „aktuelle minus zwei Major-Versionen“ anheben.
- **Erweiterungs-API:** Wenn Drittanbieter-Erweiterungen entstehen, Store-Actions und Events formal versionieren.
- **Page-Builder-Adapter:** Nur falls die Shortcode-Lösung nicht reicht – dann als separate Mini-Plugins,
  nicht im Kern.

---

## 9. Getroffene Entscheidungen

| # | Thema | Entscheidung |
|---|---|---|
| 1 | Name & Slug | **Kadenz Slider**, Slug/Text-Domain `kadenz-slider`, Block-Namespace `kadenz` |
| 2 | Lizenz | **GPL-2.0-or-later** (vorher AGPL-3.0) |
| 3 | Mindestversion | **WordPress 6.6**, **PHP 7.4** |
| 4 | Block-Präfix | `kadenz/slider`, `kadenz/slide` – ab 1.0 unveränderlich |
