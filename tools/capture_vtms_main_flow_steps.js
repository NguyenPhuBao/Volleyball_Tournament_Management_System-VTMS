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

async function openPage(context, route) {
  const page = await context.newPage();
  await page.setViewportSize({ width: 1366, height: 768 });
  const response = await page.goto(`${BASE_URL}${route}`, { waitUntil: "networkidle" });
  await page.waitForTimeout(1400);
  if (response && response.status() >= 400) {
    throw new Error(`${route} returned HTTP ${response.status()}`);
  }
  return page;
}

async function shot(page, file) {
  await page.screenshot({
    path: path.join(OUT_DIR, file),
    fullPage: false,
  });
  console.log(file);
}

async function visibleButton(page, text) {
  return page.locator("button:visible").filter({ hasText: text }).first();
}

async function clickVisibleButton(page, text, waitSelector = null) {
  await (await visibleButton(page, text)).click({ timeout: 8000 });
  if (waitSelector) {
    await page.waitForSelector(waitSelector, { state: "visible", timeout: 8000 });
  }
  await page.waitForTimeout(900);
}

async function scrollTo(page, selector) {
  await page.locator(selector).first().scrollIntoViewIfNeeded({ timeout: 5000 }).catch(() => {});
  await page.waitForTimeout(700);
}

async function captureCreateTournamentFlow(context) {
  const page = await openPage(context, "/ban-to-chuc/giai-dau");
  await shot(page, "30-main-tao-giai-01-danh-sach-giai-dau.png");

  await clickVisibleButton(page, "Tạo giải đấu", "#m_name");
  await page.fill("#m_name", "Giải minh họa từng bước VTMS").catch(() => {});
  await page.fill("#m_start", "2026-07-20T08:00").catch(() => {});
  await page.fill("#m_end", "2026-07-30T18:00").catch(() => {});
  await shot(page, "31-main-tao-giai-02-form-thong-tin-co-ban.png");

  await scrollTo(page, "#m_place_note");
  await page.fill("#m_place_note", "Nhà thi đấu minh họa").catch(() => {});
  await page.fill("#m_desc", "Mô tả ngắn cho giải đấu dùng trong tài liệu hướng dẫn.").catch(() => {});
  await shot(page, "32-main-tao-giai-03-dia-diem-mo-ta.png");

  await scrollTo(page, "#m_min_teams");
  await page.fill("#m_min_teams", "2").catch(() => {});
  await page.fill("#m_max_teams", "8").catch(() => {});
  await page.fill("#m_min_players", "6").catch(() => {});
  await page.fill("#m_max_players", "12").catch(() => {});
  await shot(page, "33-main-tao-giai-04-dieu-kien-tham-gia.png");

  await scrollTo(page, "#m_format_type");
  await shot(page, "34-main-tao-giai-05-the-thuc-va-nut-luu.png");
  await page.locator("#m_close, #m_cancel").first().click({ timeout: 3000 }).catch(async () => {
    await page.keyboard.press("Escape").catch(() => {});
  });
  await page.waitForTimeout(700);

  const row = page.locator("tr", { hasText: "TC Giải đầy đủ 20260601120808" }).first();
  if (await row.count()) {
    await row.locator("button:visible").filter({ hasText: "Đăng ký" }).first().click({ timeout: 5000 }).catch(async () => {
      await clickVisibleButton(page, "Đăng ký", "#r_status");
    });
  } else {
    await clickVisibleButton(page, "Đăng ký", "#r_status");
  }
  await page.waitForSelector("#r_status", { state: "visible", timeout: 8000 }).catch(() => {});
  await page.waitForTimeout(1800);
  await shot(page, "35-main-tao-giai-06-quan-ly-dang-ky-doi.png");
  await page.close();
}

async function captureCoachTournamentFlow(context) {
  const page = await openPage(context, "/huan-luyen-vien/giai-dau");
  await shot(page, "36-main-tao-giai-07-hlv-dang-ky-giai.png");
  await page.close();
}

