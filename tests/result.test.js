'use strict';

const { getResultState, formatCurrency, COPY } = require('../src/result');

// formatCurrency

test('formatCurrency: rounds to 2dp', () => {
  expect(formatCurrency(98.054)).toBe('£98.05');
});

test('formatCurrency: handles exact pence', () => {
  expect(formatCurrency(21.41)).toBe('£21.41');
});

test('formatCurrency: zero', () => {
  expect(formatCurrency(0)).toBe('£0.00');
});

test('formatCurrency: thousands separator', () => {
  expect(formatCurrency(1153.846)).toBe('£1,153.85');
});

test('formatCurrency: round half up (0.005 → rounds up)', () => {
  expect(formatCurrency(10.005)).toBe('£10.01');
});

test('formatCurrency: negative input returns absolute value', () => {
  expect(formatCurrency(-21.41)).toBe('£21.41');
});

// getResultState — shortfall

test('shortfall: type and colour class set', () => {
  const result = getResultState(98.05, 119.46);
  expect(result.type).toBe('shortfall');
  expect(result.colourClass).toBe('uc-calc--shortfall');
});

test('shortfall: difference is absolute value', () => {
  const result = getResultState(98.05, 119.46);
  expect(result.difference).toBeGreaterThan(0);
  expect(result.difference).toBeCloseTo(21.41, 1);
});

// getResultState — exactly enough

test('exactly enough: equal rounded values', () => {
  expect(getResultState(100.00, 100.00).type).toBe('even');
});

test('exactly enough: triggered by rounded equality, not float equality', () => {
  expect(getResultState(100.004, 100.001).type).toBe('even');
});

// getResultState — surplus

test('surplus: type and no colour class', () => {
  const result = getResultState(200, 119.46);
  expect(result.type).toBe('surplus');
  expect(result.colourClass).toBe('');
});

test('difference is rounded, so near-equal totals count as even', () => {
  expect(getResultState(100.3, 50.1 + 50.2).type).toBe('even');
});

// getResultState — empty basket

test('empty basket: type is empty', () => {
  expect(getResultState(98.05, 0).type).toBe('empty');
});

test('empty basket: difference equals income', () => {
  expect(getResultState(98.05, 0).difference).toBe(98.05);
});

// COPY — English fallback

test('COPY exposes all four states', () => {
  expect(Object.keys(COPY).sort()).toEqual(['empty', 'even', 'shortfall', 'surplus']);
});

test('COPY shortfall mentions UC', () => {
  expect(COPY.shortfall.label).toBe('Shortfall');
  expect(COPY.shortfall.subtext).toMatch(/UC provides/);
});

test('COPY says results are before rent and council tax', () => {
  ['shortfall', 'even', 'surplus'].forEach(k => expect(COPY[k].subtext).toMatch(/before rent and council tax/));
});

test('COPY empty-basket guidance is specific', () => {
  expect(COPY.empty.subtext).toMatch(/haven’t selected any essentials/);
});
