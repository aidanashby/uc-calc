# Universal Credit Calculator: Plugin Logic Spec

A WordPress plugin (single shortcode) that lets visitors check whether the Universal Credit (UC) standard rate could cover their weekly essentials. All calculation runs client-side. No data leaves the browser.

This document is the concept design. It defines inputs, calculation logic, cost data, behaviour, and constraints. It does not prescribe code.

---

## 1. Objectives

1. Show, for any household composition, the gap (or surplus) between the UC entitlement they would be on and what it costs to cover everyday essentials in Bristol and South Gloucestershire.
2. Let the user edit the basket by unchecking items that don't apply, with figures recalculated live.
3. Reinforce the campaign message: the standard rate of UC was never designed to cover the full cost of living, and even excluding rent and council tax, it routinely falls short.

The calculator follows the Trussell / JRF "Guarantee Our Essentials" framing. It excludes rent and council tax. Those are flagged in a note at the bottom.

---

## 2. Architecture and privacy constraints

| Constraint | Requirement |
|---|---|
| Server calls | None. No fetch / XHR / WebSocket. All state, calculation, and UI in the browser. |
| Persistence | None by default. State lives in memory only. Cleared on navigation or reload. No localStorage, sessionStorage, cookies, or query strings written. |
| Analytics | None on this plugin. WordPress site analytics elsewhere are unaffected. |
| Bundled data | UC rates, cost values, and scaling rules ship as defaults in the plugin (`uc_calc_defaults()` in `uc-calc.php`, mirrored in `src/data.js`). The site admin can override any value on the Settings → UC Calculator page. Only values that differ from the defaults are stored. |
| Browser support | Modern evergreen browsers, last 2 versions. iOS Safari 15+. No IE. |
| Page weight | Lightweight: vanilla JS or a small framework. No heavy libraries. Total payload under 50KB if possible. |
| Mobile | Responsive, mobile-first. Tested at 360px, 768px, 1024px, 1440px widths. |
| Accessibility | WCAG 2.2 AA. See §11. |

A short privacy notice appears at the top of the calculator: *"Everything you enter stays in your browser. No information is sent to us or anyone else, and nothing is saved when you leave this page."*

---

## 3. Page structure

One screen, three vertically stacked sections, all visible at once on desktop. On mobile, sections stack and the result panel becomes sticky at the bottom of the viewport.

```
┌─────────────────────────────────────────────────┐
│ Privacy notice                                  │
├─────────────────────────────────────────────────┤
│ A. Tell us about your household                 │
│    (form inputs)                                │
├─────────────────────────────────────────────────┤
│ B. Your weekly essentials                       │
│    (checkable basket items with live values)    │
├─────────────────────────────────────────────────┤
│ C. The result                                   │
│    Income | Essentials | Difference (live)      │
├─────────────────────────────────────────────────┤
│ Footnote: rent and council tax not included     │
│ Data sources and dates                          │
└─────────────────────────────────────────────────┘
```

Live update: every input change in section A or B triggers immediate recalculation of section C. No "calculate" button.

---

## 4. Section A: Household composition (inputs)

All fields are required. Defaults shown in parentheses.

| Field | Type | Options / Range | Default | Notes |
|---|---|---|---|---|
| `numAdults` | stepper | 1 to 6 | 1 | Label: "Number of adults". One adult row per adult, rendered dynamically. |
| `inCouple` | checkbox | true / false | false | Shown only if `numAdults` is 2 or more. Wording: "Two of us are in a couple or civil partnership." Cleared if `numAdults` drops below 2. |
| `adults[i].age` | radio per adult | "Under 25", "25 or over" | "25 or over" | Legend: "How old are you?" for the first adult, "How old are they?" for the others. |
| `adults[i].working` | checkbox per adult | true / false | false | Wording: "I am currently in work" (first adult), "Currently in work" (others). |
| `adults[i].monthlyEarnings` | number input per adult | £ per month, 2 decimals, 0 to 20,000 | empty | Net (take-home) pay. Shown only if that adult is working. Hint under the first adult's field: "Net (take-home) pay after tax and National Insurance." |
| `numChildren` | stepper | 0 to 8 | 0 | Label: "Dependent children under 16". |
| `childAges[]` | radio per child | "Under 5", "5 to 15" | "5 to 15" | One row per child. Legend: "How old is your first child?" and so on. |

The calculator covers Universal Credit only. It has no inputs for disability, caring, or other benefits. The footer says so (see §8).

---

## 5. UC entitlement calculation (2026/27 rates)

