'use strict';

// Default English copy for the four result states. Used as a JS-side fallback
// when wp_localize_script doesn't supply translated strings (e.g. in tests).
// In production these are overridden by ucCalcData.i18n.states from the server.
const COPY = {
  shortfall: {
    label: 'Shortfall',
    subtext: 'This is what you’d need to find from somewhere else each week, on top of what UC provides. And that’s before rent and council tax.',
  },
  even: {
    label: 'Exactly enough',
    subtext: 'Nothing left for anything unexpected, and that’s before rent and council tax. A washing machine breakdown, a school trip, a winter coat, a funeral, a delayed payment.',
  },
  surplus: {
    label: 'Left before rent and council tax',
    subtext: 'This is before rent and council tax. Many households on UC have to pay part of their rent from this money. Anything left has to cover the unexpected, like dental costs, a school trip or a winter coat.',
  },
  empty: {
    label: 'Money left to save or for emergencies',
    subtext: 'You haven’t selected any essentials. Try ticking the items you actually need each week.',
  },
};

/**
 * Format a number as £X.XX with thousands separator. Always returns the
 * absolute value — negative differences are presented as "Shortfall: £X".
 *
 * @param {number} n
 * @returns {string}
 */
function formatCurrency(n) {
  const abs     = Math.abs(n);
  const rounded = Math.round(abs * 100) / 100;
  return '£' + rounded.toLocaleString('en-GB', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

const round2 = n => Math.round(n * 100) / 100;

/**
 * Determine the display state from income and essentials totals.
 *
 * @param {number} income     Weekly income (raw, rounded internally to 2dp)
 * @param {number} essentials Weekly essentials total (0 = all unticked)
 * @returns {{ type: 'empty'|'shortfall'|'even'|'surplus', colourClass: string, difference: number }}
 */
function getResultState(income, essentials) {
  const roundedIncome     = round2(income);
  const roundedEssentials = round2(essentials);

  if (roundedEssentials === 0) {
    return { type: 'empty', colourClass: '', difference: roundedIncome };
  }

  const diff = round2(roundedIncome - roundedEssentials);

  if (diff < 0) {
    return { type: 'shortfall', colourClass: 'uc-calc--shortfall', difference: Math.abs(diff) };
  }
  if (diff === 0) {
    return { type: 'even', colourClass: '', difference: 0 };
  }
  return { type: 'surplus', colourClass: '', difference: diff };
}

module.exports = { formatCurrency, getResultState, COPY };
