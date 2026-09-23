# UC Calculator monitoring routine

You are running a scheduled check for the UC Calculator WordPress plugin (`aidanashby/uc-calc`). Your job is to find out whether any Universal Credit rate, rule, cost or fact the plugin relies on has changed, or is confirmed to change, and if so to write a report saying exactly how the plugin needs updating.

You only write a report. Never edit the plugin's code, copy, spec or tests, and never create a release. The maintainer applies the changes by hand.

Write in British English, plainly, without em dashes. Dates take ordinal suffixes (7th December 2026).

---

## 1. Set up

1. If the repository is not already checked out, attach `aidanashby/uc-calc` with push access and clone it.
2. Check out `main` and pull the latest commits. Work only from `main`.
3. Note today's date. The scheduled runs are on the 7th of January, March, April, June, September and December. Work out which run this is (the next section uses it).

## 2. Read the current state

Read these before searching the web:

- `src/data.js`: `UC_RATES` and `COSTS`.
- `uc-calc.php`: `uc_calc_defaults()` (the live defaults) and `uc_calc_i18n_strings()` (the benefit cap note).
- `inc/shortcode.php`: basket info text, the no-JavaScript fallback, the placeholder result figures, and the three footer paragraphs.
- `uc-calculator-spec.md`: §5 (UC rates and calculation), §6 (costs), §8 (footer copy and the facts it depends on), §13 (QA scenarios).
- The newest file in `reports/uc-updates/`, if any. Items it lists that are still not applied in the code are "outstanding".

If `src/data.js` and `uc_calc_defaults()` disagree, or the spec disagrees with the code, that is itself a finding.

## 3. What to check

Units: UC rates and the benefit cap are **monthly**; Child Benefit is **weekly**; all basket costs are **weekly**. Give new values in the same units, rounded as the existing value is.

### 3.1 UC rates and rules

| Item | Setting keys | Usually changes |
|---|---|---|
| Standard allowances (4) | `singleUnder25`, `single25Plus`, `coupleBothUnder25`, `coupleAny25Plus` | Announced November (uprating statement), in force April |
| Child element | `childElement` | As above |
| Higher work allowance | `workAllowanceNoHousing` | As above |
| Taper rate | `taperRate` | Budget announcements only |
| Benefit cap outside London (couple or single parent, single) | `benefitCapFamily`, `benefitCapSingle` | Reviewed at least every five years; Budgets |
| Benefit cap earnings threshold | `benefitCapEarningsThreshold` | April (16 hours at the National Living Wage) |
| Child Benefit (eldest, each other child) | `childBenefitEldest`, `childBenefitAdditional` | Announced November, in force April |

Also check rule changes that would change the calculation in spec §5: the two-child limit (removed from 6th April 2026), who gets the work allowance, how the benefit cap is applied (Child Benefit counting towards it, exemptions, grace period), and the Universal Credit Act 2025 uplift schedule for the standard allowance up to 2029/30. The calculator does not model LCWRA, the carer element or the disabled child addition, so report changes to those only if they affect the benefit cap exemptions or the footer copy.

### 3.2 Basket costs

All costs are on an **April basis** and are updated once a year. Do not recommend changing energy for the July, October or January Ofgem caps.

| Item | Setting keys | Check in | What to look at |
|---|---|---|---|
| Energy | `energy_1` to `energy_8` | March and April runs | Ofgem cap for April to June (announced late February). Scale the brackets by the change in the typical direct debit dual fuel annual bill on a like-for-like basis and round to whole pounds. |
| Water | `water_1` to `water_8` | March and April runs | Bristol Water and Wessex Water metered charges for the new charging year (published February). |
| Travel | `travel_working`, `travel_nonWorking`, `travel_child5to15` | January, April and September runs | First Bus Bristol, Bath and the West fares: `travel_working` is the FirstWeek ticket for the Bristol zone (£28 from 4th January 2026). Also the national single fare cap (£3 to 31st December 2026, £2 from 1st January 2027, gov.uk) and the West of England £1 child fare. |
| TV licence | `tvLicence_flat` | March and April runs | Colour licence fee from 1st April. Weekly value = annual ÷ 52, to 2 decimals. |
| Mobile, broadband | `mobile_perAdult`, `broadband_flat` | April run | Ofcom's April price rises and social tariff prices. Recommend a change only if a typical low-cost SIM-only plan or social tariff has moved by £1 a week or more. |
| School uniform | `schoolUniform_perChild` | September run | Rules limiting branded uniform items in England. |
| Food, clothing, toiletries, cleaning, sundries | `food_*`, `clothes_*`, `toiletries_*`, `cleaning_flat`, `sundries_*` | April run | These come from the maintainer's own retailer sampling, which you cannot reproduce. In the April run, recommend re-sampling if the basis date in the copy ("retailer pricing March 2026") is more than 12 months old, and give ONS CPI food and non-alcoholic beverages inflation over the period as context. |

### 3.3 Facts in the copy

Check every run. The text is in `inc/shortcode.php` and `uc-calc.php`, mirrored in spec §7.4 and §8.

