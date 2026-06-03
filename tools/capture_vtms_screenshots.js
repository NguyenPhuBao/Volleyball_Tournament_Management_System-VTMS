const fs = require("fs");
const os = require("os");
const path = require("path");

const playwrightPath = path.join(os.tmpdir(), "vtms-playwright", "node_modules", "playwright");
const { chromium } = require(playwrightPath);

const BASE_URL = "http://localhost:8000";
const OUT_DIR = path.join(process.cwd(), "runtime", "vtms-guide-screens");

const accounts = {
  admin: { username: "admin_test", password: "123456" },
  organizer: { username: "btc_quocgia", password: "123456" },
  coach: { username: "hlv_quocgia_01", password: "123456" },
  athlete: { username: "vdv_quocgia_01", password: "123456" },
  referee: { username: "tt_quocgia_01", password: "123456" },
};

const shots = [
  { file: "01-trang-chu.png", title: "Trang chủ công khai", path: "/", role: null },
  { file: "02-dang-nhap.png", title: "Màn hình đăng nhập", path: "/login", role: null },
  { file: "03-admin-quan-ly-tai-khoan.png", title: "Admin - Quản lý tài khoản", path: "/admin/users", role: "admin" },
  { file: "04-admin-nhat-ky.png", title: "Admin - Nhật ký hệ thống", path: "/admin/logs", role: "admin" },
  { file: "05-btc-giai-dau.png", title: "Ban tổ chức - Quản lý giải đấu", path: "/ban-to-chuc/giai-dau", role: "organizer" },
  { file: "06-btc-lich-thi-dau.png", title: "Ban tổ chức - Lịch thi đấu", path: "/ban-to-chuc/lich-thi-dau", role: "organizer" },
  { file: "07-hlv-ho-so-doi.png", title: "Huấn luyện viên - Hồ sơ đội bóng", path: "/huan-luyen-vien/doi-bong", role: "coach" },
  { file: "08-vdv-loi-moi.png", title: "Vận động viên - Lời mời đội bóng", path: "/van-dong-vien/loi-moi-doi-bong", role: "athlete" },
  { file: "09-trong-tai-phan-cong.png", title: "Trọng tài - Lịch phân công", path: "/trong-tai/lich-phan-cong", role: "referee" },
];

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

async function capturePage(context, shot) {
  const page = await context.newPage();
  await page.setViewportSize({ width: 1366, height: 768 });
  await page.goto(`${BASE_URL}${shot.path}`, { waitUntil: "networkidle" });
  await page.waitForTimeout(1800);
  await page.screenshot({
    path: path.join(OUT_DIR, shot.file),
    fullPage: false,
  });
  await page.close();
}

(async () => {
  fs.mkdirSync(OUT_DIR, { recursive: true });
  const browser = await chromium.launch({ headless: true });

  const publicContext = await browser.newContext({ locale: "vi-VN" });
  for (const shot of shots.filter((item) => item.role === null)) {
    await capturePage(publicContext, shot);
    console.log(`${shot.file} - ${shot.title}`);
  }
  await publicContext.close();

  for (const role of ["admin", "organizer", "coach", "athlete", "referee"]) {
    const context = await browser.newContext({ locale: "vi-VN" });
    await login(context, role);
    for (const shot of shots.filter((item) => item.role === role)) {
      await capturePage(context, shot);
      console.log(`${shot.file} - ${shot.title}`);
    }
    await context.close();
  }

  await browser.close();
})();
