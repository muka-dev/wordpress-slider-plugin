---
id: T12
title: Autoplay
phase: 2
status: open
owner:
depends_on: [T08, T09]
---

# T12 – Autoplay

## Ziel

Barrierefreies Autoplay nach WCAG 2.2.2 und WAI-ARIA APG. Der Nutzer hat immer die Kontrolle, und
Autoplay stört nie Menschen, die reduzierte Bewegung wünschen.

## Umfang (Dateien)

- `src/slider/view.js`, `src/slider/lib/autoplay.js` (reine Zustandslogik)
- `src/slider/style.scss` (Toggle-Zustände, optionaler Timer-Ring)
- `tests/js/autoplay.test.js`, `tests/e2e/autoplay.spec.js`

Das Markup des Toggles existiert bereits (T07).

## Verhalten (verbindlich)

`context.playing` ist **abgeleitet** und wird nach jeder Zustandsänderung neu berechnet:

```
playing = autoplay
       && !userStopped
       && !reducedMotionAtStart   // nur Startbedingung, siehe unten
       && !(pauseOnHover && hovered)
       && !focusWithin
       && inViewport
       && !document.hidden
       && count > 1
```

- `reducedMotionAtStart`: Ist beim Init `prefers-reduced-motion: reduce` aktiv, startet Autoplay nicht.
  Technisch heißt das: Init setzt `userStopped = true`. Drückt der Nutzer Play, läuft Autoplay trotzdem.
- `hovered` und `focusWithin` sind lokale Flags in der WeakMap (nicht im Kontext), gesetzt über
  `pointerenter`/`pointerleave` (nur bei `pointerType === 'mouse'`) und `focusin`/`focusout` (mit
  `relatedTarget`-Prüfung, ob der Fokus die Wurzel verlässt).
- **Ausnahme**: Hat der Fokus den Toggle-Button selbst, zählt das **nicht** als `focusWithin`. Sonst würde
  ein Klick auf Play sofort wieder pausieren.
- `inViewport` per `IntersectionObserver` (threshold 0.25), `document.hidden` per `visibilitychange`.
- **Manuelle Navigation** (Pfeil, Dot, Taste, Wischen, also `source` ∈ `user`, `scroll`) setzt
  `userStopped = true`. Das folgt der APG-Regel: Wer selbst navigiert, will keine Bewegung mehr.
- `actions.toggleAutoplay`: kehrt `userStopped` um.
- Eingehende Events `kadenz:play` und `kadenz:pause` setzen `userStopped` entsprechend.
- Ein Timer: `setTimeout( interval )`. Er wird bei jeder Index-Änderung und bei jedem Wechsel von `playing`
  neu gestartet und läuft nur, wenn `playing` gilt. Bei Ablauf: `nextIndex` mit erzwungenem Rewind am Ende
  (auch bei `endBehavior=stop`, weil Autoplay sonst „hängen bleibt“), `source: 'autoplay'`.
- Wechselt `playing`, wird `kadenz:autoplay { playing }` dispatcht.
- Autoplay setzt **nie** `announcement`.

## Darstellung

- Wrapper-Klasse `is-playing` (Direktive existiert bereits) steuert die Icons im Toggle.
- Optional (Design-Bonus, nur wenn es ins Budget passt): Ein Timer-Ring um den Toggle als
  `conic-gradient`-Animation mit `animation-duration: var(--kadenz-interval)`. Er wird bei jedem Slide-Wechsel
  per Klassenwechsel neu gestartet und ist unter reduced-motion aus.

## Testfälle

Jest (`lib/autoplay.js`): Wahrheitstabelle für `playing`; Toggle; „manuelle Navigation stoppt“.

E2E (mit `page.clock` von Playwright für kontrollierte Zeit):
- `autoplay: true, interval: 3000` → nach 3 s Slide 2, nach 6 s Slide 3; am Ende zurück zu Slide 1.
- Hover → kein Wechsel; nach dem Verlassen geht es weiter.
- Fokus auf einem Link im Slide → kein Wechsel.
- Klick auf Pause → `aria-label` wird zu „Start automatic slide show“, kein Wechsel mehr; Klick auf Play → weiter.
- Klick auf „Next“ → Autoplay stoppt dauerhaft, der Toggle zeigt Play.
- reduced-motion → kein Start; der Toggle zeigt „Start …“.
- Slider außerhalb des Viewports (weit unten auf der Seite) → kein Wechsel.
- Der Status-Text bleibt während Autoplay leer.
- axe ohne Verstöße mit Toggle.

## Akzeptanzkriterien

- [ ] Alle Testfälle grün.
- [ ] Der Toggle ist das erste fokussierbare Element im Slider.
- [ ] `view.js` gesamt ≤ 5 KB gzip.
- [ ] Bei 10 Slidern läuft nur der Timer der sichtbaren Slider (im E2E per Zähler über das `kadenz:change`-Event).

## Verifikation

```bash
npm run build && npm run size && npm run test:js && npm run test:e2e -- autoplay
```
