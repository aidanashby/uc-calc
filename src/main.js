'use strict';

const { UC_RATES: defaultRates, COSTS: defaultCosts } = require('./data');
const { calculateUCIncome }                          = require('./calculator');
const { calculateBasketCosts }                       = require('./basket');
const { getResultState, formatCurrency, COPY }       = require('./result');

// ─── Settings + i18n from WordPress (wp_localize_script) ─────────────────────

const _data = (typeof window !== 'undefined' && window.ucCalcData) || null;

const UC_RATES = (_data && _data.rates) ? _data.rates : defaultRates;
const COSTS    = (_data && _data.costs) ? _data.costs : defaultCosts;

// English fallback used when the bundle runs outside WordPress (e.g. tests
// or static demos). In production the server passes its own translated set.
const DEFAULT_I18N = {
  adultHeadings:      ['', 'Second adult', 'Third adult', 'Fourth adult', 'Fifth adult', 'Sixth adult'],
  ordinals:           ['first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth'],
  ageLegendSelf:      'How old are you?',
  ageLegendOther:     'How old are they?',
  ageUnder25:         'Under 25',
  ageOver25:          '25 or over',
  workingSelf:        'I am currently in work',
  workingOther:       'Currently in work',
  earningsLabelSelf:  'Your monthly take-home pay',
  earningsLabelOther: 'Monthly take-home pay',
  earningsHint:       'Net (take-home) pay after tax and National Insurance.',
  childAgeLegend:     'How old is your %s child?',
  childAgeUnder5:     'Under 5',
  childAge5to15:      '5 to 15',
  basketAriaIncluded: '%1$s, %2$s per week',
  basketAriaExcluded: '%1$s, %2$s per week, excluded',
  states:             COPY,
};

const I18N = (_data && _data.i18n) ? _data.i18n : DEFAULT_I18N;

// ─── Constants ────────────────────────────────────────────────────────────────

const MAX_ADULTS   = 6;
const MAX_CHILDREN = 8;

const BASKET_KEYS = [
  'food', 'energy', 'water', 'mobile', 'broadband', 'travel',
  'toiletries', 'cleaning', 'clothes', 'schoolUniform', 'sundries', 'tvLicence',
];

// ─── State ───────────────────────────────────────────────────────────────────

const DEFAULT_STATE = {
  adults: [{ age: '25plus', working: false, monthlyEarnings: 0 }],
  inCouple: false,
  numChildren: 0,
  childAges: [],
  basket: BASKET_KEYS.reduce((acc, k) => { acc[k] = true; return acc; }, {}),
};

function cloneDefaultState() {
  return {
    ...DEFAULT_STATE,
    adults:    [{ ...DEFAULT_STATE.adults[0] }],
    childAges: [],
    basket:    { ...DEFAULT_STATE.basket },
  };
}

let state = cloneDefaultState();

// ─── Helpers ─────────────────────────────────────────────────────────────────

function debounce(fn, ms) {
  let timer;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), ms);
  };
}

function format(template, ...args) {
  // Lightweight printf: replaces %s and positional %1$s, %2$s, … in order.
  let i = 0;
  return template
    .replace(/%(\d+)\$s/g, (_, n) => args[parseInt(n, 10) - 1] ?? '')
    .replace(/%s/g, () => args[i++] ?? '');
}

// ─── Adult row factory ────────────────────────────────────────────────────────

