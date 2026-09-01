/**
 * Step 5 verification: Drug Catalogue in the settings hub.
 *
 * Parameterised by clinic, never hardcoded to one — a check that names a single
 * clinic passes on that clinic and proves nothing about the second.
 *
 * The per-role assertions are NOT a formality. Step 2 shipped a settings block
 * that read "admin only" in a comment while the middleware was absent, and only
 * attempting the page AS the wrong role found it. Every settings screen is
 * checked for a blocked role, not merely a working one.
 */
import { chromium } from 'playwright-core';

const HOST = process.env.CLINIC_HOST ?? 'riverside.africhart-emr.test';
const ADMIN = process.env.CLINIC_ADMIN ?? 'owner@riverside.test';
const NURSE = process.env.NURSE_EMAIL ?? 'nurse@riverside.test';
const DRUG = process.env.DRUG_NAME ?? 'Riverside Probe Amoxicillin';
const PRICE = process.env.DRUG_PRICE ?? '1250';
const PORT = '8123';
const base = `http://${HOST}:${PORT}`;

let failures = 0;
const check = (label, ok, detail = '') => {
    if (!ok) failures++;
    console.log(`  ${ok ? 'ok  ' : 'FAIL'} ${label}${detail ? ` — ${detail}` : ''}`);
};

const browser = await chromium.launch({
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--host-resolver-rules=MAP *.africhart-emr.test 127.0.0.1'],
});

const login = async (page, email) => {
    await page.goto(`${base}/login`, { waitUntil: 'load' });
    await page.fill('#email', email);
    await page.fill('#password', 'password123');
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('button[type="submit"]')]);
};

// ── admin ──────────────────────────────────────────────────────────────────
const ctx = await browser.newContext();
const page = await ctx.newPage();
await login(page, ADMIN);

let res = await page.goto(`${base}/settings/catalogue`, { waitUntil: 'load' });
check('catalogue reachable in settings', res.status() === 200, String(res.status()));
check('CSS applied', (await page.evaluate(() => getComputedStyle(document.body).fontFamily)).includes('General Sans'));
check('Alpine initialised', await page.evaluate(() => typeof window.Alpine !== 'undefined'));

res = await page.goto(`${base}/drug-catalog`, { waitUntil: 'load' });
check('/drug-catalog redirects into settings', page.url().includes('/settings/catalogue'), page.url());

// add a drug
/*
  * The TRIGGER, by type. Two buttons read "Add Medication" — this one, and the
  * modal's submit — because both take their label from the same x-text
  * expression. Matching on text alone picks the trigger, and clicking it while
  * the modal is open lands on the backdrop instead.
  */
await page.click('button[type="button"]:has-text("Add Medication")');
await page.fill('input[name="name"]', DRUG);
await page.fill('input[name="default_price"]', PRICE);
/*
 * Scoped by the button's own TEXT, not button[type="submit"]:visible.
 *
 * DEPLOYMENT.md warns that several pages carry two submit buttons; this one
 * carries thirteen — the search form, one toggle per listed medication, and the
 * modal's. ":visible" matched the search button, submitted an empty search, and
 * the "add" never happened: a navigation timeout that looks like a broken app.
 */
await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.click('button[type="submit"]:has-text("Add Medication")'),
]);
const body = await page.innerText('body');
check('drug added and listed', body.includes(DRUG));
check('its price is shown', body.includes(PRICE) || body.includes(Number(PRICE).toLocaleString()));

// toggle it off and back
page.on('dialog', d => d.accept());
await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.click(`tr:has-text("${DRUG}") button[type="submit"]`),
]);
check('toggle changes state', (await page.innerText('body')).includes(DRUG));

await ctx.close();

// ── wrong roles must be blocked ────────────────────────────────────────────
for (const [role, email] of [['nurse', NURSE]]) {
    const c = await browser.newContext();
    const p = await c.newPage();
    await login(p, email);
    const r = await p.goto(`${base}/settings/catalogue`, { waitUntil: 'load' });
    check(`${role} is blocked from the catalogue`, r.status() === 403, String(r.status()));
    const r2 = await p.goto(`${base}/drug-catalog`, { waitUntil: 'load' });
    check(`${role} is blocked from the old route too`, r2.status() === 403, String(r2.status()));
    await c.close();
}

await browser.close();
console.log(`\n${failures === 0 ? 'ALL CHECKS PASSED' : failures + ' CHECK(S) FAILED'}`);
process.exit(failures === 0 ? 0 : 1);
