# Changelog

All notable changes to UC Calculator. Dates are release dates.

## [1.1.0] - 2026-09-23

### Changed
- UC figures updated for 2026/27: the work allowance for households with children rises from £673 to £710 a month.
- The benefit cap for outside London is now applied, per benefit unit, with Child Benefit counted towards it and the £881 a month earnings exemption. A note appears in the result panel when the cap lowers the UC shown.
- Footer: deductions now read "almost half of UC households (46% in February 2026), usually up to 15%". It says the benefit cap is applied, mentions free school meals, the Warm Home Discount and Healthy Start as other help, and lists the benefit cap and Child Benefit among the sources.
- Settings page: only values you change are saved. Fields left at the default now pick up new figures from each plugin update. Before this, clicking Save Settings froze every field at its current value.
- Basket info panels open on click on every device. The desktop hover popover is gone.

### Added
- Settings page: benefit cap, earnings threshold and Child Benefit rows.
- Settings page: fields that differ from the default say so, and a Reset to defaults button clears every saved value.
- Updates are now found by WordPress's background checks and WP-CLI, not only while you're in wp-admin, so automatic updates work. Check again on Dashboard → Updates bypasses the 12-hour cache.
- Each release has an installable `uc-calc.zip` attached, holding only the files the plugin needs.

### Fixed
- The calculator now starts when caching plugins such as LiteSpeed Cache delay JavaScript until the visitor interacts with the page.
- Accessibility: the adult and child counters no longer claim arrow-key support they didn't have, and screen readers announce the new count. The labels above the income and essentials figures and the info buttons now meet contrast requirements.
- Negative take-home pay is treated as £0 instead of reducing income.
- Uninstalling also removes the cached update data, on every site of a multisite network.

## [1.0.0] - 2026-04-28

- First release.