function createAdultRow(i, adult) {
  const row = document.createElement('div');
  row.className = 'uc-calc__adult-row';

  if (i > 0) {
    const heading = document.createElement('p');
    heading.className   = 'uc-calc__adult-heading';
    heading.textContent = I18N.adultHeadings[i] || '';
    row.appendChild(heading);
  }

  // Age fieldset
  const fieldset = document.createElement('fieldset');
  fieldset.className = 'uc-calc__fieldset';
  const legend = document.createElement('legend');
  legend.className   = 'uc-calc__legend';
  legend.textContent = i === 0 ? I18N.ageLegendSelf : I18N.ageLegendOther;
  fieldset.appendChild(legend);

  const radioGroup = document.createElement('div');
  radioGroup.className = 'uc-calc__radio-group';
  [['under25', I18N.ageUnder25], ['25plus', I18N.ageOver25]].forEach(([value, label]) => {
    const lbl = document.createElement('label');
    lbl.className = 'uc-calc__radio-label';
    const input = document.createElement('input');
    input.type      = 'radio';
    input.name      = `adultAge_${i}`;
    input.value     = value;
    input.className = 'uc-calc__radio';
    input.checked   = value === adult.age;
    input.dataset.adultIndex = i;
    const span = document.createElement('span');
    span.className   = 'uc-calc__radio-text';
    span.textContent = label;
    lbl.appendChild(input);
    lbl.appendChild(span);
    radioGroup.appendChild(lbl);
  });
  fieldset.appendChild(radioGroup);
  row.appendChild(fieldset);

  // Working checkbox
  const workGroup = document.createElement('div');
  workGroup.className = 'uc-calc__field-group';
  const workLabel = document.createElement('label');
  workLabel.className = 'uc-calc__checkbox-label';
  const workInput = document.createElement('input');
  workInput.type      = 'checkbox';
  workInput.name      = `adultWorking_${i}`;
  workInput.className = 'uc-calc__checkbox';
  workInput.checked   = adult.working;
  workInput.dataset.adultIndex = i;
  const workText = document.createElement('span');
  workText.className   = 'uc-calc__checkbox-text';
  workText.textContent = i === 0 ? I18N.workingSelf : I18N.workingOther;
  workLabel.appendChild(workInput);
  workLabel.appendChild(workText);
  workGroup.appendChild(workLabel);
  row.appendChild(workGroup);

  // Earnings group
  const earnGroup = document.createElement('div');
  earnGroup.className = 'uc-calc__field-group uc-calc__field-group--indented';
  earnGroup.id        = `adult-earnings-group-${i}`;
  earnGroup.hidden    = !adult.working;

  const earnLbl = document.createElement('label');
  earnLbl.className   = 'uc-calc__input-label';
  earnLbl.htmlFor     = `adultEarnings_${i}`;
  earnLbl.textContent = i === 0 ? I18N.earningsLabelSelf : I18N.earningsLabelOther;

  const inputWrap = document.createElement('div');
  inputWrap.className = 'uc-calc__input-wrap';
  const prefix = document.createElement('span');
  prefix.className = 'uc-calc__input-prefix';
  prefix.setAttribute('aria-hidden', 'true');
  prefix.textContent = '£';

  const earnInput = document.createElement('input');
  earnInput.type        = 'number';
  earnInput.name        = `adultEarnings_${i}`;
  earnInput.id          = `adultEarnings_${i}`;
  earnInput.className   = 'uc-calc__earnings-input';
  earnInput.min         = '0';
  earnInput.max         = '20000';
  earnInput.step        = '0.01';
  earnInput.placeholder = '0';
  earnInput.setAttribute('inputmode', 'decimal');
  earnInput.dataset.adultIndex = i;

  inputWrap.appendChild(prefix);
  inputWrap.appendChild(earnInput);
  earnGroup.appendChild(earnLbl);
  earnGroup.appendChild(inputWrap);

  if (i === 0) {
    const hint = document.createElement('p');
    hint.className   = 'uc-calc__input-hint';
    hint.textContent = I18N.earningsHint;
    earnGroup.appendChild(hint);
  }
  row.appendChild(earnGroup);

  return row;
}

// ─── Render functions ─────────────────────────────────────────────────────────

function updateVisibility(s) {
  const numAdults = s.adults.length;

  const adultsDisplay = document.getElementById('num-adults-display');
  if (adultsDisplay) {
    adultsDisplay.textContent = numAdults;
    adultsDisplay.setAttribute('aria-valuenow', numAdults);
  }
  const adultsDecBtn = document.getElementById('adults-decrease');
  const adultsIncBtn = document.getElementById('adults-increase');
  if (adultsDecBtn) adultsDecBtn.disabled = numAdults === 1;
  if (adultsIncBtn) adultsIncBtn.disabled = numAdults === MAX_ADULTS;

  const coupleGroup = document.getElementById('couple-group');
  if (coupleGroup) coupleGroup.hidden = numAdults < 2;

  const childDisplay = document.getElementById('num-children-display');
  if (childDisplay) {
    childDisplay.textContent = s.numChildren;
    childDisplay.setAttribute('aria-valuenow', s.numChildren);
  }
  const decBtn = document.getElementById('children-decrease');
  const incBtn = document.getElementById('children-increase');
  if (decBtn) decBtn.disabled = s.numChildren === 0;
  if (incBtn) incBtn.disabled = s.numChildren === MAX_CHILDREN;
}

