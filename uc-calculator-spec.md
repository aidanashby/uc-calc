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
| Bundled data | UC rates, cost values, and scaling rules live in a single JavaScript object inside the plugin. Editable by the site admin via a constants file. No external CMS field for v1. |
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

All fields are required unless flagged optional. Defaults shown in parentheses.

| Field | Type | Options / Range | Default | Notes |
|---|---|---|---|---|
| `numAdults` | radio / segmented | 1, 2 | 1 | Drives whether second adult fields show. |
| `adult1Age` | radio | "Under 25", "25 or over" | "25 or over" | |
| `adult2Age` | radio | "Under 25", "25 or over" | "25 or over" | Hidden if `numAdults` is 1. |
| `numChildren` | stepper | 0 to 8 | 0 | Hard cap at 8 to keep UI sane. |
| `childAges[]` | radio per child | "Under 5", "5 to 15" | "5 to 15" | One row per child. Render dynamically. |
| `adult1Working` | checkbox | true / false | false | "Adult 1 is in paid work." |
| `adult2Working` | checkbox | true / false | false | Hidden if `numAdults` is 1. "Adult 2 is in paid work." |
| `monthlyEarnings` | number input | £ per month, 2 decimals, 0 to 5000 | empty | Combined household net (take-home) pay. Hidden unless at least one adult is working. `step="0.01"`. |
| `lcwra` | checkbox | true / false | false | "Limited capability for work and work-related activity due to a long-term health condition or disability." Plain language tooltip explains. |
| `lcwraPre2026` | checkbox | true / false | false | Hidden unless `lcwra` is true. Wording: "This was confirmed before April 2026." |
| `carer` | checkbox | true / false | false | Wording: "I care for a disabled adult or child for 35 hours or more a week (and I'm not paid for it)." |
| `disabledChild` | checkbox | true / false | false | Hidden if `numChildren` is 0. |
| `disabledChildHigher` | checkbox | true / false | false | Hidden unless `disabledChild` is true. Wording: "Receives the highest rate of DLA care or enhanced PIP daily living." |

PIP itself is not in scope. The calculator is explicit on this in a footnote: *"This calculator covers Universal Credit only. It doesn't include Personal Independence Payment, which is a separate benefit and isn't reduced by UC."*

---

## 5. UC entitlement calculation (April 2026 rates)

All UC components are in monthly figures, then converted to weekly at the end.

### 5.1 Constants (single source of truth, editable)

```js
const UC_RATES_2026 = {
  // Standard allowance, monthly £
  standardAllowance: {
    singleUnder25: 338.58,
    single25Plus: 424.90,
    coupleBothUnder25: 528.34,
    coupleAny25Plus: 666.97
  },
  // Child element, monthly £ per child
  childElement: 303.94,           // simplified: post-April-2017 rate for all children
  // Disabled child addition, monthly £
  disabledChildLower: 164.79,
  disabledChildHigher: 514.71,
  // LCWRA element, monthly £
  lcwraNew: 217.26,               // claimants from April 2026 onwards
  lcwraPre2026: 429.80,           // pre-April-2026 claimants, severe conditions, or terminally ill
  // Carer element, monthly £
  carerElement: 209.34,
  // Earnings taper
  workAllowanceNoHousing: 710,    // higher rate, no housing element in calc
                                  // ELIGIBILITY: only applies if the claimant has at least
                                  // one dependent child OR has LCWRA. Childless, non-disabled
                                  // working claimants have no work allowance and the taper
                                  // applies from the first £1 earned.
  taperRate: 0.55                 // 55p reduction per £1 above work allowance
};
```

All figures sourced from the gov.uk Benefit and pension rates 2026/2027 document.

The two-child limit was removed in April 2026, so all children get the child element.

The pre-2017 first-child rate (£351.88) is not applied. Documented as a deliberate simplification: *"Children born before 6 April 2017 receive a slightly higher rate. We've used the standard rate for simplicity."*

### 5.2 Calculation pseudocode