All UC figures are monthly and converted to weekly at the end.

### 5.1 Constants (defaults, admin-editable)

```js
const UC_RATES = {
  // Standard allowance, monthly £
  standardAllowance: {
    singleUnder25:     338.58,
    single25Plus:      424.90,
    coupleBothUnder25: 528.34,
    coupleAny25Plus:   666.97,
  },
  childElement:           303.94, // monthly £ per child
  workAllowanceNoHousing: 710,    // higher work allowance, monthly £
  taperRate:              0.55,   // 55p reduction per £1 above the work allowance
  // Benefit cap outside London, monthly £
  benefitCap: {
    family:            1835,     // couples, and single people with children
    single:            1229.42,  // single people without children
    earningsThreshold: 881,      // take-home pay at or above this exempts the household
  },
  // Child Benefit, weekly £. Not counted as income; counts towards the benefit cap.
  childBenefit: {
    eldest:     27.05,
    additional: 17.90,
  },
};
```

Sources: DWP Benefit and pension rates 2026 to 2027 (UC, benefit cap), HMRC Child Benefit rates 2026 to 2027. The benefit cap is frozen at its 2025/26 level for 2026/27; the earnings threshold rose from £846 to £881 on 1 April 2026.

The two-child limit no longer applies for assessment periods starting on or after 6 April 2026, so every child gets the child element.

The higher first-child rate for children born before 6 April 2017 (£351.88) is not applied. This is a deliberate simplification.

The same values live in two places, which must be kept in step: `uc_calc_defaults()` in `uc-calc.php` (the live defaults) and `src/data.js` (the JS fallback and the tests).

### 5.2 Benefit units

- If `inCouple` is true: adults 1 and 2 form one couple benefit unit, and all children belong to it. Adults 3 and above are each a separate single claimant with no children.
- Otherwise: every adult is a separate single claimant. All children belong to adult 1, who is treated as a single parent.

### 5.3 Calculation per benefit unit

```
function calculateUnit(unit):
  // Standard allowance
  if unit is a couple:
    SA = coupleAny25Plus if either adult is "25plus" else coupleBothUnder25
  else:
    SA = single25Plus if adult is "25plus" else singleUnder25

  maxUC    = SA + unit.numChildren × childElement
  earnings = sum of monthlyEarnings for working adults in the unit

  // Earnings taper. Work allowance applies only if the unit has children.
  workAllowance = workAllowanceNoHousing if unit.numChildren > 0 else 0
  netUC = max(0, maxUC - max(0, earnings - workAllowance) × taperRate)

  // Benefit cap
  isFamily = unit is a couple OR unit.numChildren > 0
  if earnings < benefitCap.earningsThreshold:
    childBenefit = 0 if unit.numChildren == 0
                   else (eldest + (numChildren - 1) × additional) × 52 / 12
    limit = (benefitCap.family if isFamily else benefitCap.single) - childBenefit
    if netUC > limit:
      netUC  = max(0, limit)
      capped = true

  return netUC + earnings

weeklyIncome = (sum of calculateUnit over all units) × 12 / 52
```

With 2026/27 rates the cap first applies, with no earnings, to a couple with 3 children and to a single parent with 4 children.

### 5.4 Limitations

- **Benefit cap**: modelled for outside London only. The calculator ignores the housing element, so a real household paying rent reaches the cap sooner and loses more. The nine-month grace period after work ends and the exemptions for disability and carer benefits are not modelled; the footer and the cap note explain them.
- **Childcare element**: not modelled.
- **Housing element**: not modelled. Excluded by design.
- **Deductions** (advance repayments, debts to DWP, third-party debts): not modelled. Usually capped at 15% of the standard allowance since April 2025. Mentioned in the footer.
- **Other help that lowers costs** (free school meals for every child in a UC household in England from September 2026, the £150 Warm Home Discount, Healthy Start): mentioned in the footer, not deducted from the basket.

---

## 6. Section B: Essentials basket

12 line items. All ticked by default. Unticking strikes through and greys the row, removes it from the total, and triggers a result recalculation.

### 6.1 Display

Each row shows: checkbox + plain English label + calculated weekly £ value + info button that opens a short explanation of how it was calculated. A row is hidden when its value is £0 (for example, school uniform when there are no children aged 5 to 15).

### 6.2 Cost values (April 2026 prices, Bristol and South Glos averaged)

All values £ per week. Defaults live in the same two places as the UC rates and are admin-editable.

