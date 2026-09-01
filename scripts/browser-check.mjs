/**
 * The browser check DEPLOYMENT.md §6 requires.
 *
 * "A request returning 200 is not the same as the page working." This project
 * has shipped two defects that every status-code check passed — the A6 deploy
 * where asset() emitted /tenancy/assets/ and every clinic ran with no CSS and
 * no JavaScript, and a backup command reporting a tick while writing nothing.
 *
 * Usage:
 *   node scripts/browser-check.mjs --host riverside.africhart-emr.test \
 *        --origin 127.0.0.1:8123 --email owner@riverside.test --password ... \
 *        --path /settings/profile
 *
 * --host is the Host header a real visitor sends; --origin is where that name
 * is actually served right now. They are separate on purpose: mapping the name
 * at the resolver rather than editing /etc/hosts keeps the Host header and TLS
 * SNI exactly what production would see, and keeps the check working when local
 * DNS is not.
 */
import { chromium } from 'playwright-core';

const arg = (name, fallback = null) => {
    const i = process.argv.indexOf(`--${name}`);
    return i === -1 ? fallback : process.argv[i + 1];
};

const host = arg('host');
const origin = arg('origin', '127.0.0.1:8123');
const email = arg('email');
const password = arg('password');
const paths = (arg('path') ?? '/dashboard').split(',');
const expectText = arg('expect');
const forbidText = arg('forbid');
// --readonly name=true|false — assert a field's editability, which is a rule
// the page enforces rather than text it displays.
const readonlyAssertions = (arg('readonly') ?? '').split(',').filter(Boolean);

const [originHost, originPort] = origin.split(':');

const problems = [];
const note = (ok, label, detail = '') => {
    console.log(`  ${ok ? '✓' : '✗'} ${label}${detail ? ` — ${detail}` : ''}`);
    if (!ok) problems.push(label);
};

const browser = await chromium.launch({
    executablePath: '/usr/bin/google-chrome',
    args: [
        '--no-sandbox',
        // The Host header stays real; only name resolution is redirected.
        `--host-resolver-rules=MAP ${host} ${originHost}`,
    ],
});

const context = await browser.newContext({ ignoreHTTPSErrors: true });
const page = await context.newPage();

// Collected from the wire, never inferred from the document's own status.
const assets = [];
const tenancyAssetRequests = [];
const consoleErrors = [];

page.on('response', (r) => {
    const url = r.url();
    if (/\.(css|js)(\?|$)/.test(url)) assets.push({ url, status: r.status() });
    if (url.includes('/tenancy/assets/')) tenancyAssetRequests.push(url);
});
page.on('console', (m) => { if (m.type() === 'error') consoleErrors.push(m.text()); });
page.on('pageerror', (e) => consoleErrors.push(String(e)));

const base = `http://${host}:${originPort}`;

try {
    // ── Sign in ────────────────────────────────────────────────────────────
    // waitUntil 'load', never 'networkidle': the queue, consultation and
    // billing pages poll, so networkidle never settles on them.
    await page.goto(`${base}/login`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);

    const blocked = await page.$$eval('form :invalid', (els) =>
        els.map((e) => `${e.name}: ${e.validationMessage}`));
    if (blocked.length) console.log('  ! blocked by browser validation:', blocked.join(' | '));

    await Promise.all([
        page.waitForNavigation({ waitUntil: 'load' }),
        page.click('button[type="submit"]:visible'),
    ]);

    note(!page.url().includes('/login'), 'signed in', page.url().replace(base, ''));

    // ── Each page under test ───────────────────────────────────────────────
    for (const path of paths) {
        console.log(`\n  ── ${path}`);
        const response = await page.goto(`${base}${path}`, { waitUntil: 'load' });
        note(response.status() === 200, `${path} responded`, `HTTP ${response.status()}`);

        /*
         * 3 — CSS APPLIED, not merely fetched.
         *
         * NOT the body background. DEPLOYMENT.md names rgb(248,247,245), which
         * is --color-warm and correct for the guest and marketing layouts — but
         * the app layout is bg-page, i.e. #ffffff, which is ALSO the browser's
         * default. An assertion that passes when the stylesheet is missing is
         * not an assertion.
         *
         * font-family is unambiguous: General Sans can only come from our
         * stylesheet, and the fallback with no CSS is a serif.
         */
        const font = await page.evaluate(() => getComputedStyle(document.body).fontFamily);
        note(font.includes('General Sans'), 'CSS applied (computed font-family)', font.split(',')[0]);

        // 4 — the check that caught the A6 defect. Every interactive control
        // in this app is Alpine; without it the page looks fine and does nothing.
        const alpine = await page.evaluate(() => typeof window.Alpine !== 'undefined');
        note(alpine, 'Alpine is running');

        /*
         * Visible text AND form values. innerText excludes the value of an
         * <input>, so a settings screen whose whole job is to show the clinic's
         * name in an editable field would read as empty — which is exactly how
         * this check first failed, on a page that was working correctly.
         */
        const visible = async (needle) => page.evaluate((t) => {
            const text = document.body.innerText;
            const values = Array.from(document.querySelectorAll('input, textarea, select'))
                .map((el) => el.tagName === 'SELECT'
                    ? (el.selectedOptions[0]?.textContent ?? '')
                    : el.value)
                .join('\n');
            return (text + '\n' + values).includes(t);
        }, needle);

        if (expectText) note(await visible(expectText), `page shows "${expectText}"`);
        if (forbidText) note(!(await visible(forbidText)), `page does NOT show "${forbidText}"`);

        for (const assertion of readonlyAssertions) {
            const [field, expected] = assertion.split('=');
            const actual = await page.$eval(`[name="${field}"]`, (el) => el.readOnly || el.disabled)
                .catch(() => null);
            note(actual !== null && String(actual) === expected,
                `${field} readonly=${expected}`, actual === null ? 'field not found' : `is ${actual}`);
        }
    }

    // ── Asset-wide assertions ──────────────────────────────────────────────
    console.log('\n  ── assets');
    note(tenancyAssetRequests.length === 0, 'no /tenancy/assets/ requests',
        tenancyAssetRequests.length ? tenancyAssetRequests[0] : `${assets.length} css/js seen`);

    const broken = assets.filter((a) => a.status !== 200);
    note(broken.length === 0, 'every CSS/JS response is 200',
        broken.length ? broken.map((b) => `${b.status} ${b.url}`).join(', ') : `${assets.length} files`);

    note(consoleErrors.length === 0, 'no console errors',
        consoleErrors.slice(0, 2).join(' | '));
} catch (e) {
    note(false, 'harness error', String(e).split('\n')[0]);
} finally {
    await browser.close();
}

console.log(problems.length === 0
    ? '\nBROWSER CHECK PASSED'
    : `\nBROWSER CHECK FAILED (${problems.length}): ${problems.join(', ')}`);

process.exit(problems.length === 0 ? 0 : 1);
