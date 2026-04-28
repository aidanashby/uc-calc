'use strict';

const { calculateBasketCosts } = require('../src/basket');
const { COSTS } = require('../src/data');

const ALL_TICKED = {
  food: true, energy: true, water: true, mobile: true,
  broadband: true, travel: true, toiletries: true,
  cleaning: true, clothes: true, schoolUniform: true, sundries: true, tvLicence: true,
};

function state(overrides = {}) {
  // Build adults array from shorthand props if explicit adults not provided
  const numAdults = overrides.numAdults !== undefined ? overrides.numAdults : 1;
  const adults = overrides.adults || Array.from({ length: numAdults }, (_, i) => ({
    age: '25plus',
    working: i === 0 ? (overrides.adult1Working || false) : (overrides.adult2Working || false),
    monthlyEarnings: 0,
  }));

  return {
    adults,
    inCouple: overrides.inCouple || false,
    numChildren: overrides.numChildren || 0,
    childAges:   overrides.childAges   || [],
    basket:      overrides.basket      || { ...ALL_TICKED },
  };
}

// Food (firstAdult=35, additionalAdult=15, childUnder5=15, child5to15=20)

test('food: 1 adult only', () => {
  expect(calculateBasketCosts(state(), COSTS).food).toBe(35);
});

test('food: 2 adults', () => {
  expect(calculateBasketCosts(state({ numAdults: 2 }), COSTS).food).toBe(50);
});

test('food: 1 adult + 1 child under 5', () => {
  expect(calculateBasketCosts(state({ numChildren: 1, childAges: ['under5'] }), COSTS).food).toBe(50);
});

test('food: 1 adult + 1 child 5-to-15', () => {
  expect(calculateBasketCosts(state({ numChildren: 1, childAges: ['5to15'] }), COSTS).food).toBe(55);
});

test('food: 2 adults + 2 children', () => {
  // 35 + 15 + 15 + 20 = 85
  expect(calculateBasketCosts(state({
    numAdults: 2,
    numChildren: 2,
    childAges: ['under5', '5to15'],
  }), COSTS).food).toBe(85);
});

// Energy and water — bracket lookups

test('energy: household size 1 → £25', () => {
  expect(calculateBasketCosts(state(), COSTS).energy).toBe(25);
});

test('energy: household size 4 → £38', () => {
  expect(calculateBasketCosts(state({ numAdults: 2, numChildren: 2, childAges: ['5to15', '5to15'] }), COSTS).energy).toBe(38);
});

test('energy: household size capped at 8 for bracket lookup', () => {
  expect(calculateBasketCosts(state({
    numAdults: 2,
    numChildren: 8,
    childAges: ['5to15','5to15','5to15','5to15','5to15','5to15','5to15','5to15'],
  }), COSTS).energy).toBe(45);
});

test('water: household size 1 → £6', () => {
  expect(calculateBasketCosts(state(), COSTS).water).toBe(6);
});

test('water: household size 3 → £11', () => {
  expect(calculateBasketCosts(state({ numAdults: 1, numChildren: 2, childAges: ['5to15','5to15'] }), COSTS).water).toBe(11);
});

// Mobile

test('mobile: 1 adult → £4', () => {
  expect(calculateBasketCosts(state(), COSTS).mobile).toBe(4);
});

test('mobile: 2 adults → £8', () => {
  expect(calculateBasketCosts(state({ numAdults: 2 }), COSTS).mobile).toBe(8);
});

test('mobile: 3 adults → £12', () => {
  expect(calculateBasketCosts(state({ numAdults: 3 }), COSTS).mobile).toBe(12);
});

// Broadband

test('broadband: always flat £5', () => {
  expect(calculateBasketCosts(state(), COSTS).broadband).toBe(5);
  expect(calculateBasketCosts(state({ numAdults: 2, numChildren: 3, childAges: ['5to15','5to15','5to15'] }), COSTS).broadband).toBe(5);
});

// Travel — per-adult working flags

test('travel: 1 adult, not working → £10', () => {
  expect(calculateBasketCosts(state(), COSTS).travel).toBe(10);
});

test('travel: 1 adult, working → £22', () => {
  expect(calculateBasketCosts(state({ adult1Working: true }), COSTS).travel).toBe(22);
});

test('travel: 2 adults, neither working → £20', () => {
  expect(calculateBasketCosts(state({ numAdults: 2 }), COSTS).travel).toBe(20);
});

test('travel: 2 adults, adult1 working only → £32', () => {
  expect(calculateBasketCosts(state({ numAdults: 2, adult1Working: true }), COSTS).travel).toBe(32);
});

