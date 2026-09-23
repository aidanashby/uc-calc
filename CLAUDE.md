# UC Calculator

WordPress plugin (`[uc_calculator]` shortcode) comparing Universal Credit income with weekly essentials in Bristol and South Gloucestershire. All calculation runs client-side. `uc-calculator-spec.md` is the source of truth for behaviour, figures, copy and decisions (§15). `README.md` covers install, updates, releasing and uninstall.

## Commands

- `npm ci`: install
- `npm test`: Jest tests (`tests/`)
- `npm run build`: bundle `src/` into `assets/uc-calc.js`. The bundle is committed; rebuild and commit it after any `src/` change.
- `php -l <file>`: lint PHP (no PHP test suite)

## Key rules

- Default rates and costs live in **two places that must match**: `uc_calc_defaults()` in `uc-calc.php` (live defaults) and `src/data.js` (JS fallback and tests). Adding a key also means updating `uc_calc_build_js_data()` and the rows in `inc/settings.php`.
- Saved admin settings (`uc_calc_settings` option) override the defaults on a live site. Only values that differ from the defaults are stored (from v1.1.0; v1.0.0 stored every field).
- UC rates and the benefit cap are monthly; Child Benefit and basket costs are weekly.
- All visible copy is in `inc/shortcode.php` and `uc_calc_i18n_strings()` in `uc-calc.php`, wrapped for translation. The spec mirrors it (§7, §8), so change both.
- When figures change, recalculate the QA scenarios in spec §13 from the code, and update the placeholder figures in `inc/shortcode.php` if scenario 1 changes.
- Costs stay on an April basis between annual reviews.
- British English, plain language (reading age 11 for visitor-facing copy), no em dashes in new text.

## Releases and updates

- Version is set in two places in `uc-calc.php`: the `Version:` header and `UC_CALC_VERSION`. Add a `## [x.y.z] - date` section to `CHANGELOG.md`; the release workflow uses it as the release notes. Only release when the maintainer asks.
- Pushing a `v*` tag runs `.github/workflows/release.yml`, which verifies and attaches `uc-calc.zip`.
- `inc/updater.php` reads GitHub releases via core's `update_plugins_github.com` hook. `.gitattributes` keeps dev files out of release zips.

## Monitoring

- A scheduled Claude Code routine ("UC Calculator monitoring") follows `.claude/routines/uc-monitor.md` and writes reports to `reports/uc-updates/`. Reports are recommendations; apply them by hand.
- Outstanding setup tasks for the maintainer are in `docs/next-steps.md` (delete when done).