function renderAdultRows(s) {
  const container = document.getElementById('adult-rows-container');
  if (!container) return;

  let count = container.querySelectorAll('.uc-calc__adult-row').length;
  while (count < s.adults.length) {
    container.appendChild(createAdultRow(count, s.adults[count]));
    count++;
  }
  while (container.querySelectorAll('.uc-calc__adult-row').length > s.adults.length) {
    container.removeChild(container.lastElementChild);
  }

  container.querySelectorAll('.uc-calc__adult-row').forEach((row, i) => {
    if (i >= s.adults.length) return;
    const adult = s.adults[i];

    const earnGroup = document.getElementById(`adult-earnings-group-${i}`);
    if (earnGroup) earnGroup.hidden = !adult.working;

    row.querySelectorAll(`input[name="adultAge_${i}"]`).forEach(r => { r.checked = r.value === adult.age; });

    const workCheck = row.querySelector(`input[name="adultWorking_${i}"]`);
    if (workCheck) workCheck.checked = adult.working;

    const earningsInput = row.querySelector(`input[name="adultEarnings_${i}"]`);
    if (earningsInput && document.activeElement !== earningsInput) {
      earningsInput.value = adult.monthlyEarnings || '';
    }
  });
}

function updateBasketRows(s, costs) {
  BASKET_KEYS.forEach(key => {
    const costEl = document.getElementById(`basket-cost-${key}`);
    const rowEl  = document.getElementById(`basket-row-${key}`);
    const check  = rowEl ? rowEl.querySelector('[data-item]') : null;
    const nameEl = rowEl ? rowEl.querySelector('.uc-calc__basket-name') : null;
    const isOn   = s.basket[key];

    if (costEl) costEl.textContent = formatCurrency(costs[key]);

    if (check && nameEl) {
      const template = isOn ? I18N.basketAriaIncluded : I18N.basketAriaExcluded;
      check.setAttribute('aria-label', format(template, nameEl.textContent, formatCurrency(costs[key])));
    }

    if (rowEl) {
      rowEl.classList.toggle('uc-calc__basket-row--unchecked', !isOn);
      rowEl.hidden = costs[key] === 0;
    }
  });
}

function renderChildAges(s) {
  const container = document.getElementById('child-ages-container');
  if (!container) return;

  const existing = container.querySelectorAll('.uc-calc__child-age-row');

  for (let i = existing.length; i < s.numChildren; i++) {
    const age = s.childAges[i] || '5to15';
    const fieldset = document.createElement('fieldset');
    fieldset.className = 'uc-calc__fieldset uc-calc__child-age-row';

    const legend = document.createElement('legend');
    legend.className   = 'uc-calc__legend';
    legend.textContent = format(I18N.childAgeLegend, I18N.ordinals[i] || `#${i + 1}`);
    fieldset.appendChild(legend);

    const group = document.createElement('div');
    group.className = 'uc-calc__radio-group';

    [['under5', I18N.childAgeUnder5], ['5to15', I18N.childAge5to15]].forEach(([value, label]) => {
      const lbl = document.createElement('label');
      lbl.className = 'uc-calc__radio-label';
      const input = document.createElement('input');
      input.type      = 'radio';
      input.name      = `childAge_${i}`;
      input.value     = value;
      input.className = 'uc-calc__radio';
      input.checked   = value === age;
      input.dataset.childIndex = i;
      const span = document.createElement('span');
      span.className   = 'uc-calc__radio-text';
      span.textContent = label;
      lbl.appendChild(input);
      lbl.appendChild(span);
      group.appendChild(lbl);
    });

    fieldset.appendChild(group);
    container.appendChild(fieldset);
  }

  while (container.children.length > s.numChildren) {
    container.removeChild(container.lastChild);
  }
}

function updateResultPanel(income, essentialsTotal, result) {
  const incomeEl      = document.getElementById('uc-calc-income');
  const essentialsEl  = document.getElementById('uc-calc-essentials');
  const diffEl        = document.getElementById('uc-calc-difference');
  const labelEl       = document.getElementById('uc-calc-state-label');
  const subtextEl     = document.getElementById('uc-calc-subtext');
  const resultSection = document.getElementById('uc-calc-result');

  const stateCopy = (I18N.states && I18N.states[result.type]) || COPY[result.type];

  if (incomeEl)     incomeEl.textContent     = formatCurrency(income);
  if (essentialsEl) essentialsEl.textContent = formatCurrency(essentialsTotal);
  if (diffEl)       diffEl.textContent       = formatCurrency(result.difference);
  if (labelEl)      labelEl.textContent      = stateCopy.label;
  if (subtextEl)    subtextEl.textContent    = stateCopy.subtext;

  if (resultSection) {
    resultSection.className = [
      'uc-calc__section',
      'uc-calc__result',
      result.colourClass,
    ].filter(Boolean).join(' ');
  }
}