```js
const COSTS = {
  food: {
    firstAdult:      35,
    additionalAdult: 15,
    childUnder5:     15,
    child5to15:      20,
  },
  energy: {
    // By household size. Ofgem April 2026 cap plus a small prepayment-meter premium.
    bracket: { 1: 25, 2: 30, 3: 38, 4: 38, 5: 45, 6: 45, 7: 45, 8: 45 },
  },
  water: {
    // Bristol Water + Wessex Water sewerage on a meter, by household size
    bracket: { 1: 6, 2: 9, 3: 11, 4: 13, 5: 15, 6: 15, 7: 15, 8: 15 },
  },
  mobile:        { perAdult: 4 },
  broadband:     { flat: 5 },
  travel: {
    workingAdult:    22,  // weekly bus pass equivalent
    nonWorkingAdult: 10,  // ad-hoc essential trips
    child5to15:      4,   // under 5s travel free
  },
  toiletries:    { perPerson: 5, householdBase: 2 },
  cleaning:      { flat: 4 },
  clothes:       { perAdult: 5, childUnder5: 6, child5to15: 4 },
  schoolUniform: { perChild5to15: 6 },
  tvLicence:     { flat: 3.46 },  // £180 a year from 1 April 2026
  sundries:      { single: 13, household: 20 },
};
```

All costs use an April basis and are reviewed once a year. The quarterly Ofgem cap changes in July, October and January are not applied between reviews.

### 6.3 Per-item formulas

`householdSize = min(numAdults + numChildren, 8)`.

| Item | Formula | Hidden when |
|---|---|---|
| Food | `firstAdult + additionalAdult × (numAdults - 1) + childUnder5 × numUnder5 + child5to15 × num5to15` | never |
| Energy (gas and electric) | `energy.bracket[householdSize]` | never |
| Water | `water.bracket[householdSize]` | never |
| Mobile phones | `perAdult × numAdults` | never |
| Home broadband | `flat` | never |
| Travel (bus) | for each adult, `workingAdult` if working else `nonWorkingAdult`; plus `child5to15 × num5to15` | never |
| Toiletries and period products | `perPerson × (numAdults + numChildren) + householdBase` | never |
| Cleaning products | `flat` | never |
| Everyday clothing | `perAdult × numAdults + childUnder5 × numUnder5 + child5to15 × num5to15` | never |
| School uniform and shoes | `perChild5to15 × num5to15` | `num5to15 == 0` |
| Sundries | `single` if `householdSize == 1` else `household` | never |
| TV licence | `flat` | never |

### 6.4 Dated copy in the basket

The info text for energy (April 2026 price cap), water (2026/27), clothing (March 2026 pricing) and TV licence (£180 from 1 April 2026) names its source date. Update it whenever the matching figure changes.

---

## 7. Section C: The result panel (live)

Three figures and a short message, updated on every input change. No "calculate" button.

### 7.1 Layout

```
┌──────────────────────┬──────────────────────┐
│ INCOME PER WEEK      │ ESSENTIALS PER WEEK  │
│ £xxx.xx              │ £xxx.xx              │
└──────────────────────┴──────────────────────┘
┌─────────────────────────────────────────────┐
│ DIFFERENCE                                  │
│ £xxx.xx                                     │
│ [contextual line - see 7.2]                 │
│ [benefit cap note, if capped - see 7.4]     │
└─────────────────────────────────────────────┘
```

The difference panel is the largest visual element.

### 7.2 Result states

```
difference = income - essentials
```

If `difference < 0` (essentials exceed income):
- Display `£X.XX` in brand orange `#ff5414`
- Label: "Shortfall"
- Subtext: "This is what you'd need to find from somewhere else each week, on top of what UC provides."

If `difference == 0`:
- Display `£0.00` in default text colour
- Label: "Exactly enough"
- Subtext: "Nothing left for anything unexpected. A washing machine breakdown, a school trip, a winter coat, a funeral, a delayed payment."

If `difference > 0`:
- Display `£X.XX` in default text colour. Do not use green: it implies "all is well" and undercuts the campaign point.
- Label: "Money left to save or for emergencies"
- Subtext: "This is what's left after a week of essentials. It has to cover anything unexpected, including replacement clothes and household items, dental costs, a school trip, or a winter coat."

Headline figures (income, essentials, difference) sit in a result panel with a slim dark green `#0a3d2e` strip across the top carrying the section heading in white uppercase. The income and essentials labels above their figures use mid green `#00ab52` for emphasis. Yellow `#ffe42d` is reserved for a single optional highlight bar behind the active state of the difference label, used sparingly so it retains impact.

