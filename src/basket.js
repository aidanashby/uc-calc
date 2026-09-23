'use strict';

function calculateBasketCosts(state, costs) {
  const adults = state.adults;
  const numAdults = adults.length;
  const numUnder5 = state.childAges.filter(a => a === 'under5').length;
  const num5to15  = state.childAges.filter(a => a === '5to15').length;
  const householdSize = Math.min(numAdults + state.numChildren, 8);

  // What each item costs this household, whether or not it is ticked. The
  // basket shows these (struck through when unticked); only ticked items
  // count towards the total. An item that doesn't apply costs 0 here, e.g.
  // school uniform with no children aged 5 to 15.
  const raw = {
    food: costs.food.firstAdult
      + costs.food.additionalAdult * (numAdults - 1)
      + costs.food.childUnder5 * numUnder5
      + costs.food.child5to15  * num5to15,
    energy: costs.energy.bracket[householdSize],
    water:  costs.water.bracket[householdSize],
    mobile: costs.mobile.perAdult * numAdults,
    broadband: costs.broadband.flat,
    travel: adults.reduce((sum, a) =>
      sum + (a.working ? costs.travel.workingAdult : costs.travel.nonWorkingAdult), 0)
      + costs.travel.child5to15 * num5to15,
    toiletries: costs.toiletries.perPerson * (numAdults + state.numChildren)
      + costs.toiletries.householdBase,
    cleaning: costs.cleaning.flat,
    clothes: costs.clothes.perAdult   * numAdults
      + costs.clothes.childUnder5 * numUnder5
      + costs.clothes.child5to15  * num5to15,
    schoolUniform: costs.schoolUniform.perChild5to15 * num5to15,
    tvLicence: costs.tvLicence.flat,
    sundries: householdSize === 1 ? costs.sundries.single : costs.sundries.household,
  };

  const ticked = {};
  let total = 0;
  Object.keys(raw).forEach(key => {
    ticked[key] = state.basket[key] ? raw[key] : 0;
    total += ticked[key];
  });

  return { ...ticked, total, raw };
}

module.exports = { calculateBasketCosts };
