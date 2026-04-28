'use strict';

const { calculateUCIncome } = require('../src/calculator');
const { UC_RATES } = require('../src/data');

function round2(n) {
  return Math.round(n * 100) / 100;
}

// Build a single-adult state (most common scenario)
function singleState(overrides = {}) {
  return {
    adults: [{ age: overrides.adult1Age || '25plus', working: overrides.adult1Working || false, monthlyEarnings: overrides.adult1MonthlyEarnings || 0 }],
    inCouple: false,
    numChildren: overrides.numChildren || 0,
    childAges:   overrides.childAges   || [],
  };
}

// Build a couple state (inCouple = true, two adults)
function coupleState(overrides = {}) {
  return {
    adults: [
      { age: overrides.adult1Age || '25plus', working: overrides.adult1Working || false, monthlyEarnings: overrides.adult1MonthlyEarnings || 0 },
      { age: overrides.adult2Age || '25plus', working: overrides.adult2Working || false, monthlyEarnings: overrides.adult2MonthlyEarnings || 0 },
    ],
    inCouple: true,
    numChildren: overrides.numChildren || 0,
    childAges:   overrides.childAges   || [],
  };
}

// QA scenarios

test('scenario 1: single 25+, no work', () => {
  expect(round2(calculateUCIncome(singleState(), UC_RATES))).toBe(98.05);
});

test('scenario 2: single under 25, no work', () => {
  expect(round2(calculateUCIncome(singleState({ adult1Age: 'under25' }), UC_RATES))).toBe(78.13);
});

test('scenario 3: couple 25+, no children, no work', () => {
  expect(round2(calculateUCIncome(coupleState(), UC_RATES))).toBe(153.92);
});

test('scenario 4: single parent 25+, 1 child 5-15, no work', () => {
  expect(round2(calculateUCIncome(singleState({ numChildren: 1, childAges: ['5to15'] }), UC_RATES))).toBe(168.19);
});

test('scenario 5: couple 25+, 2 children (1 under 5, 1 five-to-15)', () => {
  expect(round2(calculateUCIncome(coupleState({
    numChildren: 2,
    childAges: ['under5', '5to15'],
  }), UC_RATES))).toBe(294.20);
});

test('scenario 9: single 25+, working £1,200/month — no work allowance (no children)', () => {
  // Taper from £0: reduction = 1200 × 0.55 = 660. UC = max(0, 424.90 - 660) = 0
  // Income = earnings only: 1200 × 12/52 = 276.92
  expect(round2(calculateUCIncome(singleState({
    adult1Working: true,
    adult1MonthlyEarnings: 1200,
  }), UC_RATES))).toBe(276.92);
});

// Work allowance — only when household has children

test('work allowance applies when household has children', () => {
  // Single 25+, 1 child, working £673/month (exactly at work allowance)
  // excess = 673 - 673 = 0. No reduction.
  const monthly = UC_RATES.standardAllowance.single25Plus + UC_RATES.childElement + 673;
  expect(round2(calculateUCIncome(singleState({
    numChildren: 1,
    childAges: ['5to15'],
    adult1Working: true,
    adult1MonthlyEarnings: 673,
  }), UC_RATES))).toBe(round2(monthly * 12 / 52));
});

test('no work allowance for childless working person', () => {
  const monthly = (UC_RATES.standardAllowance.single25Plus - 200 * 0.55) + 200;
  expect(round2(calculateUCIncome(singleState({
    adult1Working: true,
    adult1MonthlyEarnings: 200,
  }), UC_RATES))).toBe(round2(monthly * 12 / 52));
});

// Per-adult earnings

test('two adults in couple, both working: combined earnings in taper', () => {
  const monthly = Math.max(0, UC_RATES.standardAllowance.coupleAny25Plus - 1200 * 0.55) + 1200;
  expect(round2(calculateUCIncome(coupleState({
    adult1Working: true,
    adult1MonthlyEarnings: 600,
    adult2Working: true,
    adult2MonthlyEarnings: 600,
  }), UC_RATES))).toBe(round2(monthly * 12 / 52));
});

test('couple: only working adult earnings count in taper', () => {
  const monthly = Math.max(0, UC_RATES.standardAllowance.coupleAny25Plus - 600 * 0.55) + 600;
  expect(round2(calculateUCIncome(coupleState({
    adult1Working: true,
    adult1MonthlyEarnings: 600,
    adult2Working: false,
  }), UC_RATES))).toBe(round2(monthly * 12 / 52));
});

// Standard allowance selection

test('couple: one over 25 triggers coupleAny25Plus rate', () => {
  expect(round2(calculateUCIncome(coupleState({
    adult1Age: 'under25',
    adult2Age: '25plus',
  }), UC_RATES))).toBe(round2(UC_RATES.standardAllowance.coupleAny25Plus * 12 / 52));
});

test('couple: both under 25 triggers coupleBothUnder25 rate', () => {
  expect(round2(calculateUCIncome(coupleState({
    adult1Age: 'under25',
    adult2Age: 'under25',
  }), UC_RATES))).toBe(round2(UC_RATES.standardAllowance.coupleBothUnder25 * 12 / 52));
});

test('two non-couple adults each get single rate', () => {
  // Both single rate, no couple bonus
  const monthly = UC_RATES.standardAllowance.single25Plus * 2;
  expect(round2(calculateUCIncome({
    adults: [
      { age: '25plus', working: false, monthlyEarnings: 0 },
      { age: '25plus', working: false, monthlyEarnings: 0 },
    ],
    inCouple: false,
    numChildren: 0,
    childAges: [],
  }, UC_RATES))).toBe(round2(monthly * 12 / 52));
});

test('third adult (non-couple) gets own single rate on top of couple', () => {
  const coupleMonthly = UC_RATES.standardAllowance.coupleAny25Plus;
  const thirdAdultMonthly = UC_RATES.standardAllowance.single25Plus;
  expect(round2(calculateUCIncome({
    adults: [
      { age: '25plus', working: false, monthlyEarnings: 0 },
      { age: '25plus', working: false, monthlyEarnings: 0 },
      { age: '25plus', working: false, monthlyEarnings: 0 },
    ],
    inCouple: true,
    numChildren: 0,
    childAges: [],
  }, UC_RATES))).toBe(round2((coupleMonthly + thirdAdultMonthly) * 12 / 52));
});

test('UC tapers cleanly to zero (single, no children)', () => {
  expect(round2(calculateUCIncome(singleState({
    adult1Working: true,
    adult1MonthlyEarnings: 4000,
  }), UC_RATES))).toBe(round2(4000 * 12 / 52));
});

test('uses × 12/52 for monthly-to-weekly conversion', () => {
  const raw = calculateUCIncome(singleState(), UC_RATES);
  expect(raw).toBeCloseTo(UC_RATES.standardAllowance.single25Plus * 12 / 52, 5);
});