```
function calculateUC(input):
  // Standard allowance
  if input.numAdults == 1:
    if input.adult1Age == "25_plus": SA = singleUnder25Plus
    else: SA = singleUnder25
  else:
    if input.adult1Age == "25_plus" OR input.adult2Age == "25_plus":
      SA = coupleAny25Plus
    else:
      SA = coupleBothUnder25

  // Child element (no two-child limit)
  CE = input.numChildren × childElement

  // Disabled child addition
  if input.disabledChild:
    DCA = disabledChildHigher if input.disabledChildHigher else disabledChildLower
  else:
    DCA = 0

  // LCWRA
  if input.lcwra:
    LCWRA = lcwraPre2026 if input.lcwraPre2026 else lcwraNew
  else:
    LCWRA = 0

  // Carer
  CARER = carerElement if input.carer else 0

  // Maximum UC entitlement (monthly)
  maxUC = SA + CE + DCA + LCWRA + CARER

  // Earnings taper
  // Work allowance only applies if there's a dependent child or LCWRA in the household.
  // Otherwise the taper hits from £1 earned.
  if (input.adult1Working OR (input.numAdults == 2 AND input.adult2Working)) AND input.monthlyEarnings > 0:
    if input.numChildren > 0 OR input.lcwra:
      workAllowance = workAllowanceNoHousing
    else:
      workAllowance = 0
    excess = max(0, input.monthlyEarnings - workAllowance)
    reduction = excess × taperRate
    netUC = max(0, maxUC - reduction)
    monthlyIncome = netUC + input.monthlyEarnings
  else:
    netUC = maxUC
    monthlyIncome = maxUC

  // Convert to weekly
  weeklyIncome = monthlyIncome × 12 / 52

  return weeklyIncome rounded to 2 decimals
```

### 5.3 Edge cases and notes

- **Benefit cap**: not modelled in v1. Most scenarios for our audience won't hit it. Documented as a limitation.
- **Childcare element**: not modelled. Out of scope.
- **Housing element**: not modelled. Excluded by design.
- **Benefit deductions** (advance repayments, court fines, child maintenance arrears, rent arrears): can take up to 25% off the standard allowance for many UC claimants. Mentioned in the footnote, not modelled. *"For around 1 in 4 UC claimants, deductions for advance loans or other debts reduce the standard rate by up to 25% before they receive it."*
- If `numAdults` changes from 2 to 1, drop `adult2Age` from the calculation. If `numChildren` decreases, truncate `childAges` array.

---

## 6. Section B: Essentials basket

11 line items. All ticked by default. Unticking strikes through and greys the row, removes it from the total, and triggers a result recalculation.

### 6.1 Display

Each row shows: checkbox + plain English label + calculated weekly £ value + small info icon (tooltip explaining how it was calculated). On mobile, the row collapses to two lines.

```
[✓] Food                                     £140
    Based on Aldi-priced budget for 2 adults
    and 2 children
```

### 6.2 Cost values and scaling rules (April 2026 prices, Bristol and South Glos averaged)

All values £ per week. These live in a single object, so they can be tuned without touching logic.

```js
const COSTS_2026 = {
  food: {
    firstAdult: 50,
    additionalAdult: 35,
    childUnder5: 22,
    child5to15: 30
  },
  energy: {
    // Bracket on total household size (adults + children)
    bracket: { 1: 25, 2: 30, 3: 38, 4: 38, 5: 45, 6: 45, 7: 45, 8: 45 }
    // Reflects single in flat at low end through to family in 3-bed at top end.
    // Includes a small premium over Ofgem direct-debit benchmark to reflect
    // the prepayment-meter cost penalty common among UC claimants.
  },
  water: {
    // Bristol Water + Wessex sewerage on a meter, per total household size
    bracket: { 1: 6, 2: 9, 3: 11, 4: 13, 5: 15, 6: 15, 7: 15, 8: 15 }
  },
  mobile: {
    perAdult: 4   // basic SIM-only, mid-market (£17/month average)
  },
  broadband: {
    flat: 5       // £20/month average; social tariffs available cheaper
  },
  travel: {
    workingAdult: 22,       // weekly bus pass equivalent, allowing for some skipped days
    nonWorkingAdult: 10,    // ad-hoc essential trips
    child5to15: 4,
    childUnder5: 0          // free under 5
  },
  toiletries: {
    perPerson: 5,           // includes period products
    householdBase: 2        // shared items (toilet roll, etc)
  },
  cleaning: {
    flat: 4
  },
  clothes: {
    perAdult: 5,
    childUnder5: 6,         // grow fastest, replaced most often
    child5to15: 4           // school uniform tracked separately
  },
  schoolUniform: {
    perChild5to15: 6        // £312/year averaged primary and secondary, statutory branded-item cap in force
  },
  tvLicence: {
    flat: 3.46              // £180/year colour licence, from 1 April 2026
  }
};
```

### 6.3 Per-item formulas

| Item | Default | Formula | Hide row if |
|---|---|---|---|
| Food | on | `firstAdult + additionalAdult × (numAdults - 1) + childUnder5 × numUnder5 + child5to15 × num5to15` | never |
| Energy | on | Look up `bracket[householdSize]` where `householdSize = min(numAdults + numChildren, 8)` | never |
| Water | on | Look up `bracket[householdSize]` | never |
| Mobile | on | `perAdult × numAdults` | never |
| Broadband | on | `flat` | never |
| Travel (bus) | on | `(workingAdult if adult1Working else nonWorkingAdult) + (workingAdult if adult2Working else nonWorkingAdult, if numAdults == 2) + child5to15 × num5to15` | never |
| Toiletries & period products | on | `perPerson × (numAdults + numChildren) + householdBase` | never |
| Cleaning products | on | `flat` | never |
| Clothes (everyday) | on | `perAdult × numAdults + childUnder5 × numUnder5 + child5to15 × num5to15` | never |
| School uniform & shoes | on | `perChild5to15 × num5to15` | `num5to15 == 0` |
| TV licence | on | `flat` | never |

