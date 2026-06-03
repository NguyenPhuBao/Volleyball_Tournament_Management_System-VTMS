<section class="coach-page coach-team-schedule" data-teams-api="<?= e(url('/api/coach/teams')) ?>">
    <header class="coach-topbar">
        <div>
            <p class="eyebrow">HUẤN LUYỆN VIÊN</p>
            <h1>Lịch thi đấu đội bóng</h1>
            <p class="sub">Xem các trận đấu của đội theo giải, trạng thái và khoảng ngày.</p>
        </div>
    </header>

    <section class="coach-toolbar">
        <select id="teamSelect"></select>
        <input id="q" placeholder="Tìm theo giải / đội / sân" />
        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="CHUA_DIEN_RA">Chưa diễn ra</option>
            <option value="SAP_DIEN_RA">Sắp diễn ra</option>
            <option value="DANG_DIEN_RA">Đang diễn ra</option>
            <option value="TAM_DUNG">Tạm dừng</option>
            <option value="DA_KET_THUC">Đã kết thúc</option>
            <option value="DA_HUY">Đã hủy</option>
        </select>
        <input type="date" id="fromDate" />
        <input type="date" id="toDate" />
        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <div id="pageMessage" class="coach-message"></div>

    <section id="empty" class="coach-card empty hidden">Chưa có lịch thi đấu.</section>

    <div class="table-wrap">
        <table class="coach-table" id="scheduleTable">
            <thead>
                <tr>
                    <th>Ngày & giờ</th>
                    <th>Giải đấu</th>
                    <th>Đối thủ</th>
                    <th>Sân</th>
                    <th>Vòng</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody id="tbody"><tr><td colspan="6" class="empty">Đang tải dữ liệu...</td></tr></tbody>
        </table>
    </div>
</section>