test('travel: 2 adults, both working → £44', () => {
  expect(calculateBasketCosts(state({ numAdults: 2, adult1Working: true, adult2Working: true }), COSTS).travel).toBe(44);
});

test('travel: 3 adults, all working → £66', () => {
  expect(calculateBasketCosts(state({ numAdults: 3, adult1Working: true, adult2Working: true }), COSTS).travel).toBe(66);
});

test('travel: child 5-to-15 adds £4', () => {
  expect(calculateBasketCosts(state({ numChildren: 1, childAges: ['5to15'] }), COSTS).travel).toBe(14);
});

// Toiletries

test('toiletries: 1 adult → 5 + 2 = £7', () => {
  expect(calculateBasketCosts(state(), COSTS).toiletries).toBe(7);
});

test('toiletries: 2 adults → 10 + 2 = £12', () => {
  expect(calculateBasketCosts(state({ numAdults: 2 }), COSTS).toiletries).toBe(12);
});

test('toiletries: 1 adult + 2 children → 15 + 2 = £17', () => {
  expect(calculateBasketCosts(state({ numChildren: 2, childAges: ['under5','5to15'] }), COSTS).toiletries).toBe(17);
});

// Cleaning

test('cleaning: always flat £4', () => {
  expect(calculateBasketCosts(state(), COSTS).cleaning).toBe(4);
});

// Clothes

test('clothes: 1 adult → £5', () => {
  expect(calculateBasketCosts(state(), COSTS).clothes).toBe(5);
});

test('clothes: 2 adults + 1 under-5 + 1 five-to-15 → 5+5+6+4 = £20', () => {
  expect(calculateBasketCosts(state({
    numAdults: 2,
    numChildren: 2,
    childAges: ['under5','5to15'],
  }), COSTS).clothes).toBe(20);
});

// School uniform

test('school uniform: 0 when no 5-to-15 children regardless of basket tick', () => {
  expect(calculateBasketCosts(state(), COSTS).schoolUniform).toBe(0);
});

test('school uniform: 0 when children are all under 5', () => {
  expect(calculateBasketCosts(state({ numChildren: 2, childAges: ['under5','under5'] }), COSTS).schoolUniform).toBe(0);
});

test('school uniform: £6 per 5-to-15 child', () => {
  expect(calculateBasketCosts(state({ numChildren: 1, childAges: ['5to15'] }), COSTS).schoolUniform).toBe(6);
  expect(calculateBasketCosts(state({ numChildren: 2, childAges: ['5to15','5to15'] }), COSTS).schoolUniform).toBe(12);
});

// TV licence

test('tvLicence: always £3.46', () => {
  expect(calculateBasketCosts(state(), COSTS).tvLicence).toBe(3.46);
});

// Sundries

test('sundries: single person → £13', () => {
  expect(calculateBasketCosts(state(), COSTS).sundries).toBe(13);
});

test('sundries: 2 adults → £20', () => {
  expect(calculateBasketCosts(state({ numAdults: 2 }), COSTS).sundries).toBe(20);
});

test('sundries: 1 adult + 1 child → £20', () => {
  expect(calculateBasketCosts(state({ numChildren: 1, childAges: ['5to15'] }), COSTS).sundries).toBe(20);
});

// Basket totals

test('scenario 1 default basket — single 25+, not working', () => {
  // food=35 energy=25 water=6 mobile=4 broadband=5 travel=10 toiletries=7 cleaning=4 clothes=5 schoolUniform=0 tvLicence=3.46 sundries=13
  // total = 117.46
  expect(calculateBasketCosts(state(), COSTS).total).toBe(117.46);
});

// Unticked items

test('unticked item contributes £0 to total', () => {
  const result = calculateBasketCosts(state({
    basket: { ...ALL_TICKED, food: false },
  }), COSTS);
  expect(result.food).toBe(0);
});

test('all items unticked: total is £0', () => {
  const result = calculateBasketCosts(state({
    basket: {
      food: false, energy: false, water: false, mobile: false,
      broadband: false, travel: false, toiletries: false,
      cleaning: false, clothes: false, schoolUniform: false, sundries: false, tvLicence: false,
    },
  }), COSTS);
  expect(result.total).toBe(0);
});

test('school uniform unticked: £0 even with 5-to-15 children', () => {
  const result = calculateBasketCosts(state({
    numChildren: 1,
    childAges: ['5to15'],
    basket: { ...ALL_TICKED, schoolUniform: false },
  }), COSTS);
  expect(result.schoolUniform).toBe(0);
});