async function captureCreateMatchFlow(context) {
  const page = await openPage(context, "/ban-to-chuc/lich-thi-dau");
  await page.getByText("TC Giải đầy đủ 20260601120808").first().click({ timeout: 5000 }).catch(() => {});
  await page.waitForTimeout(1000);
  await shot(page, "40-main-tao-tran-01-man-hinh-lich-thi-dau.png");

  await clickVisibleButton(page, "Thêm bảng đấu", "#gm_name");
  await page.fill("#gm_name", "Bảng hướng dẫn").catch(() => {});
  await shot(page, "41-main-tao-tran-02-form-tao-bang-dau.png");
  await page.locator("#gm_close, #gm_cancel").first().click({ timeout: 3000 }).catch(async () => {
    await page.evaluate(() => {
      const modal = document.getElementById("groupModal");
      if (modal) {
        modal.classList.add("hidden");
        modal.setAttribute("aria-hidden", "true");
      }
    }).catch(() => {});
  });
  await page.waitForTimeout(700);

  await clickVisibleButton(page, "Thêm trận đấu", "#mm_team1");
  await shot(page, "42-main-tao-tran-03-form-chon-doi-san.png");
  await scrollTo(page, "#mm_start");
  await shot(page, "43-main-tao-tran-04-thoi-gian-trang-thai.png");
  await page.locator("#mm_add_referee").click({ timeout: 5000 }).catch(() => {});
  await page.waitForTimeout(800);
  await shot(page, "44-main-tao-tran-05-phan-cong-trong-tai.png");
  await page.locator("#mm_close, #mm_cancel").first().click({ timeout: 3000 }).catch(async () => {
    await page.evaluate(() => {
      const modal = document.getElementById("matchModal");
      if (modal) {
        modal.classList.add("hidden");
        modal.setAttribute("aria-hidden", "true");
      }
    }).catch(() => {});
  });
  await page.waitForTimeout(700);
  await scrollTo(page, "text=Đội testcase");
  await shot(page, "45-main-tao-tran-06-danh-sach-tran-da-tao.png");
  await page.close();
}

async function captureRefereeSupervisionFlow(context) {
  const assignments = await openPage(context, "/trong-tai/lich-phan-cong");
  await shot(assignments, "50-main-giam-sat-01-lich-phan-cong.png");

  const detailButton = assignments.locator("button:visible").filter({ hasText: "Chi tiết trận" }).first();
  if (await detailButton.count()) {
    await detailButton.click({ timeout: 5000 });
    await assignments.waitForSelector("#matchDetailModal:not(.hidden), #md_matchId", { timeout: 8000 }).catch(() => {});
    await assignments.waitForTimeout(1000);
    await shot(assignments, "51-main-giam-sat-02-chi-tiet-tran-phan-cong.png");
  }
  await assignments.close();

  const supervise = await openPage(context, "/trong-tai/giam-sat?matchId=1&assignmentId=1");
  await shot(supervise, "52-main-giam-sat-03-man-hinh-giam-sat.png");
  const join = supervise.locator("button:visible").filter({ hasText: "Xác nhận tham gia" }).first();
  if (await join.count()) {
    const disabled = await join.isDisabled().catch(() => true);
    if (!disabled) {
      await join.click({ timeout: 5000 });
      await supervise.waitForTimeout(1200);
    }
  }
  await supervise.locator("button:visible").filter({ hasText: "Chọn trọng tài tham gia" }).first().click({ timeout: 5000 }).catch(() => {});
  await supervise.waitForTimeout(900);
  await shot(supervise, "53-main-giam-sat-04-xac-nhan-to-trong-tai.png");
  await supervise.close();

  const incident = await openPage(context, "/trong-tai/bao-cao-su-co");
  await shot(incident, "54-main-giam-sat-05-bao-cao-su-co.png");
  await clickVisibleButton(incident, "Tạo báo cáo", "#m_title").catch(() => {});
  await incident.fill("#m_title", "Sự cố minh họa").catch(() => {});
  await incident.fill("#m_content", "Nội dung sự cố minh họa cho tài liệu hướng dẫn.").catch(() => {});
  await shot(incident, "55-main-giam-sat-06-form-tao-bao-cao-su-co.png");
  await incident.close();
}

async function captureNominationFlow(context) {
  const page = await openPage(context, "/ban-to-chuc/tu-cach-cap-tren");
  await shot(page, "60-main-de-cu-01-tu-cach-cap-tren.png");
  await scrollTo(page, "#candidateBody");
  await shot(page, "61-main-de-cu-02-danh-sach-doi-co-the-de-cu.png");
  await scrollTo(page, "#incomingBody");
  await shot(page, "62-main-de-cu-03-de-cu-gui-den-va-duyet.png");
  await page.close();

  const teams = await openPage(context, "/ban-to-chuc/doi-bong");
  await shot(teams, "63-main-de-cu-04-doi-bong-doi-chieu-ho-so.png");
  await teams.close();
}

(async () => {
  fs.mkdirSync(OUT_DIR, { recursive: true });
  const browser = await chromium.launch({ headless: true });

  const organizer = await browser.newContext({ locale: "vi-VN" });
  await login(organizer, "organizer");
  await captureCreateTournamentFlow(organizer);
  await captureCreateMatchFlow(organizer);
  await captureNominationFlow(organizer);
  await organizer.close();

  const coach = await browser.newContext({ locale: "vi-VN" });
  await login(coach, "coach");
  await captureCoachTournamentFlow(coach);
  await coach.close();

  const referee = await browser.newContext({ locale: "vi-VN" });
  await login(referee, "referee");
  await captureRefereeSupervisionFlow(referee);
  await referee.close();

  await browser.close();
})();
