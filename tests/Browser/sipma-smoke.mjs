import { chromium, expect } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
const base = process.env.SIPMA_BROWSER_URL || 'http://127.0.0.1:8002';
const browser = await chromium.launch({ executablePath: process.env.SIPMA_BROWSER_EXECUTABLE || 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', headless: true });
const dir = 'docs/screenshots';
await mkdir(dir, { recursive: true });
const issues = [];
const results = [];
async function snapshot(page, name) {
  await page.evaluate(() => document.fonts.ready);
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: dir + '/' + name + '.png', fullPage: true });
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 2);
  if (overflow) issues.push(name + ': horizontal page overflow');
  results.push(name);
}
async function login(page, email) {
  await page.goto(base + '/login');
  await page.locator('#email').fill(email);
  await page.locator('#password').fill('Sipma123!');
  await page.getByRole('button', { name: 'Masuk ke SIPMA' }).click();
  await page.waitForURL(url => !url.pathname.endsWith('/login'));
}
try {
  const resizeContext = await browser.newContext();
  const resizePage = await resizeContext.newPage();
  await resizePage.goto(base + '/login');
  for (const viewport of [{width:1920,height:1080},{width:1440,height:900},{width:1366,height:768},{width:1280,height:720},{width:390,height:844},{width:375,height:667}]) {
    await resizePage.setViewportSize(viewport);
    await resizePage.evaluate(() => document.fonts.ready);
    const dimensions = await resizePage.evaluate(() => ({height:document.documentElement.scrollHeight,width:document.documentElement.scrollWidth}));
    if(dimensions.height > viewport.height + 2 || dimensions.width > viewport.width + 2) issues.push('Login needs scrolling at ' + viewport.width + 'x' + viewport.height + ': ' + JSON.stringify(dimensions));
  }
  await resizeContext.close();
  for (const [size, viewport] of [['desktop',{width:1440,height:1000}],['mobile',{width:390,height:844}]]) {
    const context = await browser.newContext({ viewport, locale: 'id-ID' });
    const page = await context.newPage();
    page.on('pageerror', error => issues.push(size + ': ' + error.message));
    page.on('response', response => { if (response.status() >= 500 && response.url().startsWith(base)) issues.push(size + ': HTTP ' + response.status() + ' ' + response.url()); });
    await page.goto(base + '/login');
    await expect(page.getByRole('heading', {name:'Selamat datang di SIPMA'})).toBeVisible();
    await expect(page.locator('#email')).toBeFocused();
    await snapshot(page, 'login-' + size);
    await page.locator('#email').fill('salah@sipma.test');
    await page.locator('#password').fill('invalid-password');
    await page.getByRole('button',{name:'Tampilkan kata sandi',exact:true}).click();
    await expect(page.locator('#password')).toHaveAttribute('type','text');
    await page.getByRole('button',{name:'Sembunyikan kata sandi',exact:true}).click();
    await page.getByRole('button',{name:'Masuk ke SIPMA'}).click();
    await expect(page.getByRole('alert')).toBeVisible();
    await snapshot(page, 'login-error-' + size);
    await login(page, 'mudabbir1@sipma.test');
    await expect(page.getByRole('heading',{name:'Beranda Pembinaan'})).toBeVisible();
    await snapshot(page, 'dashboard-' + size);
    await page.goto(base + '/admin/operational-sessions');
    await expect(page.getByRole('heading',{name:'Buka sesi kegiatan',exact:true})).toBeVisible();
    const firstSession = page.locator('.sipma-session-list button').first();
    await firstSession.click();
    await expect(page.getByRole('button',{name:'Simpan seluruh absensi'})).toBeVisible();
    await snapshot(page, 'absensi-' + size);
    await page.goto(base + '/admin/weekly-reports');
    await snapshot(page, 'laporan-empty-' + size);
    await context.close();
    const portalContext = await browser.newContext({ viewport, locale:'id-ID' });
    const portal = await portalContext.newPage();
    await login(portal, 'mahasantri1@sipma.test');
    await snapshot(portal, 'portal-' + size);
    if(size === 'mobile') {
      await expect(portal.getByRole('link',{name:'Izin & sakit',exact:true})).toBeHidden();
      await portal.getByRole('button',{name:'Menu',exact:true}).click();
      await expect(portal.getByRole('link',{name:'Izin & sakit',exact:true})).toBeVisible();
      await portal.getByRole('button',{name:'Menu',exact:true}).click();
      await expect(portal.getByRole('link',{name:'Izin & sakit',exact:true})).toBeHidden();
    }
    await portal.goto(base + '/portal/pengajuan');
    await snapshot(portal, 'pengajuan-' + size);
    await portalContext.close();
    const parentContext = await browser.newContext({viewport});
    const parent = await parentContext.newPage();
    await login(parent, 'orangtua1@sipma.test');
    await snapshot(parent, 'orang-tua-' + size);
    await parentContext.close();
  }
  await writeFile(dir + '/checks.json', JSON.stringify({ base, pages:results, issues },null,2));
  if(issues.length) throw new Error(issues.join('\n'));
  console.log('Browser checks passed: ' + results.length + ' screenshots, desktop/mobile, login error, password toggle, scoped dashboards and portal.');
} finally { await browser.close(); }