The travel formula now reads each adult's working flag independently. A two-earner household correctly applies the working travel rate twice; a single-earner couple applies it once. Children under 5 travel free in Bristol so contribute zero.

### 6.4 Cost data caveat displayed beneath the basket

> Costs reflect April 2026 prices for Bristol and South Gloucestershire, drawn from Ofgem (energy), Bristol Water and Wessex Water (water), First Bus (travel), retailer pricing for food, clothing and household goods, and government rates for the TV licence. Figures show a realistic low budget, not the bare minimum.

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

---

## 8. Bottom note

Below the result panel, a short paragraph (admin-editable via a single shortcode attribute or constants file):

> This calculator shows what the basic rate of Universal Credit has to stretch across, after rent and council tax. UC's housing element helps with rent up to a capped amount called Local Housing Allowance, which in Bristol hasn't risen since April 2024 even as rents have. Council tax is handled separately through Council Tax Reduction. Many people end up topping up rent or council tax from the same standard rate this calculator looks at. For around 1 in 4 UC claimants, deductions for advance loans or other debts reduce the standard rate by up to 25% before it reaches them.
>
> North Bristol & South Gloucestershire Foodbank is not a qualified benefits or financial advisor. This tool is for awareness and campaigning. It should not be used to plan a household budget or as a substitute for advice. For a personal benefits check, contact Citizens Advice or your local advice service.

Followed by a sources line: *"Data: Ofgem (April 2026 price cap), DWP UC rates April 2026, First Bus fares January 2026, Bristol Water and Wessex Water 2026/27, retailer pricing March 2026, TV Licensing April 2026."*

---

## 9. Behavioural rules

### 9.1 Live update

- Every input change in Section A or B triggers a recalculation function within the same tick.
- No debouncing on text inputs (only one numeric input, `monthlyEarnings`). On mobile keyboards, the recalc happens on each digit. If this causes flicker, debounce earnings input at 200ms.

### 9.2 Validation

- `monthlyEarnings`: positive number to 2 decimal places, 0 to 5000. `step="0.01"`. Reject non-numeric. If empty and at least one adult is working, treat as 0.
- `numChildren`: bounded 0-8. Stepper buttons enforce. Manual entry disabled.
- All checkboxes and radios validate by definition (have a value).
- No form-level submit. Validation happens inline.

### 9.3 Conditional visibility

- `adult2Age`: shown only if `numAdults == 2`.
- `adult2Working`: shown only if `numAdults == 2`.
- `childAges[]`: one row per child, rendered dynamically.
- `monthlyEarnings`: shown only if `adult1Working` or `adult2Working` is true.
- `lcwraPre2026`: shown only if `lcwra` is true.
- `disabledChild`: shown only if `numChildren > 0`.
- `disabledChildHigher`: shown only if `disabledChild` is true.
- `schoolUniform` row: shown only if at least one child is in the 5 to 15 bracket.

### 9.4 Keyboard and tab order

Logical top-to-bottom, left-to-right. Section A first, then B, then C is read-only. Skip the result panel from tab focus.

### 9.5 Reset

A slim "Start again" button sits at the bottom of the calculator. Pill-shaped (fully rounded ends), dark green `#0a3d2e` background, white text, smaller uppercase, generous horizontal padding, minimal vertical padding so it reads as a slim control rather than a primary CTA. Left-aligned on desktop, full-width-but-narrow on mobile. On click, every input resets to its default and every basket item is re-ticked.

---

## 10. Edge cases and null cases

| Scenario | Behaviour |
|---|---|
| `numAdults = 1`, `numChildren = 0`, all defaults | Single 25+ scenario. Income £98.05/week, basket roughly £119/week, shortfall ~£21/week. |
| User unticks every basket item | Total essentials = £0. Surplus equals income. Subtext changes to: "You haven't selected any essentials. Try ticking the items you actually need each week." |
| `monthlyEarnings` very high (e.g. £4,000) | Taper reduces UC to zero. Income = earnings ÷ 4.33 (weekly). Surplus likely large. Calculator handles correctly without breaking. |
| `numChildren = 8`, all under 5 | Edge of UI but mathematically valid. Render 8 child rows. Calculation runs normally. |
| User switches `numAdults` from 2 to 1 | Adult 2 inputs hide; adult 2 contributions to all formulas drop. |
| User switches both working flags off after entering earnings | Earnings field hides. `monthlyEarnings` retained in state but not used. If user re-ticks either, value reappears. |
| All children removed | School uniform row hides. Disabled child checkbox hides. |
| Income is exactly equal to essentials | Display £0.00 with the "Exactly enough" copy in 7.2. |

