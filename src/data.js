// EDITABLE CONSTANTS — update these when rates change, then run `npm run build`
// April 2026 rates

const UC_RATES = {
  standardAllowance: {
    singleUnder25:     338.58,
    single25Plus:      424.90,
    coupleBothUnder25: 528.34,
    coupleAny25Plus:   666.97,
  },
  childElement:            303.94,
  workAllowanceNoHousing:  710,
  taperRate:               0.55,
  // Benefit cap outside London, monthly. Exempt if take-home earnings reach the threshold.
  benefitCap: {
    family:            1835,
    single:            1229.42,
    earningsThreshold: 881,
  },
  // Child Benefit, weekly. Not counted as income, but counts towards the benefit cap.
  childBenefit: {
    eldest:     27.05,
    additional: 17.90,
  },
};

const COSTS = {
  food: {
    firstAdult:      35,
    additionalAdult: 15,
    childUnder5:     15,
    child5to15:      20,
  },
  energy: {
    bracket: { 1: 25, 2: 30, 3: 38, 4: 38, 5: 45, 6: 45, 7: 45, 8: 45 },
  },
  water: {
    bracket: { 1: 6, 2: 9, 3: 11, 4: 13, 5: 15, 6: 15, 7: 15, 8: 15 },
  },
  mobile: {
    perAdult: 4,
  },
  broadband: {
    flat: 5,
  },
  travel: {
    workingAdult:    28,
    nonWorkingAdult: 10,
    child5to15:      4,
  },
  toiletries: {
    perPerson:     5,
    householdBase: 2,
  },
  cleaning: {
    flat: 4,
  },
  clothes: {
    perAdult:    5,
    childUnder5: 6,
    child5to15:  4,
  },
  schoolUniform: {
    perChild5to15: 6,
  },
  tvLicence: {
    flat: 3.46,
  },
  sundries: {
    single:    13,
    household: 20,
  },
};

module.exports = { UC_RATES, COSTS };
