const fs = require("fs");
const os = require("os");
const path = require("path");

const playwrightPath = path.join(os.tmpdir(), "vtms-playwright", "node_modules", "playwright");
const { chromium } = require(playwrightPath);

const BASE_URL = "http://localhost:8000";
const OUT_DIR = path.join(process.cwd(), "runtime", "vtms-guide-screens");

const accounts = {
  organizer: { username: "btc_quocgia", password: "123456" },
  coach: { username: "hlv_quocgia_01", password: "123456" },
  athlete: { username: "vdv_quocgia_01", password: "123456" },
  referee: { username: "tt_quocgia_01", password: "123456" },
};

async function login(context, role) {
  const account = accounts[role];
  const page = await context.newPage();
  await page.goto(`${BASE_URL}/login`, { waitUntil: "networkidle" });
  await page.fill("#identifier", account.username);
  await page.fill("#password", account.password);
  await Promise.all([
    page.waitForLoadState("networkidle").catch(() => {}),
    page.click("#submitBtn"),
  ]);
  await page.waitForTimeout(1200);
  await page.close();
}

async function openPage(context, urlPath) {
  const page = await context.newPage();
  await page.setViewportSize({ width: 1366, height: 768 });
  const response = await page.goto(`${BASE_URL}${urlPath}`, { waitUntil: "networkidle" });
  await page.waitForTimeout(1400);
  if (response && response.status() >= 400) {
    throw new Error(`${urlPath} returned HTTP ${response.status()}`);
  }
  return page;
}

async function clickButton(page, label, waitSelector) {
  const button = page.locator("button:visible").filter({ hasText: label }).first();
  await button.click({ timeout: 7000 });
  if (waitSelector) {
    await page.waitForSelector(waitSelector, { state: "visible", timeout: 8000 });
  }
  await page.waitForTimeout(900);
}

async function screenshot(page, file) {
  await page.screenshot({
    path: path.join(OUT_DIR, file),
    fullPage: false,
  });
  console.log(file);
}

async function captureStatic(context, route, file) {
  const page = await openPage(context, route);
  await screenshot(page, file);
  await page.close();
}

async function captureModal(context, route, buttonText, waitSelector, file, afterOpen) {
  const page = await openPage(context, route);
  await clickButton(page, buttonText, waitSelector);
  if (afterOpen) {
    await afterOpen(page);
  }
  await screenshot(page, file);
  await page.close();
}

(async () => {
  fs.mkdirSync(OUT_DIR, { recursive: true });
  const browser = await chromium.launch({ headless: true });

  const organizer = await browser.newContext({ locale: "vi-VN" });
  await login(organizer, "organizer");
  await captureModal(
    organizer,
    "/ban-to-chuc/giai-dau",
    "Tạo giải đấu",
    "#m_name",
    "10-btc-tao-giai-modal.png",
    async (page) => {
      await page.fill("#m_name", "Giải minh họa quy trình VTMS").catch(() => {});
      await page.selectOption("#m_level", { index: 1 }).catch(() => {});
    }
  );
  await captureModal(
    organizer,
    "/ban-to-chuc/giai-dau",
    "Đăng ký",
    "#r_status",
    "11-btc-duyet-dang-ky-modal.png"
  );
  await captureStatic(organizer, "/ban-to-chuc/doi-bong", "12-btc-duyet-doi-bong.png");
  await captureStatic(organizer, "/ban-to-chuc/tu-cach-cap-tren", "13-btc-de-cu-duyet-doi.png");
  await captureModal(
    organizer,
    "/ban-to-chuc/lich-thi-dau",
    "Thêm bảng đấu",
    "#gm_name",
    "14-btc-tao-bang-dau-modal.png",
    async (page) => {
      await page.fill("#gm_name", "Bảng minh họa").catch(() => {});
    }
  );
  await captureModal(
    organizer,
    "/ban-to-chuc/lich-thi-dau",
    "Thêm trận đấu",
    "#mm_team1",
    "15-btc-tao-tran-dau-modal.png"
  );
  await captureModal(
    organizer,
    "/ban-to-chuc/lich-thi-dau",
    "Thêm trận đấu",
    "#mm_team1",
    "16-btc-phan-cong-trong-tai-modal.png",
    async (page) => {
      await page.locator("button").filter({ hasText: "+ Thêm trọng tài" }).first().click({ timeout: 3000 }).catch(() => {});
      await page.waitForTimeout(700);
    }
  );
  await organizer.close();

  const coach = await browser.newContext({ locale: "vi-VN" });
  await login(coach, "coach");
  await captureStatic(coach, "/huan-luyen-vien/giai-dau", "17-hlv-dang-ky-giai.png");
  await captureStatic(coach, "/huan-luyen-vien/thanh-vien", "18-hlv-thanh-vien-doi.png");
  await captureStatic(coach, "/huan-luyen-vien/doi-hinh", "19-hlv-doi-hinh.png");
  await coach.close();

  const athlete = await browser.newContext({ locale: "vi-VN" });
  await login(athlete, "athlete");
  await captureStatic(athlete, "/van-dong-vien/doi-hinh", "20-vdv-doi-hinh.png");
  await captureStatic(athlete, "/van-dong-vien/nghi-phep-thi-dau", "21-vdv-nghi-phep-thi-dau.png");
  await athlete.close();

  const referee = await browser.newContext({ locale: "vi-VN" });
  await login(referee, "referee");
  await captureStatic(referee, "/trong-tai/giam-sat", "22-trong-tai-giam-sat.png");
  await captureStatic(referee, "/trong-tai/bao-cao-su-co", "23-trong-tai-bao-cao-su-co.png");
  await captureStatic(referee, "/trong-tai/xin-nghi-phep", "24-trong-tai-xin-nghi-phep.png");
  await referee.close();

  await browser.close();
})();
