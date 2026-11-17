---
id: T19
title: Nutzer- & Entwickler-Dokumentation
phase: 4
status: open
owner:
depends_on: [T12, T13, T15]
---

# T19 – Nutzer- & Entwickler-Dokumentation

## Ziel

Gute Doku ist Teil des „Zurückgebens“. Redakteure, Theme-Entwickler und Mitwirkende finden jeweils schnell,
was sie brauchen.

## Umfang (Dateien)

- `docs/user-guide.md`: für Redakteure (Englisch, weil das die internationale Zielgruppe ist; die deutsche Fassung folgt per Übersetzung)
- `docs/theming.md`: für Theme-Entwickler (Englisch)
- `docs/developer-api.md`: Hooks, Events, Integration (Englisch)
- `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md` (Contributor Covenant 2.1), `SECURITY.md`
- `.github/ISSUE_TEMPLATE/bug_report.yml`, `feature_request.yml`, `config.yml`
- `README.md` (Überarbeitung: Features, Screenshots/GIF, Links)

## Inhalt

1. **user-guide.md**: Slider einfügen, Variationen, Slides verwalten, alle Einstellungen (mit Erklärung, *wann*
   man sie nutzt), Barrierefreiheits-Tipps (Label vergeben, Autoplay sparsam, Alt-Texte), Wiederverwendung über
   Synced Patterns, bekannte Grenzen (kein Endlos-Loop, Hintergrundbilder nicht lazy → `core/cover` nutzen).
2. **theming.md**: alle öffentlichen Custom Properties und Klassen aus [contracts.md §7](../contracts.md#7-css-api-öffentlich)
   mit Beispielen. Dazu ein Beispiel für `theme.json` (`styles.blocks["kadenz/slider"]`) und ein Beispiel für
   eigene Pfeil-Icons per CSS `mask`, Dark-Mode-Hinweise und die Garantie: „Public API follows semver.“
3. **developer-api.md**: PHP-Filter (§8) mit Beispielen, DOM-Events (§6.4) mit Beispielen (Analytics-Snippet,
   externer Steuer-Button), Hinweis, dass der Store intern ist.
4. **CONTRIBUTING.md**: Setup in 5 Minuten, Verweis auf AGENTS.md und docs/plan, Ablauf für PRs, Coding Standards,
   Hinweis zu Übersetzungen über translate.wordpress.org.
5. **SECURITY.md**: Meldung über GitHub Security Advisories (privat), keine E-Mail-Adresse im Repo.
6. **Issue-Templates**: Bug-Formular mit WP-Version, PHP-Version, Theme, Browser, Schritten und einem Feld
   „Barrierefreiheit betroffen?“.

## Akzeptanzkriterien

- [ ] Jede Einstellung aus §3.2 ist im User Guide erklärt.
- [ ] Jede öffentliche API aus contracts.md (§3, §5-Klassen, §6.4, §7, §8) ist dokumentiert.
- [ ] Alle Code-Beispiele sind getestet: CSS- und PHP-Snippets wurden in wp-env ausprobiert.
- [ ] Keine toten Links (`npx markdown-link-check` oder Ähnliches).

## Verifikation

Manuell nachvollziehen, ggf. Link-Check-Skript.