function render(s) {
  const income = calculateUCIncome(s, UC_RATES);
  const costs  = calculateBasketCosts(s, COSTS);
  const result = getResultState(income, costs.total);

  updateVisibility(s);
  renderAdultRows(s);
  renderChildAges(s);
  updateBasketRows(s, costs);
  updateResultPanel(income, costs.total, result);
}

// ─── Sync DOM → State (used on reset) ────────────────────────────────────────

function syncDOMFromState(s) {
  const coupleEl = document.getElementById('inCouple');
  if (coupleEl) coupleEl.checked = s.inCouple;

  Object.keys(s.basket).forEach(key => {
    const el = document.querySelector(`[data-item="${key}"]`);
    if (el) el.checked = s.basket[key];
  });
}

// ─── Event handlers ───────────────────────────────────────────────────────────

function handleFormChange(e) {
  const { name, value, checked } = e.target;
  if (!name) return;

  if (name.startsWith('adultAge_')) {
    const i = parseInt(name.slice(9), 10);
    if (state.adults[i]) state.adults[i].age = value;
    return;
  }
  if (name.startsWith('adultWorking_')) {
    const i = parseInt(name.slice(13), 10);
    if (state.adults[i]) state.adults[i].working = checked;
    return;
  }
  if (name === 'inCouple') {
    state.inCouple = checked;
    return;
  }
  if (name.startsWith('childAge_')) {
    const i = parseInt(name.slice(9), 10);
    state.childAges[i] = value;
  }
}

function handleEarningsInput(e) {
  const { name } = e.target;
  if (!name || !name.startsWith('adultEarnings_')) return;
  const i = parseInt(name.slice(14), 10);
  if (state.adults[i]) state.adults[i].monthlyEarnings = parseFloat(e.target.value) || 0;
  render(state);
}

const debouncedEarnings = debounce(handleEarningsInput, 200);

function handleBasketChange(e) {
  const key = e.target.dataset.item;
  if (!key) return;
  state.basket[key] = e.target.checked;
  render(state);
}

function handleStepperClick(id) {
  if (id === 'adults-increase' && state.adults.length < MAX_ADULTS) {
    state.adults.push({ age: '25plus', working: false, monthlyEarnings: 0 });
  } else if (id === 'adults-decrease' && state.adults.length > 1) {
    state.adults.pop();
    if (state.adults.length < 2) state.inCouple = false;
  } else if (id === 'children-increase' && state.numChildren < MAX_CHILDREN) {
    state.numChildren++;
    state.childAges.push('5to15');
  } else if (id === 'children-decrease' && state.numChildren > 0) {
    state.numChildren--;
    state.childAges.pop();
  } else {
    return;
  }
  render(state);
}

function handleReset() {
  state = cloneDefaultState();
  syncDOMFromState(state);
  render(state);
}

function handleTooltipToggle(e) {
  const btn = e.target.closest('.uc-calc__info-btn');
  if (!btn) return;
  const panel = document.getElementById(btn.getAttribute('aria-controls'));
  if (!panel) return;
  panel.hidden = !panel.hidden;
  btn.setAttribute('aria-expanded', String(!panel.hidden));
}

// ─── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  const form      = document.getElementById('uc-calc-form');
  const basket    = document.getElementById('uc-calc-basket');
  const resetBtn  = document.getElementById('uc-calc-reset');
  const container = document.getElementById('uc-calc');

  if (!form) return;

  form.addEventListener('change', e => {
    if (e.target.name && e.target.name.startsWith('adultEarnings_')) return;
    handleFormChange(e);
    render(state);
  });

  form.addEventListener('input', e => {
    if (!e.target.name || !e.target.name.startsWith('adultEarnings_')) return;
    debouncedEarnings(e);
  });

  if (basket) basket.addEventListener('change', handleBasketChange);

  if (container) container.addEventListener('click', e => {
    const stepperIds = ['adults-increase', 'adults-decrease', 'children-increase', 'children-decrease'];
    if (stepperIds.includes(e.target.id)) {
      handleStepperClick(e.target.id);
      return;
    }
    handleTooltipToggle(e);
  });

  if (resetBtn) resetBtn.addEventListener('click', handleReset);

  render(state);
});
