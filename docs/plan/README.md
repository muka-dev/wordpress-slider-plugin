# Umsetzungsplan: Kadenz Slider

Dieser Plan zerlegt das [System Design](../system-design.md) in **eigenständige Aufgaben**. Jede Aufgabe kann
ein Agent oder Mensch ohne weiteres Vorwissen bearbeiten, wenn er diese Dateien gelesen hat:

1. [`/AGENTS.md`](../../AGENTS.md): Konventionen, Befehle, Arbeitsablauf
2. [`contracts.md`](contracts.md): **verbindliche** Schnittstellen (Namen, Attribute, Markup, Store, CSS, Events)
3. die eigene Task-Datei unter [`tasks/`](tasks/)

## Getroffene Entscheidungen

| Thema | Entscheidung |
|---|---|
| Name / Slug / Text-Domain | Kadenz Slider / `kadenz-slider` / `kadenz-slider` |
| Blöcke | `kadenz/slider`, `kadenz/slide` |
| Lizenz | GPL-2.0-or-later |
| Mindestversionen | WordPress 6.6, PHP 7.4 |
| Frontend | CSS Scroll-Snap + Interactivity API (Script Module) |
| Speicherung | Nur Block-Markup in `post_content`; Wiederverwendung über Synced Patterns |

## Aufgaben

| ID | Titel | Phase | Abhängig von |
|---|---|---|---|
| [T01](tasks/T01-scaffold.md) | Projekt-Scaffold & Build | 0 Fundament | – |
| [T02](tasks/T02-php-tooling.md) | PHP-Tooling: Composer, PHPCS, PHPUnit | 0 Fundament | T01 |
| [T03](tasks/T03-js-tooling.md) | JS-Tooling: Jest, Playwright, size-limit | 0 Fundament | T01 |
| [T04](tasks/T04-ci.md) | CI-Workflows (GitHub Actions) | 0 Fundament | T02, T03 |
| [T05](tasks/T05-attributes.md) | Attribut-Normalisierung & CSS-Variablen (PHP) | 1 MVP | T02 |
| [T06](tasks/T06-slide-block.md) | Slide-Block | 1 MVP | T01 |
| [T07](tasks/T07-slider-render.md) | Slider: Server-Rendering | 1 MVP | T05, T06 |
| [T08](tasks/T08-frontend-css.md) | Frontend-CSS | 1 MVP | T07 |
| [T09](tasks/T09-store-navigation.md) | Interactivity-Store: Navigation | 1 MVP | T03, T07 |
| [T10](tasks/T10-slider-editor.md) | Slider: Editor-UI | 1 MVP | T06 |
| [T11](tasks/T11-mvp-e2e.md) | MVP-E2E- & Barrierefreiheits-Suite | 1 MVP | T08, T09, T10 |
| [T12](tasks/T12-autoplay.md) | Autoplay | 2 Verhalten | T08, T09 |
| [T13](tasks/T13-fade.md) | Fade-Effekt | 2 Verhalten | T08, T09 |
| [T14](tasks/T14-images.md) | Bild-Optimierung (lazy, fetchpriority, sizes) | 2 Verhalten | T07 |
| [T15](tasks/T15-variations.md) | Block-Variationen | 3 Editor | T10 |
| [T16](tasks/T16-editor-polish.md) | Editor-Feinschliff | 3 Editor | T10 |
| [T17](tasks/T17-demo-playground.md) | Demo, Playground-Blueprint, Lighthouse CI | 3 Editor | T11, T15 |
| [T18](tasks/T18-i18n.md) | i18n & Übersetzungs-Pipeline | 4 Release | T07, T10 |
| [T19](tasks/T19-docs.md) | Nutzer- & Entwickler-Dokumentation | 4 Release | T12, T13, T15 |
| [T20](tasks/T20-release.md) | Release-Pipeline, readme.txt, wp.org | 4 Release | alle bis T19 |

Für die Zeit nach 1.0 sind fünf Pakete grob vorgeplant: [Backlog 1.x](tasks/backlog.md). Wer eins davon
übernimmt, macht daraus zuerst eine vollständige Task-Datei.

Der **Status** steht im Frontmatter der jeweiligen Task-Datei (`open` → `in-progress` → `review` → `done`),
nicht in dieser Tabelle. So gibt es keine Merge-Konflikte, wenn mehrere Agenten parallel arbeiten.

## Parallelisierung

```
Welle 1:  T01
Welle 2:  T02   T03   T06
Welle 3:  T04   T05   T10
Welle 4:  T07              (T15, T16 können ab hier parallel laufen)
Welle 5:  T08   T09   T14   T18
Welle 6:  T11   T12   T13
Welle 7:  T17   T19
Welle 8:  T20
```

Kritischer Pfad: T01 → T02 → T05 → T07 → T09 → T11 → T17 → T20.

Releases: **0.1** nach T11, **0.2** nach T12–T14, **0.3** nach T15–T17, **1.0** nach T20.

## Ablauf für einen Agenten

1. Wähle einen Task mit `status: open`, dessen Abhängigkeiten alle `done` sind.
2. Branch `task/<ID>-<kurzname>` von aktuellem `main` anlegen. Im Frontmatter `status: in-progress` und
   `owner: <dein Name/Session>` setzen und diesen Statuswechsel als **ersten** Commit pushen.
3. Umsetzen. Nur die in „Umfang“ genannten Dateien anfassen; Abweichungen im PR begründen.
4. Alle Punkte unter „Verifikation“ ausführen. Was lokal nicht geht (z. B. ohne Docker), im PR benennen.
5. `status: review` setzen und einen PR gegen `main` öffnen. Der Titel beginnt mit der Task-ID, die
   PR-Beschreibung hakt die Akzeptanzkriterien ab.
6. Nach dem Merge setzt der Reviewer `status: done`.

Ändert ein Task einen Vertrag in `contracts.md`, gehört die Änderung in denselben PR, wird in der
Beschreibung hervorgehoben, und alle betroffenen offenen Tasks werden darin genannt.

## Definition of Done (gilt für jeden Task)

- [ ] Alle Akzeptanzkriterien der Task-Datei erfüllt.
- [ ] `npm run build`, `npm run lint`, `composer lint` grün (sofern die Werkzeuge schon existieren).
- [ ] Neue Logik ist getestet (PHPUnit, Jest oder Playwright, je nach Ebene).
- [ ] Keine neuen Abhängigkeiten zur Laufzeit; Dev-Abhängigkeiten im PR begründet.
- [ ] Keine externen Requests, keine Admin-Notices, keine Optionen in der Datenbank.
- [ ] Alle UI-Strings übersetzbar (Text-Domain `kadenz-slider`).
- [ ] Frontend-Budget eingehalten (`npm run size`, ab T03).
- [ ] Bei UI-Änderungen: axe ohne Verstöße, Tastaturbedienung geprüft.
- [ ] `docs/` aktualisiert, falls sich Verhalten oder öffentliche API ändern.
