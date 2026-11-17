---
id: T20
title: Release-Pipeline, readme.txt, wp.org
phase: 4
status: open
owner:
depends_on: [T04, T11, T12, T13, T14, T15, T16, T17, T18, T19]
---

# T20 – Release-Pipeline, readme.txt, wp.org

## Ziel

Reproduzierbare Releases per Git-Tag: GitHub Release mit ZIP und Deployment ins wp.org-SVN, dazu ein
vollständiger und ehrlicher Plugin-Eintrag.

## Umfang (Dateien)

- `.github/workflows/release.yml`
- `readme.txt` (vollständig)
- `.wordpress-org/` (Banner 772×250 und 1544×500, Icon 128×128 und 256×256 plus SVG, Screenshots 1–5)
- `CHANGELOG.md`
- `bin/bump-version.mjs`: setzt die Version in `kadenz-slider.php` (Header + Konstante), `readme.txt` (Stable tag),
  `package.json` und `block.json` (`version`)

## Umsetzung

1. **Versionierung**: SemVer. `npm run version:bump -- 1.0.0` ruft `bin/bump-version.mjs` auf. Ein CI-Check
   prüft, dass alle Versionsangaben übereinstimmen.
2. **release.yml** (Trigger: Tag `v*.*.*`):
   - Build, Tests (Wiederverwendung von `ci.yml` per `workflow_call`), `npm run plugin-zip`
   - Prüfung: Tag == Version in allen Dateien
   - GitHub Release mit ZIP und Changelog-Abschnitt
   - wp.org-Deployment mit `10up/action-wordpress-plugin-deploy` (SVN). Benötigt die Secrets `SVN_USERNAME` und
     `SVN_PASSWORD`, die **ein Mensch** in den Repo-Settings anlegt; ohne sie wird der Schritt übersprungen
     (`if: env.SVN_USERNAME != ''`).
   - Assets aus `.wordpress-org/` gehen über dieselbe Action bzw. `10up/action-wordpress-plugin-asset-update`
     für reine Asset-Änderungen.
3. **readme.txt**: Beschreibung mit klarer Haltung („No ads. No upsells. No tracking. Ever.“), Feature-Liste,
   FAQ (Page Builder? → Shortcode ab 1.1 / Synced Patterns; Endlos-Loop? Warum nicht; Performance-Zahlen;
   Barrierefreiheit), Screenshots-Beschreibungen, Changelog, Upgrade Notice. Mit dem wp.org-Readme-Validator prüfen.
4. **Grafiken**: eigenständig gestaltet. Das Logo ist eine abstrahierte Kadenz, also rhythmische Balken bzw.
   Punkte, als SVG. Keine Stockfotos mit unklarer Lizenz.
5. **Erst-Einreichung bei wp.org** (manuell, Checkliste in `docs/release.md`): Plugin-ZIP hochladen, Review
   abwarten, SVN-Zugang einrichten, Secrets setzen.

## Akzeptanzkriterien

- [ ] Ein Test-Tag `v0.0.1-rc.1` in einem Fork bzw. mit Dry-Run erzeugt ein GitHub Release mit korrektem ZIP;
  der SVN-Schritt wird ohne Secrets sauber übersprungen.
- [ ] Plugin Check und der Readme-Validator laufen ohne Fehler.
- [ ] `docs/release.md` beschreibt den Release-Ablauf in höchstens 10 Schritten.

## Verifikation

```bash
npm run version:bump -- 0.1.0 && git diff
npm run build && npm run plugin-zip && unzip -l kadenz-slider.zip
```