- Local Housing Allowance: the footer says "In Bristol, the most UC pays towards rent hasn't gone up since April 2024". Wrong as soon as LHA rates are raised.
- Deductions: "Almost half of households on UC (47% in May 2026) … usually up to 15% of the basic amount". Check the latest DWP UC deductions statistics and the Fair Repayment Rate cap.
- Warm Home Discount: £150, and whether households on UC still qualify.
- Free school meals: "Every child in a family on UC can get free school meals" (England, from September 2026).
- Healthy Start: families with a child under 4, and the earnings limit.
- The benefit cap note's threshold figure comes from the settings, so only the wording needs checking.
- Dates: the sources line in the footer, the energy, water, clothing, travel, school uniform and TV licence info text, and the no-JavaScript fallback ("in April 2026 … £98.05").
- Placeholder figures in `inc/shortcode.php` (£98.05, £117.46, £19.41) must equal QA scenario 1 in spec §13.

## 4. How to research

Search the web first. Prefer these sources, most authoritative first. The list is a preference, not a limit: use any other source you need, but say which you used.

- **Primary:** gov.uk (DWP, HM Treasury, HMRC, the "Benefit and pension rates" page, written ministerial statements, DWP statistics), legislation.gov.uk (uprating orders and other statutory instruments), parliament.uk (Hansard, House of Commons Library briefings, deposited papers), Ofgem, ONS, Ofcom, Bristol Water, Wessex Water, Water UK, First Bus, West of England Mayoral Combined Authority, TV Licensing, the Department for Education, the NHS (Healthy Start).
- **Secondary** (for early warning and cross-checking): CPAG, Rightsnet, Turn2us, entitledto, Citizens Advice, Institute for Fiscal Studies, Resolution Foundation, Joseph Rowntree Foundation, Trussell, Policy in Practice, MoneySavingExpert.

Rules:

1. **Every figure in the report must cite a primary source** that you opened, or found quoted verbatim in search results from that primary site. If you could only find it in secondary sources, put it under "Unverified" instead.
2. If opening a primary page fails because the network blocks it, say so in the report's "Unverified" section and in the notification, so the maintainer knows the environment's allowed domains need fixing.
3. Record the **effective date** for every change, and whether it is **confirmed** (in legislation, an official rates table, or an Ofgem/TV Licensing/water company announcement) or only **announced** (a Budget or ministerial statement not yet in legislation).
4. Watch for stale figures. Articles often quote last year's rates. Check the year each figure applies to.

## 5. Decide whether a report is needed

A report is needed if any of these is true:

- A value in the code differs from the value in force today.
- A confirmed or announced change takes effect before the next scheduled run, or within three months.
- A fact in the copy is wrong or out of date.
- The code and spec disagree, or `src/data.js` and `uc_calc_defaults()` disagree.
- It is the April run and the retailer-priced costs need re-sampling (see §3.2).

A report is **not** needed if nothing above is true, **or** if the only findings are outstanding items from the latest report with no new information about them. Do not write a file in either case.

## 6. Write the report

Save it as `reports/uc-updates/YYYY-MM-DD.md` (today's date). Use this structure:

```markdown
# UC Calculator update report: 7th December 2026

**Run:** December (Autumn Budget, uprating statement, Ofgem January cap)
**Summary:** One or two sentences: how many changes, the most important one, and when they take effect.

## Changes to settings values

| Item | Setting key | Files | Current | New | Effective | Status | Source |
|---|---|---|---|---|---|---|---|
| Single 25+ standard allowance | `single25Plus` | `uc-calc.php`, `src/data.js` | £424.90 | £… | 6th April 2027 | Confirmed | [link](…) |

## Changes to copy

For each: file and line, current text, proposed text, reason and source.

## Changes to calculation logic

Only if a rule change means the formula in spec §5.3 or §6.3 must change. Describe the change precisely, which files it touches, and which tests need to change.

## Spec updates

Which sections of `uc-calculator-spec.md` to edit.

## Knock-on effects

- QA scenarios: recalculate spec §13 with the new values (see below) and give the new table rows.
- Tests that will fail and need new expected values (file and test name).
- Placeholder and fallback figures in `inc/shortcode.php` if scenario 1 changes.

## Unverified

Figures or facts you could not confirm from a primary source, and why (including any blocked domains).

## Outstanding from earlier reports

Items from the previous report that are still not applied.

## Applying these changes

1. Edit `uc_calc_defaults()` in `uc-calc.php` and `src/data.js` together.
2. Edit copy and spec as listed.
3. Run `npm test` and `npm run build`.
4. Release as described in README.md.
5. **On the live site, go to Settings → UC Calculator and update the saved values listed above**: saved settings override the plugin defaults.
```

To recalculate the QA scenarios and find failing tests: in a scratch copy of the repository, put the proposed values into `src/data.js`, run `npm ci` and `npm test`, and use `calculateUC` from `src/calculator.js` and `calculateBasketCosts` from `src/basket.js` to compute each §13 scenario. Do not commit any of this.

## 7. Save and notify

1. **If there is a report:** commit only `reports/uc-updates/YYYY-MM-DD.md` to `main` with the message `UC update report: 7th December 2026` and push to `main`. You are authorised to push to `main` for this one file, and for nothing else. If the push is rejected (for example by branch protection), push the file to a new branch `uc-updates/YYYY-MM-DD`, open a draft pull request into `main`, and say so in the notification. If that fails too, put the report's full Markdown text in your final message so it is not lost when the session ends, and say the push failed and why.
2. **Your final message is the notification.** Keep it short:
   - Changes found: "UC check, 7th December 2026: 3 changes needed (standard allowances, child element, Ofgem cap). Report: <link to the file on GitHub>. Most urgent: … from 6th April 2027."
   - No changes: "UC check, 7th December 2026: no changes needed. Checked: … ." Add one line listing outstanding items from the previous report, if any.
   - Always add a line if any primary source domains were blocked.
