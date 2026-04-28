'use strict';

function calculateUCIncome(state, rates) {
  const sa = rates.standardAllowance;
  const adults = state.adults;
  let totalMonthly = 0;

  if (state.inCouple && adults.length >= 2) {
    // Adults 0 and 1 form the couple household unit (with children)
    const a0 = adults[0], a1 = adults[1];
    const coupleUC = ((a0.age === '25plus' || a1.age === '25plus')
      ? sa.coupleAny25Plus : sa.coupleBothUnder25)
      + state.numChildren * rates.childElement;

    const coupleEarnings = (a0.working ? (a0.monthlyEarnings || 0) : 0)
                         + (a1.working ? (a1.monthlyEarnings || 0) : 0);
    const workAllowance = state.numChildren > 0 ? rates.workAllowanceNoHousing : 0;
    const excess = Math.max(0, coupleEarnings - workAllowance);
    const coupleNetUC = Math.max(0, coupleUC - excess * rates.taperRate);
    totalMonthly += coupleNetUC + coupleEarnings;

    // Adults 2+ as individual single claimants (no children, no work allowance)
    for (let i = 2; i < adults.length; i++) {
      const a = adults[i];
      const singleUC = a.age === '25plus' ? sa.single25Plus : sa.singleUnder25;
      const earnings = a.working ? (a.monthlyEarnings || 0) : 0;
      const netUC = Math.max(0, singleUC - earnings * rates.taperRate);
      totalMonthly += netUC + earnings;
    }
  } else {
    // Each adult is an individual single claimant; children belong to adult 0
    for (let i = 0; i < adults.length; i++) {
      const a = adults[i];
      let uc = a.age === '25plus' ? sa.single25Plus : sa.singleUnder25;
      if (i === 0) uc += state.numChildren * rates.childElement;

      const earnings = a.working ? (a.monthlyEarnings || 0) : 0;
      const workAllowance = (i === 0 && state.numChildren > 0) ? rates.workAllowanceNoHousing : 0;
      const excess = Math.max(0, earnings - workAllowance);
      const netUC = Math.max(0, uc - excess * rates.taperRate);
      totalMonthly += netUC + earnings;
    }
  }

  return totalMonthly * 12 / 52;
}

module.exports = { calculateUCIncome };
