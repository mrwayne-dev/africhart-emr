/**
 * Step 4 verification: Team & Seats.
 *
 * The load-bearing check is the invite LINK. It is deliberately stored only as
 * a hash, so if the email is the only copy and mail fails or is stubbed, the
 * invitation is unrecoverable. This proves the admin always has a working path.
 */
import { chromium } from 'playwright-core';

const HOST = process.env.CLINIC_HOST ?? 'riverside.africhart-emr.test';
const EMAIL = process.env.CLINIC_ADMIN ?? 'owner@riverside.test';
// Parameterised, not hardcoded: the whole point is that a second clinic shows
// ITS name. A literal here made the check pass on clinic one and fail on two.
const CLINIC_NAME = process.env.CLINIC_NAME ?? 'Riverside Family Practice';
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

const login = async (page, email, password = 'password123') => {
    await page.goto(`${base}/login`, { waitUntil: 'load' });
    await page.fill('#email', email);
    await page.fill('#password', password);
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('button[type="submit"]')]);
};

// ── admin: invite, and get a usable link back ──────────────────────────────
const ctx = await browser.newContext();
const page = await ctx.newPage();
await login(page, EMAIL);

await page.goto(`${base}/settings/team`, { waitUntil: 'load' });
check('Team & Seats reachable from settings', page.url().includes('/settings/team'));
check('CSS applied', (await page.evaluate(() => getComputedStyle(document.body).fontFamily)).includes('General Sans'));
check('Alpine initialised', await page.evaluate(() => typeof window.Alpine !== 'undefined'));
check('/staff redirects here', await (async () => {
    await page.goto(`${base}/staff`, { waitUntil: 'load' });
    return page.url().includes('/settings/team');
})());

const invitee = `newnurse.${Date.now()}@riverside.test`;
await page.click('button:has-text("Invite")');
await page.fill('#email', invitee);
await page.fill('#name', 'New Nurse');
await page.selectOption('#role', 'nurse');
await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.click('button[type="submit"]:has-text("Create invitation")'),
]);

// input VALUE, not innerText — the DEPLOYMENT.md gotcha
const link = await page.$eval('input[readonly]', el => el.value).catch(() => null);
check('invitation link is shown on screen', !!link && link.includes('/invite/'), link ? link.slice(0, 58) + '…' : 'not found');
check('link is for THIS clinic', !!link && link.includes(HOST));
check('invitee appears in pending invitations', (await page.innerText('body')).includes(invitee));

// ── the link actually works, in a clean context (no admin session) ─────────
const guest = await browser.newContext();
const gp = await guest.newPage();
await gp.goto(link, { waitUntil: 'load' });
const inviteText = await gp.innerText('body');
check('link opens a working acceptance page', inviteText.includes('invited to join'), (await gp.title()));
check('acceptance page names the clinic', inviteText.includes(CLINIC_NAME), CLINIC_NAME);
check('role is stated, not chosen', inviteText.includes('Nurse') && (await gp.$('select[name="role"]')) === null);
await guest.close();

// ── deactivate / reactivate ────────────────────────────────────────────────
await page.goto(`${base}/settings/team`, { waitUntil: 'load' });
page.on('dialog', d => d.accept());
const before = await page.innerText('body');
if (before.includes('Deactivate')) {
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('button:has-text("Deactivate")')]);
    check('deactivate works', (await page.innerText('body')).includes('Deactivated'));
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('button:has-text("Reactivate")')]);
    check('reactivate works', (await page.innerText('body')).includes('Active'));
} else {
    check('deactivate control present', false, 'no other staff to act on');
}
await ctx.close();

// ── a non-admin must not reach it ──────────────────────────────────────────
const nurseCtx = await browser.newContext();
const np = await nurseCtx.newPage();
await login(np, process.env.NURSE_EMAIL ?? 'nurse@riverside.test');
await np.goto(`${base}/settings/team`, { waitUntil: 'load' });
const status = await np.evaluate(() => document.body.innerText);
check('non-admin is refused', !status.includes('Invite a team member'), np.url());
await nurseCtx.close();

await browser.close();
console.log(`\n${failures === 0 ? 'ALL CHECKS PASSED' : failures + ' CHECK(S) FAILED'}`);
process.exit(failures === 0 ? 0 : 1);
