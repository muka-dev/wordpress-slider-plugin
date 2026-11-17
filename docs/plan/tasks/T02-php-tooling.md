---
id: T02
title: "PHP-Tooling: Composer, PHPCS, PHPUnit"
phase: 0
status: open
owner:
depends_on: [T01]
---

# T02 – PHP-Tooling

## Ziel

Code-Standards und zwei PHPUnit-Suiten: **unit** (ohne WordPress, sehr schnell) und **integration**
(mit WordPress in wp-env).

## Umfang (Dateien)

- `composer.json`, `composer.lock`, `phpcs.xml.dist`, `phpunit.xml.dist`
- `tests/php/unit/bootstrap.php`, `tests/php/unit/SmokeTest.php`
- `tests/php/integration/bootstrap.php`, `tests/php/integration/BlockRegistrationTest.php`
- `package.json` (nur Skript `test:php`)

## Umsetzung

1. **composer.json** (nur `require-dev`):
   - `wp-coding-standards/wpcs` ^3, `phpcompatibility/phpcompatibility-wp`, `dealerdirect/phpcodesniffer-composer-installer`
   - `phpunit/phpunit` ^9.6 (läuft auf PHP 7.4 bis 8.x), `yoast/phpunit-polyfills` ^2 oder ^3,
     `wp-phpunit/wp-phpunit`, `brain/monkey` ^2 (für Unit-Tests mit gestubbten WP-Funktionen)
   - `config.platform.php`: `7.4`
   - Skripte: `lint` (phpcs), `lint:fix` (phpcbf), `test:unit` (`phpunit --testsuite unit`)
2. **phpcs.xml.dist**: Ruleset `WordPress`, `PHPCompatibilityWP` mit `testVersion 7.4-`, `minimum_wp_version 6.6`,
   Prefixes `kadenz_slider`, `Kadenz\Slider` und `KADENZ_SLIDER`, Text-Domain `kadenz-slider`.
   Geprüft werden `kadenz-slider.php`, `includes/`, `src/**/render.php` und `tests/php/`; ausgeschlossen sind
   `build/`, `vendor/` und `node_modules/`. Für Tests sind Dateinamensregeln gelockert (PSR-4-artige Testklassen).
3. **phpunit.xml.dist** mit zwei Testsuites:
   - `unit` → `tests/php/unit`, bootstrap `tests/php/unit/bootstrap.php` (Composer-Autoload, Brain Monkey,
     `require_once` der `includes/`-Dateien, Definition von `ABSPATH`)
   - `integration` → `tests/php/integration`, bootstrap über `wp-phpunit`, lädt das Plugin per
     `tests_add_filter( 'muplugins_loaded', … )`
   Beide Suiten verwenden eine gemeinsame Konfigurationsdatei, die Suite wird per `--testsuite` gewählt.
   Ein Unit-Bootstrap darf WordPress nicht laden.
4. **package.json**: `"test:php": "wp-env run tests-cli --env-cwd=wp-content/plugins/kadenz-slider vendor/bin/phpunit --testsuite integration"`.
   Prüfe den tatsächlichen Mount-Pfad des Plugins in wp-env und passe ihn bei Bedarf an.
5. **Beispieltests**:
   - Unit: `SmokeTest` prüft, dass die Klasse `Kadenz\Slider\Plugin` existiert.
   - Integration: `BlockRegistrationTest` prüft, dass `kadenz/slider` und `kadenz/slide` registriert sind und dass
     `kadenz/slide` als `parent` den Wert `kadenz/slider` hat.
6. Bestehende PHP-Dateien aus T01 PHPCS-konform machen.

## Akzeptanzkriterien

- [ ] `composer install && composer lint` ist grün.
- [ ] `composer test:unit` läuft ohne Docker und ohne WordPress grün.
- [ ] `npm run test:php` läuft in wp-env grün.
- [ ] PHPCompatibility meldet nichts für PHP 7.4.

## Verifikation

```bash
composer install && composer lint && composer test:unit
npm run env:start && npm run test:php
```
