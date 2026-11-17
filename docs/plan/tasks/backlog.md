# Backlog 1.x (nach 1.0)

Grob vorgeplante Pakete. Wer eins übernimmt, macht zuerst daraus eine vollständige Task-Datei nach dem
Muster von T01–T20 (Frontmatter, Umfang, Umsetzung, Akzeptanzkriterien) und ergänzt bei Bedarf contracts.md.

## B01 – Beitrags-Karussell (Query-Loop-Integration)

- **Ziel**: Slides aus Beiträgen, Produkten oder Custom Post Types.
- **Ansatz**: Eine Block-Variation von `core/query`, deren `core/post-template` als Slide-Track gerendert wird.
  Ein Filter auf `render_block_core/post-template` erkennt die Variation (Namespace-Attribut, z. B.
  `namespace: 'kadenz/post-carousel'`) und wickelt die Ausgabe in das Slider-Markup aus contracts.md §5. Dafür
  wird der Render-Teil von T07 zu einer wiederverwendbaren Funktion
  `Kadenz\Slider\Renderer::render( array $attrs, array $slides_html )` extrahiert.
- **Offene Frage**: Wo leben die Slider-Attribute? Vorschlag: in einem Wrapper-`kadenz/slider` mit
  `allowedBlocks: ['kadenz/slide', 'core/query']`, der die Query-Ausgabe statt der Slides verwendet.

## B02 – Shortcode für Synced Patterns

- `[kadenz_slider pattern="123"]` rendert einen Synced Pattern per `do_blocks()`, aber nur, wenn das Pattern
  einen `kadenz/slider` enthält und der Post-Status `publish` ist.
- Zielgruppe: Classic Editor, Widgets, Page Builder.
- Im Editor eines Synced Patterns gibt es einen Hinweis-Block bzw. ein Panel mit „Shortcode kopieren“.

## B03 – Thumbnail-Navigation

- Zweiter Slider als Steuerung des ersten (`controls: "<id>"`-Attribut am Thumbnail-Slider).
- Kommunikation ausschließlich über die öffentlichen DOM-Events `kadenz:change` und `kadenz:goto`. Das ist
  gleichzeitig ein Praxistest der Event-API.
- A11y: Thumbnails als Buttons mit `aria-controls` und `aria-current`.

## B04 – Ticker-/Marquee-Modus

- `effect: "ticker"`: kontinuierliche CSS-Animation (`translateX` per `@keyframes`), Inhalte einmal echt und
  einmal als `aria-hidden="true"` + `inert`-Duplikat.
- Pflicht: Pause-Button, Stopp bei Hover und Fokus, unter reduced-motion **kein** Laufen (statische, umbrechende Liste).
- Budget: kein zusätzliches JS außer dem Toggle.

## B05 – Datenschutzfreundliche Video-Slides

- Für `core/embed` (YouTube, Vimeo) in Slides: Klick-zum-Laden-Fassade mit dem Vorschaubild als lokal
  gecachtem Attachment (einmaliger Server-Download beim Speichern, **kein** Client-Request an YouTube vor dem Klick).
- Beim Slide-Wechsel pausieren laufende Videos (`postMessage`-API bzw. `video.pause()` für `core/video`).
- Autoplay pausiert, solange ein Video läuft.

## Ideen ohne Termin

- Vertikaler Slider (`scroll-snap-type: y`), z. B. für Stories
- CSS-only-Steuerung mit `::scroll-button()` / `::scroll-marker`, sobald breit unterstützt (siehe system-design §8)
- Lightbox-Integration mit dem Core-Image-Lightbox
- Deep-Linking (`#slider-id/3`)