### 7.3 Number formatting

- All currency values: `£X.XX` to two decimals.
- Round half up.
- Use thin space or comma for thousands (none expected at weekly scale).
- Negative values shown without a minus, instead labelled "Shortfall" so the figure reads "Shortfall: £21.41" rather than "-£21.41". Avoids the accounting convention which can be unclear at a glance.

### 7.4 Benefit cap note

When the benefit cap reduces any benefit unit's UC, a note appears beneath the subtext, separated by a thin rule:

> The benefit cap has lowered the UC shown here. The cap doesn't apply if your household takes home at least £881.00 a month from work, or if someone gets certain disability or carer benefits.

The threshold figure is filled from `benefitCap.earningsThreshold`. The note is hidden otherwise.

---

## 8. Footer

Below the calculator, three paragraphs.

> This calculator shows what the basic rate of Universal Credit has to stretch across, after rent and council tax. UC's housing element helps with rent up to a capped amount called Local Housing Allowance, which in Bristol hasn't risen since April 2024 even as rents have. Council tax is handled separately through Council Tax Reduction. Many people end up topping up rent or council tax from the same standard rate this calculator looks at. Almost half of UC households (46% in February 2026) have money taken off their payment to repay advance loans or other debts, usually up to 15% of the standard rate.
>
> It covers the standard allowance and child element only, and applies the benefit cap for outside London. It does not include the LCWRA addition, carer element, disabled child addition, Personal Independence Payment, DLA, Child Benefit, or the housing and childcare elements of UC. Child Benefit is not counted as income here, but it is counted towards the benefit cap. If anyone in your household gets a disability or carer benefit, the cap usually does not apply. If any of these apply, your actual UC and support costs may differ. Some households get other help that lowers their costs: free school meals for every child in a household on UC, the £150 Warm Home Discount on energy bills, and Healthy Start payments for some families with a child under 4. For a personal benefits check, contact Citizens Advice or your local advice service. North Bristol & South Gloucestershire Foodbank is not a qualified benefits adviser. This tool is for awareness and campaigning only.
>
> Costs reflect April 2026 prices for Bristol and South Gloucestershire, at a realistic low-budget level. Data: Ofgem (April 2026 price cap), DWP UC rates and benefit cap April 2026, HMRC Child Benefit rates April 2026, First Bus fares January 2026, Bristol Water and Wessex Water 2026/27, retailer pricing March 2026, TV Licensing April 2026.

Facts in this copy that change over time, and must be checked at each review: the LHA freeze (still frozen for 2026/27), the deductions share and cap (DWP deductions statistics), the Warm Home Discount amount, free school meals eligibility, and Healthy Start eligibility.

---

## 9. Behavioural rules

### 9.1 Live update

- Every input change triggers a recalculation within the same tick.
- Earnings inputs are debounced at 200ms.

### 9.2 Validation

- `adults[i].monthlyEarnings`: number to 2 decimal places, 0 to 20,000, `step="0.01"`. Empty, non-numeric or negative is treated as 0.
- `numAdults` (1 to 6) and `numChildren` (0 to 8): stepper buttons enforce the bounds and disable at each limit.
- No form-level submit. Validation happens inline.

### 9.3 Conditional visibility

- `inCouple`: shown only if `numAdults` is 2 or more.
- `adults[i].monthlyEarnings`: shown only if that adult is working. The value is kept in state if the adult stops working, but not used.
- `childAges[]`: one row per child.
- Any basket row whose value is £0 is hidden (in practice, school uniform when there are no children aged 5 to 15).
- Benefit cap note: shown only when the cap applies (§7.4).

### 9.4 Keyboard and tab order

Logical top-to-bottom, left-to-right. Section A first, then B, then C is read-only. Skip the result panel from tab focus.

### 9.5 Reset

A slim "Start again" button sits at the bottom of the calculator. Pill-shaped (fully rounded ends), dark green `#0a3d2e` background, white text, smaller uppercase, generous horizontal padding, minimal vertical padding so it reads as a slim control rather than a primary CTA. Left-aligned on desktop, full-width-but-narrow on mobile. On click, every input resets to its default and every basket item is re-ticked.

---

## 10. Edge cases and null cases

