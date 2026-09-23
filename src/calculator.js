'use strict';

// Monthly Child Benefit for a benefit unit. Not counted as income by the
// calculator, but it counts towards the benefit cap, so it reduces the room
// left for UC under the cap.
function monthlyChildBenefit(numChildren, rates) {
  if (!rates.childBenefit || numChildren === 0) return 0;
  const weekly = rates.childBenefit.eldest + (numChildren - 1) * rates.childBenefit.additional;
  return weekly * 52 / 12;
}

// Apply the benefit cap to one benefit unit's UC award. Couples and single
// parents get the family cap; single people without children get the single
// cap. Exempt if the unit's take-home earnings reach the threshold.
function applyBenefitCap(netUC, earnings, isFamily, numChildren, rates) {
  const cap = rates.benefitCap;
  if (!cap || earnings >= cap.earningsThreshold) return { uc: netUC, capped: false };

  const limit = (isFamily ? cap.family : cap.single) - monthlyChildBenefit(numChildren, rates);
  if (netUC <= limit) return { uc: netUC, capped: false };
  return { uc: Math.max(0, limit), capped: true };
}

/**
 * Weekly household income (UC plus earnings) and whether the benefit cap
 * reduced any benefit unit's UC.
 *
 * @returns {{ weekly: number, capped: boolean }}
 */
function calculateUC(state, rates) {
  const sa = rates.standardAllowance;
  const adults = state.adults;
  let totalMonthly = 0;
  let capped = false;

  function addUnit(maxUC, earnings, isFamily, numChildren) {
    const workAllowance = numChildren > 0 ? rates.workAllowanceNoHousing : 0;
    const excess = Math.max(0, earnings - workAllowance);
    const netUC = Math.max(0, maxUC - excess * rates.taperRate);
    const result = applyBenefitCap(netUC, earnings, isFamily, numChildren, rates);
    if (result.capped) capped = true;
    totalMonthly += result.uc + earnings;
  }

  function earningsOf(a) {
    return a.working ? Math.max(0, a.monthlyEarnings || 0) : 0;
  }

  if (state.inCouple && adults.length >= 2) {
    // Adults 0 and 1 form the couple household unit (with children)
    const a0 = adults[0], a1 = adults[1];
    const coupleUC = ((a0.age === '25plus' || a1.age === '25plus')
      ? sa.coupleAny25Plus : sa.coupleBothUnder25)
      + state.numChildren * rates.childElement;
    addUnit(coupleUC, earningsOf(a0) + earningsOf(a1), true, state.numChildren);

    // Adults 2+ as individual single claimants (no children, no work allowance)
    for (let i = 2; i < adults.length; i++) {
      const a = adults[i];
      addUnit(a.age === '25plus' ? sa.single25Plus : sa.singleUnder25, earningsOf(a), false, 0);
    }
  } else {
    // Each adult is an individual single claimant; children belong to adult 0
    for (let i = 0; i < adults.length; i++) {
      const a = adults[i];
      const children = i === 0 ? state.numChildren : 0;
      const uc = (a.age === '25plus' ? sa.single25Plus : sa.singleUnder25)
        + children * rates.childElement;
      addUnit(uc, earningsOf(a), children > 0, children);
    }
  }

  return { weekly: totalMonthly * 12 / 52, capped };
}

function calculateUCIncome(state, rates) {
  return calculateUC(state, rates).weekly;
}

module.exports = { calculateUC, calculateUCIncome };
