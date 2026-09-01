/**
 * Step 3 verification: the clinic-identity fix, proven per-tenant.
 *
 * Two clinics, a real browser. Each must show ITS OWN name on the invoice
 * letterhead, in the <title>, and in the topbar — and must never show the
 * other's. A single-clinic check would pass on a hardcoded string.
 */
import { chromium } from 'playwright-core';

const CLINICS = [
    { host: 'riverside.africhart-emr.test', email: 'owner@riverside.test',
      name: 'Riverside Family Practice', invoice: 'RIVR-INV-20260901-0001',
      address: '12 Aba Road, Port Harcourt' },
    { host: 'grace.africhart-emr.test', email: 'admin@grace.test',
      name: 'Grace Medical Centre', invoice: 'GRAC-INV-20260901-0001',
      address: '7 Old GRA Close, Port Harcourt' },
];
const ORIGIN = '127.0.0.1:8123';
const PASSWORD = 'password123';

let failures = 0;
const check = (label, ok, detail = '') => {
    if (!ok) failures++;
    console.log(`  ${ok ? 'ok  ' : 'FAIL'} ${label}${detail ? ` — ${detail}` : ''}`);
};

const browser = await chromium.launch({
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', `--host-resolver-rules=MAP *.africhart-emr.test ${ORIGIN.split(':')[0]}`],
});

for (const clinic of CLINICS) {
    const other = CLINICS.find(c => c.host !== clinic.host);
    console.log(`\n=== ${clinic.name} (${clinic.host}) ===`);

    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    const base = `http://${clinic.host}:${ORIGIN.split(':')[1]}`;

    const bad = [];
    page.on('response', r => {
        const u = r.url();
        if (u.includes('/tenancy/assets/')) bad.push(`tenancy asset: ${u}`);
        if (/\.(css|js)(\?|$)/.test(u) && r.status() !== 200) bad.push(`${r.status()} ${u}`);
    });

    await page.goto(`${base}/login`, { waitUntil: 'load' });
    await page.fill('#email', clinic.email);
    await page.fill('#password', PASSWORD);
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('button[type="submit"]')]);

    // --- harness soundness: the checks from DEPLOYMENT.md §6 ---
    const font = await page.evaluate(() => getComputedStyle(document.body).fontFamily);
    check('CSS applied (font-family carries General Sans)', font.includes('General Sans'), font.slice(0, 40));
    check('Alpine initialised', await page.evaluate(() => typeof window.Alpine !== 'undefined'));
    check('no /tenancy/assets/ and all CSS/JS 200', bad.length === 0, bad.join('; '));

    // --- dashboard: topbar names THIS clinic ---
    const dashTitle = await page.title();
    const dashText = await page.innerText('body');
    check('dashboard <title> names this clinic', dashTitle.includes(clinic.name), dashTitle);
    check('dashboard <title> excludes "AfriChart EMR"', !dashTitle.includes('AfriChart EMR'), dashTitle);
    check('topbar shows this clinic', dashText.includes(clinic.name));
    check('dashboard shows nothing of the other clinic', !dashText.includes(other.name));

    // --- the invoice: the sharpest instance ---
    await page.goto(`${base}/invoices`, { waitUntil: 'load' });
    await page.click(`text=${clinic.invoice}`);
    await page.waitForLoadState('load');

    const invText = await page.innerText('body');
    const invTitle = await page.title();
    check('invoice letterhead names this clinic', invText.includes(clinic.name));
    check('invoice letterhead shows its address', invText.includes(clinic.address));
    check('invoice <title> names this clinic', invTitle.includes(clinic.name), invTitle);
    check('invoice shows nothing of the other clinic', !invText.includes(other.name));
    check('invoice no longer says "AfriChart EMR"', !invText.includes('AfriChart EMR'));

    // the print path is what made this the sharpest instance
    check('print button still present', await page.$('.no-print button, [onclick*="print"], button:has-text("Print")') !== null);

    await ctx.close();
}

await browser.close();
console.log(`\n${failures === 0 ? 'ALL CHECKS PASSED' : failures + ' CHECK(S) FAILED'}`);
process.exit(failures === 0 ? 0 : 1);