| Scenario | Behaviour |
|---|---|
| 1 adult 25+, no children, all defaults | Income £98.05/week, essentials £117.46/week, shortfall £19.41/week. |
| User unticks every basket item | Essentials = £0. Difference equals income, with the "You haven't selected any essentials" subtext. |
| Very high earnings (e.g. £4,000/month) | Taper reduces UC to zero. Income = earnings × 12 ÷ 52. |
| `numChildren = 8`, all under 5 | Eight child rows. Calculation runs normally. The benefit cap applies unless earnings reach the threshold. |
| `numAdults` drops from 2 to 1 | Last adult row removed; `inCouple` cleared. |
| Adult stops working after entering earnings | Earnings field hides. Value kept in state but not used. Reappears if re-ticked. |
| All children removed | School uniform row hides. |
| Income exactly equals essentials | £0.00 with the "Exactly enough" copy in §7.2. |
| Benefit cap applies | Income reflects the capped UC; the cap note appears (§7.4). |

---

## 11. Accessibility

| Requirement | Implementation |
|---|---|
| Semantic HTML | Use `<fieldset>` and `<legend>` for grouped inputs. Proper `<label>` for each input. Result panel uses `<output>` or ARIA live region. |
| ARIA live region | The difference figure is announced when it changes. `aria-live="polite"`. |
| Colour | Don't rely on red alone for shortfall. The word "Shortfall" must accompany the colour. |
| Contrast | All text 4.5:1 minimum, large text 3:1. Verify against NBSGF brand colours. |
| Keyboard | Every input reachable and operable with keyboard alone. Visible focus states. The adult and child counters are plain + and − buttons with a polite live region for the count (no `spinbutton` role, since there is no arrow-key support). Basket info panels are click-to-open disclosures (`aria-expanded`), not hover tooltips. |
| Screen reader labels | Each basket row's checkbox label includes the item name and its value, e.g. "Food, £140 per week". |
| Reduced motion | Respect `prefers-reduced-motion`. No animated transitions on the result figures. |
| Reading age | All visible copy at reading age 11. |
| Heading hierarchy | `<h2>` for each section. No skipped levels. |

---

## 12. WordPress packaging

- Plugin shortcode: `[uc_calculator]`. Placeable on any page or post.
- Admin page at Settings → UC Calculator. Only values that differ from the defaults are stored, in the `uc_calc_settings` option, and merged over `uc_calc_defaults()`, so fields left unchanged pick up new defaults from each plugin update. A changed field shows its default beneath it. "Reset to defaults" deletes the option.
- Default rates and costs live in `uc_calc_defaults()` (`uc-calc.php`) and `src/data.js`. After editing `src/` run `npm run build` to regenerate `assets/uc-calc.js`.
- Updates come from GitHub releases through `inc/updater.php`, using the `update_plugins_github.com` hook that WordPress core fires for plugins with an `Update URI` header. Update checks work in wp-admin, WP-Cron (so automatic updates can be switched on) and WP-CLI. The latest release is cached for 12 hours; "Check again" on Dashboard → Updates bypasses the cache.
- Release archives contain only runtime files (`uc-calc.php`, `uninstall.php`, `inc/`, `assets/`, `LICENSE`). `.gitattributes` excludes the rest.
- Uninstalling (deleting from the Plugins screen) removes the `uc_calc_settings` option and the cached release, on every site of a multisite network. Deactivating clears the cached release only.

### 12.0 Releasing an update

1. Set the new version in both the `Version:` header and `UC_CALC_VERSION` in `uc-calc.php`, and add a section for it to `CHANGELOG.md`.
2. Run `npm test` and `npm run build`, then commit, including `assets/uc-calc.js`.
3. Push a tag such as `v1.1.0`. The Release workflow checks the version matches the tag, runs the tests, confirms the committed bundle is current, and publishes the release with a `uc-calc.zip` attached. If you drafted the release yourself first, it keeps your notes and just attaches the zip.
4. Sites see the update within 12 hours, or straight away after "Check again".
- Single CSS file scoped to the plugin's container class to avoid theme conflicts.
- Single JS file, vanilla, bundled with esbuild.
- Translation-ready: all visible strings wrapped in `__()` or equivalent.
- No GDPR notice required because no data is collected.

### 12.1 Brand palette

