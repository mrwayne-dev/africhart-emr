/**
 * Step 6 verification: Branding.
 *
 * The two things worth proving are that the file physically lands in the
 * clinic's OWN tenant directory, and that one clinic's logo never appears for
 * another — so the check uploads two visually distinct images and compares the
 * bytes served back, not merely that some image rendered.
 */
import { chromium } from 'playwright-core';
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { deflateSync } from 'node:zlib';

const PORT = '8123';
/*
 * Fixtures are GENERATED, not read from a path someone has to prepare.
 *
 * This check previously loaded two PNGs from a scratch directory. When that
 * directory was cleared the check failed with ENOENT and looked exactly like a
 * regression in the app — which cost time to disprove. A verification script
 * that cannot run twice on a clean machine is not a verification script.
 */
const S = mkdtempSync(join(tmpdir(), 'branding-check-'));

const solidPng = (path, [r, g, b]) => {
    const crcTable = Array.from({ length: 256 }, (_, n) => {
        let c = n;
        for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
        return c >>> 0;
    });
    const crc = buf => {
        let c = 0xffffffff;
        for (const byte of buf) c = crcTable[(c ^ byte) & 0xff] ^ (c >>> 8);
        return (c ^ 0xffffffff) >>> 0;
    };
    const chunk = (type, data) => {
        const body = Buffer.concat([Buffer.from(type), data]);
        const len = Buffer.alloc(4); len.writeUInt32BE(data.length);
        const sum = Buffer.alloc(4); sum.writeUInt32BE(crc(body));
        return Buffer.concat([len, body, sum]);
    };
    const w = 64, h = 64;
    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(w, 0); ihdr.writeUInt32BE(h, 4);
    ihdr[8] = 8; ihdr[9] = 2;
    const row = Buffer.concat([Buffer.from([0]), Buffer.concat(Array(w).fill(Buffer.from([r, g, b])))]);
    const raw = Buffer.concat(Array(h).fill(row));
    writeFileSync(path, Buffer.concat([
        Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
        chunk('IHDR', ihdr), chunk('IDAT', deflateSync(raw)), chunk('IEND', Buffer.alloc(0)),
    ]));
    return path;
};
const CLINICS = [
    { host: 'riverside.africhart-emr.test', admin: 'owner@riverside.test', nurse: 'nurse@riverside.test',
      name: 'Riverside Family Practice', logo: solidPng(join(S, 'riverside.png'), [200, 30, 30]) },
    { host: 'grace.africhart-emr.test', admin: 'admin@grace.test', nurse: 'nurse@grace.test',
      name: 'Grace Medical Centre', logo: solidPng(join(S, 'grace.png'), [30, 90, 200]) },
];

let failures = 0;
const check = (l, ok, d = '') => { if (!ok) failures++; console.log(`  ${ok ? 'ok  ' : 'FAIL'} ${l}${d ? ` — ${d}` : ''}`); };
const sha = b => createHash('sha256').update(b).digest('hex').slice(0, 12);

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

const served = {};

for (const c of CLINICS) {
    const base = `http://${c.host}:${PORT}`;
    console.log(`\n=== ${c.name} ===`);

    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, base, c.admin);

    // no-logo state must be a real state, not a broken box
    let r = await page.goto(`${base}/settings/branding`, { waitUntil: 'load' });
    check('branding page reachable', r.status() === 200, String(r.status()));
    const emptyText = await page.innerText('body');
    check('no-logo state names the clinic instead', emptyText.includes('No logo yet') && emptyText.includes(c.name));

    // upload
    await page.setInputFiles('#logo', c.logo);
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('button[type="submit"]:has-text("Save logo")')]);
    check('upload accepted', (await page.innerText('body')).includes('Logo updated'));

    /*
     * Fetched INSIDE the page, not via page.request.
     *
     * APIRequestContext resolves DNS through Node, which knows nothing about
     * Chrome's --host-resolver-rules, so it fails with ENOTFOUND on a host that
     * the browser itself reaches perfectly well. Anything that must resolve the
     * mapped hostname has to run in page context.
     *
     * The BYTES are what matters here, not that an <img> tag exists: a broken
     * src still renders an element.
     */
    const fetched = await page.evaluate(async (url) => {
        const r = await fetch(url);
        const buf = new Uint8Array(await r.arrayBuffer());
        return { status: r.status, bytes: Array.from(buf) };
    }, `${base}/branding/logo`);

    check('logo route serves 200', fetched.status === 200, String(fetched.status));
    const body = Buffer.from(fetched.bytes);
    served[c.host] = sha(body);
    check('served bytes match the uploaded file', sha(body) === sha(readFileSync(c.logo)), `${sha(body)} vs ${sha(readFileSync(c.logo))}`);

    // and it appears on the invoice letterhead
    await page.goto(`${base}/invoices`, { waitUntil: 'load' });
    /* The invoice list navigates from an onclick on the ROW, not an anchor —
       so there is no a[href] to click. (Worth noting separately: a row that is
       only reachable by mouse is not keyboard-navigable.) */
    await page.click('tr[onclick]');
    await page.waitForLoadState('load');
    const imgs = await page.$$eval('img', els => els.map(e => e.getAttribute('src')));
    check('invoice letterhead shows the logo', imgs.some(s => s && s.includes('/branding/logo')), imgs.join(','));
    check('invoice still names the clinic beside it', (await page.innerText('body')).includes(c.name));

    await ctx.close();

    // wrong role
    const nc = await browser.newContext();
    const np = await nc.newPage();
    await login(np, base, c.nurse);
    const nr = await np.goto(`${base}/settings/branding`, { waitUntil: 'load' });
    check('nurse blocked from branding', nr.status() === 403, String(nr.status()));
    // but CAN read the logo, because it is on invoices they legitimately see
    const nlogo = await np.evaluate(async (url) => (await fetch(url)).status, `${base}/branding/logo`);
    check('nurse can still load the logo itself', nlogo === 200, String(nlogo));
    await nc.close();
}

const [a, b] = CLINICS.map(c => served[c.host]);
console.log('\n=== isolation ===');
check('the two clinics serve DIFFERENT logo bytes', a !== b, `${a} vs ${b}`);

await browser.close();
console.log(`\n${failures === 0 ? 'ALL CHECKS PASSED' : failures + ' CHECK(S) FAILED'}`);
process.exit(failures === 0 ? 0 : 1);
