# UC Calculator

A WordPress plugin for North Bristol & South Gloucestershire Foodbank's *Guarantee Our Essentials* campaign. It shows whether the Universal Credit standard rate covers a household's weekly essentials in Bristol and South Gloucestershire, excluding rent and council tax.

Everything runs in the visitor's browser. Nothing is sent to a server, stored or tracked.

The full design, including every rate, formula, piece of copy and QA scenario, is in [`uc-calculator-spec.md`](uc-calculator-spec.md).

## What it calculates

- **Income:** UC standard allowance plus the child element for each child, reduced by the 55% earnings taper (with the work allowance for households with children), then limited by the benefit cap for outside London. Earnings are added on top. Shown per week.
- **Essentials:** 12 weekly costs (food, energy, water, mobile, broadband, bus travel, toiletries, cleaning, clothing, school uniform, sundries, TV licence) that scale with household size. Visitors can untick any item.
- **Difference:** the weekly shortfall, or what's left over.

It does not model the LCWRA, carer or disabled child elements, housing or childcare costs, PIP, DLA or deductions. The footer says so.

## Requirements

- WordPress 5.8 or later
- PHP 7.4 or later

## Installing

1. Download `uc-calc.zip` from the [latest release](https://github.com/aidanashby/uc-calc/releases/latest). For releases before the zip was attached, use the "Source code (zip)" file instead.
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, choose the zip and activate it.
3. Add the shortcode `[uc_calculator]` to any page or post.

## Settings

**Settings → UC Calculator** lists every UC rate, benefit cap figure, Child Benefit rate and basket cost. Rates are monthly and costs are weekly, as labelled.

Only values you change are saved; they override the plugin's defaults. Every other field follows the defaults, so it picks up new figures from each plugin update. A changed field shows its default beneath it, and **Reset to defaults** clears every saved value.

## Updates

The plugin updates itself from this repository's GitHub releases, through the normal WordPress update screens. It checks for new releases when WordPress checks for updates (twice a day, and whenever you click **Check again** on **Dashboard → Updates**). You can turn on automatic updates for it on the Plugins screen.

## Releasing a new version

1. In `uc-calc.php`, set the new version in both the `Version:` header and `UC_CALC_VERSION`.
2. Run `npm test` and `npm run build`, then commit, including `assets/uc-calc.js`.
3. Push a tag for the version, for example:

   ```sh
   git tag v1.1.0
   git push origin v1.1.0
   ```

4. The **Release** GitHub Action checks the version matches the tag, runs the tests, confirms the committed JavaScript is up to date, then publishes the release with `uc-calc.zip` attached. If you drafted the release yourself first, it keeps your notes and just attaches the zip. The action fails, and publishes nothing, if any check fails.

Sites see the new version within 12 hours, or straight away after **Check again**.

## Uninstalling

Deactivating keeps your settings. Deleting the plugin from the Plugins screen removes its settings and cached update data, on every site of a multisite network.

## Keeping the figures current

A scheduled Claude Code routine checks for changes to UC rates, the benefit cap, Child Benefit, energy, water, bus fares, the TV licence and the facts in the footer. It runs at 08:00 UTC on the 7th of January, March, April, June, September and December.

When something needs updating it commits a report to [`reports/uc-updates/`](reports/uc-updates/) saying exactly which settings, copy, spec sections and tests to change. When nothing has changed it only sends a notification. Its instructions are in [`.claude/routines/uc-monitor.md`](.claude/routines/uc-monitor.md).

## Development

```sh
npm ci
npm test         # Jest tests for the calculation, basket and result logic
npm run build    # Bundles src/ into assets/uc-calc.js
npm run watch    # Rebuilds on change
```

| Path | Contents |
|---|---|
| `uc-calc.php` | Plugin bootstrap, default rates and costs, script data, shortcode, admin menu |
| `inc/shortcode.php` | Calculator markup and all visible copy |
| `inc/settings.php` | Settings page |
| `inc/updater.php` | GitHub release updater |
| `uninstall.php` | Clean-up on delete |
| `src/` | Calculator source (bundled into `assets/uc-calc.js`) |
| `src/data.js` | Default rates and costs for the JavaScript fallback and tests. Keep in step with `uc_calc_defaults()` |
| `tests/` | Jest tests |

Release zips contain only the files the plugin needs at runtime; `.gitattributes` lists what is left out.

## Licence

MIT. See [LICENSE](LICENSE).
