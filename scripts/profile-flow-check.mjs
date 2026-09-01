/**
 * Walks the Clinic Profile save flow as a real signed-in admin.
 *
 * The page can echo back what you typed without having stored it, so this
 * script only drives the browser — every assertion about what was SAVED is made
 * against the database afterwards, by the caller.
 */
import { chromium } from 'playwright-core';

const arg = (n, d = null) => {
    const i = process.argv.indexOf(`--${n}`);
    return i === -1 ? d : process.argv[i + 1];
};

const host = arg('host');
const [originHost, originPort] = arg('origin', '127.0.0.1:8123').split(':');
const base = `http://${host}:${originPort}`;

const browser = await chromium.launch({
    executablePath: '/usr/bin/google-chrome',
    args: ['--no-sandbox', `--host-resolver-rules=MAP ${host} ${originHost}`],
});
const page = await (await browser.newContext()).newPage();

const out = (s) => console.log(`  ${s}`);

try {
    await page.goto(`${base}/login`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', arg('email'));
    await page.fill('input[name="password"]', arg('password'));
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'load' }),
        page.click('button[type="submit"]:visible'),
    ]);

    await page.goto(`${base}/settings/profile`, { waitUntil: 'load' });

    // Is the prefix offered as editable, or locked? Reported, not assumed.
    const prefixReadonly = await page.$eval('input[name="id_prefix"]',
        (el) => el.hasAttribute('readonly'));
    out(`id_prefix field readonly: ${prefixReadonly}`);

    await page.fill('input[name="name"]', arg('name'));
    await page.fill('textarea[name="address"]', arg('address'));
    await page.fill('input[name="phone"]', arg('phone'));
    await page.fill('input[name="consultation_fee"]', arg('fee'));
    await page.selectOption('select[name="timezone"]', arg('timezone'));

    if (!prefixReadonly && arg('prefix')) {
        await page.fill('input[name="id_prefix"]', arg('prefix'));
    }

    const blocked = await page.$$eval('form :invalid',
        (els) => els.map((e) => `${e.name}: ${e.validationMessage}`));
    if (blocked.length) out(`! blocked by browser validation: ${blocked.join(' | ')}`);

    await Promise.all([
        page.waitForNavigation({ waitUntil: 'load' }),
        page.click('button[type="submit"]:visible'),
    ]);

    const flash = await page.evaluate(() => document.body.innerText.includes('Clinic profile updated'));
    out(`success toast shown: ${flash}`);

    const errors = await page.$$eval('[role="alert"]', (els) => els.map((e) => e.textContent.trim()));
    if (errors.length) out(`validation errors on page: ${errors.join(' | ')}`);

    out('FLOW COMPLETED');
} catch (e) {
    out(`FLOW FAILED: ${String(e).split('\n')[0]}`);
    process.exitCode = 1;
} finally {
    await browser.close();
}
