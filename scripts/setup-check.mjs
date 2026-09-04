/**
 * Step 7 verification: the first-run wizard.
 *
 * The things worth proving are that it appears for a FRESH clinic, does not
 * appear for an established one, does not re-ask what provisioning captured,
 * and never shows again once finished.
 */
import { chromium } from 'playwright-core';

const PORT = '8123';
const FRESH = process.env.FRESH_HOST ?? 'harbour.africhart-emr.test';
const FRESH_ADMIN = process.env.FRESH_ADMIN ?? 'owner@harbour.test';
const FRESH_NURSE = process.env.FRESH_NURSE ?? 'nurse@harbour.test';
const FRESH_NAME = process.env.FRESH_NAME ?? 'Harbour Clinic';

let failures = 0;
const check = (l, ok, d = '') => { if (!ok) failures++; console.log(`  ${ok ? 'ok  ' : 'FAIL'} ${l}${d ? ` — ${d}` : ''}`); };

const browser = await chromium.launch({
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', '--host-resolver-rules=MAP *.africhart-emr.test 127.0.0.1'],
});

const login = async (page, base, email) => {
    await page.goto(`${base}/login`, { waitUntil: 'load' });
    await page.fill('#email', email);
    await page.fill('#password', 'password123');
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('button[type="submit"]')]);
};

// ── a FRESH clinic is sent to the wizard ───────────────────────────────────
console.log(`\n=== ${FRESH_NAME} (fresh, provisioned minimally) ===`);
const base = `http://${FRESH}:${PORT}`;
let ctx = await browser.newContext();
let page = await ctx.newPage();
await login(page, base, FRESH_ADMIN);

check('admin is redirected into setup on sign-in', page.url().includes('/setup'), page.url());
check('CSS applied', (await page.evaluate(() => getComputedStyle(document.body).fontFamily)).includes('General Sans'));
check('Alpine initialised', await page.evaluate(() => typeof window.Alpine !== 'undefined'));

const step1 = await page.innerText('body');
check('greets by clinic name', step1.includes(FRESH_NAME), FRESH_NAME);
check('does NOT re-ask the clinic name', await page.$('input[name="name"]') === null);
check('does NOT re-ask the ID prefix', await page.$('input[name="id_prefix"]') === null);

// step 1 — profile gaps
await page.fill('#consultation_fee', '6500');
await page.fill('#address', '5 Sunrise Way, Port Harcourt');
await page.fill('#phone', '0803 555 1234');
await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('button[type="submit"]:has-text("Continue")')]);
check('step 1 advances to drug prices', page.url().includes('/setup/drug-prices'), page.url());

// step 2 — prices already seeded, so this is a review
const step2 = await page.innerText('body');
check('drug list is pre-seeded, not empty', !step2.includes('No medications yet'));
const priceInputs = await page.$$eval('input[name^="prices["]', els => els.length);
check('every seeded drug has a price field', priceInputs === 10, `${priceInputs} fields`);
await page.fill('input[name^="prices["]', '999');
await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('button[type="submit"]:has-text("Continue")')]);
check('step 2 advances to the team', page.url().includes('/setup/team'), page.url());

// step 3 — the first admin is NOT re-invited
const step3 = await page.innerText('body');
check('acknowledges the admin already exists', step3.includes('already have an administrator'));

await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('button[type="submit"]:has-text("Finish setup")')]);
check('finishing lands on the dashboard', page.url().includes('/dashboard'), page.url());

// ── and never again ────────────────────────────────────────────────────────
await page.goto(`${base}/dashboard`, { waitUntil: 'load' });
check('wizard does not reappear after completion', page.url().includes('/dashboard'), page.url());
await ctx.close();

ctx = await browser.newContext();
page = await ctx.newPage();
await login(page, base, FRESH_ADMIN);
check('nor on a fresh sign-in', page.url().includes('/dashboard'), page.url());
await ctx.close();

// ── a clinic that FINISHED setup is never prompted again, even with no
//    patients — proving the marker works on its own, not merely the patient
//    count standing in for it ────────────────────────────────────────────────
console.log('\n=== Sunrise (setup completed, still zero patients) ===');
ctx = await browser.newContext();
page = await ctx.newPage();
await login(page, `http://sunrise.africhart-emr.test:${PORT}`, 'owner@sunrise.test');
check('completed clinic is NOT sent to setup', !page.url().includes('/setup'), page.url());
await ctx.close();

// ── an ESTABLISHED clinic is never prompted ────────────────────────────────
console.log('\n=== Riverside (established, has patients) ===');
ctx = await browser.newContext();
page = await ctx.newPage();
await login(page, `http://riverside.africhart-emr.test:${PORT}`, 'owner@riverside.test');
check('established clinic is NOT sent to setup', !page.url().includes('/setup'), page.url());
await ctx.close();

// ── role gate ──────────────────────────────────────────────────────────────
console.log('\n=== role gate ===');
ctx = await browser.newContext();
page = await ctx.newPage();
await login(page, base, FRESH_NURSE);
const r = await page.goto(`${base}/setup`, { waitUntil: 'load' });
check('nurse is blocked from the wizard', r.status() === 403, String(r.status()));
await ctx.close();

await browser.close();
console.log(`\n${failures === 0 ? 'ALL CHECKS PASSED' : failures + ' CHECK(S) FAILED'}`);
process.exit(failures === 0 ? 0 : 1);
