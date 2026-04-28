'use strict';

function calculateBasketCosts(state, costs) {
  const adults = state.adults;
  const numAdults = adults.length;
  const numUnder5 = state.childAges.filter(a => a === 'under5').length;
  const num5to15  = state.childAges.filter(a => a === '5to15').length;
  const householdSize = Math.min(numAdults + state.numChildren, 8);

  function itemCost(key, raw) {
    return state.basket[key] ? raw : 0;
  }

  const food = itemCost('food',
    costs.food.firstAdult
    + costs.food.additionalAdult * (numAdults - 1)
    + costs.food.childUnder5 * numUnder5
    + costs.food.child5to15  * num5to15
  );

  const energy = itemCost('energy', costs.energy.bracket[householdSize]);
  const water  = itemCost('water',  costs.water.bracket[householdSize]);
  const mobile = itemCost('mobile', costs.mobile.perAdult * numAdults);
  const broadband = itemCost('broadband', costs.broadband.flat);

  const travel = itemCost('travel',
    adults.reduce((sum, a) =>
      sum + (a.working ? costs.travel.workingAdult : costs.travel.nonWorkingAdult), 0)
    + costs.travel.child5to15 * num5to15
  );

  const toiletries = itemCost('toiletries',
    costs.toiletries.perPerson * (numAdults + state.numChildren)
    + costs.toiletries.householdBase
  );

  const cleaning = itemCost('cleaning', costs.cleaning.flat);

  const clothes = itemCost('clothes',
    costs.clothes.perAdult   * numAdults
    + costs.clothes.childUnder5 * numUnder5
    + costs.clothes.child5to15  * num5to15
  );

  // School uniform: zero if no 5-to-15 children regardless of basket tick
  const schoolUniform = (num5to15 > 0 && state.basket.schoolUniform)
    ? costs.schoolUniform.perChild5to15 * num5to15
    : 0;

  const tvLicence = itemCost('tvLicence', costs.tvLicence.flat);

  const sundries = itemCost('sundries',
    householdSize === 1 ? costs.sundries.single : costs.sundries.household
  );

  const total = food + energy + water + mobile + broadband + travel
    + toiletries + cleaning + clothes + schoolUniform + tvLicence + sundries;

  return { food, energy, water, mobile, broadband, travel, toiletries, cleaning, clothes, schoolUniform, tvLicence, sundries, total };
}

module.exports = { calculateBasketCosts };