---

## 11. Accessibility

| Requirement | Implementation |
|---|---|
| Semantic HTML | Use `<fieldset>` and `<legend>` for grouped inputs. Proper `<label>` for each input. Result panel uses `<output>` or ARIA live region. |
| ARIA live region | The difference figure is announced when it changes. `aria-live="polite"`. |
| Colour | Don't rely on red alone for shortfall. The word "Shortfall" must accompany the colour. |
| Contrast | All text 4.5:1 minimum, large text 3:1. Verify against NBSGF brand colours. |
| Keyboard | Every input reachable and operable with keyboard alone. Visible focus states. |
| Screen reader labels | Each basket row's checkbox label includes the item name and its value, e.g. "Food, £140 per week". |
| Reduced motion | Respect `prefers-reduced-motion`. No animated transitions on the result figures. |
| Reading age | All visible copy at reading age 11. |
| Heading hierarchy | `<h2>` for each section. No skipped levels. |

---

## 12. WordPress packaging

- Plugin shortcode: `[uc_calculator]`. Placeable on any page or post.
- Plugin admin page is not required for v1. All constants edited in `/inc/data.js` (or equivalent).
- Single CSS file scoped to the plugin's container class to avoid theme conflicts.
- Single JS file, vanilla or minimal framework (no React unless the existing site already uses it).
- Translation-ready: wrap all visible strings in `__()` or equivalent.
- No GDPR notice required because no data is collected.

### 12.1 Brand palette

| Token | Hex | Use |
|---|---|---|
| Mid green | `#00ab52` | Emphasis: section labels above headline figures, the active checkbox tick, the input focus ring, small accents intended to draw the eye |
| Dark green | `#0a3d2e` | Sparingly: the slim header strip on the result panel, the Start again pill, optional thin rule under section headings. Never as the background of a whole section. White text always sits on it. |
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

| # | Household | Expected income | Notes |
|---|---|---|---|
| 1 | Single 25+, no work, no disability | £98.05/week | Headline campaign figure. Basket ~£119, shortfall ~£21. |
| 2 | Single under 25, no work | £78.13/week | Bigger gap. |
| 3 | Couple 25+, 0 children, no work | £153.92/week | |
| 4 | Single parent 25+, 1 child age 5-15 | £168.19/week | |
| 5 | Couple 25+, 2 children (1 under 5, 1 in 5-15) | £294.20/week | Largest UC scenario among childed cases. |
| 6 | As (1) but with LCWRA new claimant | £148.19/week | +£217.26/month. |
| 7 | As (1) but with LCWRA pre-April-2026 | £197.24/week | +£429.80/month. Demonstrates the gap between old and new claimants. |
| 8 | As (1) but is a carer | £146.36/week | +£209.34/month. |
| 9a | Single 25+, working £1,200/month net (no children, no LCWRA) | £276.92/week | No work allowance applies. Taper: £1,200 × 0.55 = £660 reduction. UC reduced to £0. Income = earnings only. Powerful campaign point: someone earning below NLW gets zero UC if childless and non-disabled. |
| 9b | Single parent 25+, 1 child 5-15, working £1,200/month net | £382.93/week | Work allowance £710 applies. Excess £490 × 0.55 = £269.50 reduction. Net UC = £728.84 - £269.50 = £459.34. Income = £459.34 + £1,200 = £1,659.34/month. |
| 9c | Couple 25+, both working, total £1,500/month net, no children, no LCWRA | weekly equivalent of (max(0, 666.97 - (1500 × 0.55)) + 1500) | Validates the no-work-allowance branch for couples. UC will reduce to £0; income equals earnings. |
| 10 | All basket items unticked | Surplus = income | Validates the empty-basket message. |

---

## 14. Out of scope for v1

- Saving or sharing a result.
- Multiple working adults with separate earnings.
- Childcare element of UC.
- Housing element of UC and LHA shortfall.
- Council Tax Reduction.
- PIP, DLA for adults, Carer's Allowance.
- Benefit cap.
- Pre-April-2017 first-child rate.
- Comparison to MIS or destitution thresholds (beyond the bottom note).
- Animation / charts.

---

## 15. Confirmed and resolved before build

- Brand palette: see §12.1.
- Bottom note copy: see §8 (final).
- Start again control: slim dark green pill, smaller uppercase white text. See §9.5.
- Children cap: 8.
- JavaScript-disabled fallback: a static paragraph stating the headline figure (£98.05/week for a single adult, April 2026) and a link to Trussell's Guarantee Our Essentials page.