| Token | Hex | Use |
|---|---|---|
| Mid green | `#00ab52` | Emphasis: the active checkbox tick, the input focus ring, small accents intended to draw the eye |
| Dark green | `#0a3d2e` | Sparingly: the slim header strip on the result panel, the labels above the income and essentials figures and the basket info buttons (mid green fails contrast there), the Start again pill, optional thin rule under section headings. Never as the background of a whole section. White text always sits on it. |
| Cream | `#f3f3eb` | Calm neutral background for the basket section if visual separation is needed. Otherwise leave backgrounds unset (white). |
| Highlight yellow | `#ffe42d` | Reserved for one accent: a thin bar or subtle background behind the active difference label. Used at most once per render so it doesn't lose impact. |
| Orange | `#ff5414` | Shortfall figure and any error or validation message. Not used for anything else. |
| Black | inherited | All body text. Theme default. Do not override. |

### 12.2 Lines and rules

Any rules, dividers, or input borders are kept subtle: 1px, low-contrast greys (e.g. `#e5e5e0` against cream, `#ececec` against white). No heavy boxes, no full-width section dividers in dark green.

### 12.3 Type

Use existing NBSGF brand fonts where the theme already loads them (Switzer for body, Verbeine for handwritten/list emphasis, Together Sans for numbers and mathematical symbols). The headline figures in the result panel should use Together Sans.

---

## 13. Test scenarios for QA

All with every basket item ticked. Income and essentials per week.

| # | Household | Income | Essentials | Difference | Notes |
|---|---|---|---|---|---|
| 1 | Single 25+, no work | £98.05 | £117.46 | Shortfall £19.41 | Headline campaign figure. |
| 2 | Single under 25, no work | £78.13 | £117.46 | Shortfall £39.33 | |
| 3 | Couple 25+, no children, no work | £153.92 | £171.46 | Shortfall £17.54 | |
| 4 | Single parent 25+, 1 child 5 to 15, no work | £168.19 | £171.46 | Shortfall £3.27 | |
| 5 | Couple 25+, 2 children (1 under 5, 1 aged 5 to 15), no work | £294.20 | £248.46 | £45.74 left | |
| 6 | Two adults 25+, not a couple, no work | £196.11 | £171.46 | £24.65 left | Each adult is a separate single claimant. |
| 7 | Single 25+, working, £1,200/month | £276.92 | £129.46 | £147.46 left | No work allowance without children. £1,200 × 0.55 = £660 exceeds £424.90, so UC is £0. |
| 8 | Single parent 25+, 1 child 5 to 15, working, £1,200/month | £382.92 | £183.46 | £199.46 left | Work allowance £710. Excess £490 × 0.55 = £269.50. UC = £728.84 − £269.50 = £459.34. |
| 9 | Couple 25+, both working, £750/month each, no children | £346.15 | £195.46 | £150.69 left | No work allowance. £1,500 × 0.55 = £825 exceeds £666.97, so UC is £0. |
| 10 | Couple 25+, 3 children 5 to 15, no work | £360.61 | £309.46 | £51.15 left | Capped. Max UC £1,578.79; limit £1,835 − Child Benefit £272.35 = £1,562.65. |
| 11 | Single parent 25+, 4 children 5 to 15, no work | £342.71 | £309.46 | £33.25 left | Capped. Max UC £1,640.66; limit £1,835 − Child Benefit £349.92 = £1,485.08. |
| 12 | As 10, but one adult working, £881/month | £545.94 | £321.46 | £224.48 left | Earnings at the threshold, so the cap does not apply. |
| 13 | All basket items unticked | Income unchanged | £0.00 | Equals income | Empty-basket message. |

The automated tests in `tests/` cover these calculations.

---

## 14. Out of scope

- Saving or sharing a result.
- LCWRA element, carer element, disabled child addition.
- Childcare element of UC.
- Housing element of UC and LHA shortfall.
- Council Tax Reduction.
- PIP, DLA, Carer's Allowance, and Child Benefit as income.
- London benefit cap rates, the benefit cap grace period, and exemptions for disability and carer benefits.
- Pre-April-2017 first-child rate.
- Deductions from UC.
- Deducting free school meals, the Warm Home Discount or Healthy Start from the basket.
- Comparison to MIS or destitution thresholds (beyond the footer).
- Animation / charts.

---

## 15. Decisions

- Brand palette: see §12.1.
- Footer copy: see §8.
- Start again control: slim dark green pill, smaller uppercase white text. See §9.5.
- Caps: 6 adults, 8 children.
- Benefit cap: modelled for outside London, with Child Benefit counted towards it.
- Other help that lowers costs is mentioned in the footer, not deducted, so the basket stays the gross cost of essentials.
- Costs stay on an April basis between annual reviews.
- JavaScript-disabled fallback: a static paragraph stating the headline figure (£98.05/week for a single adult, April 2026) and a link to Trussell's Guarantee Our Essentials page.
