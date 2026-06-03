<section
    class="coach-page coach-tournaments"
    data-tournaments-api="<?= e(url('/api/coach/tournaments')) ?>"
    data-registrations-api="<?= e(url('/api/coach/tournament-registrations')) ?>"
    data-teams-api="<?= e(url('/api/coach/teams')) ?>"
>
    <header class="coach-topbar">
        <div>
            <p class="eyebrow">HUẤN LUYỆN VIÊN</p>
            <h1>Danh sách giải đấu</h1>
            <p class="sub">Xem giải đang mở đăng ký và các giải đội của bạn đã gửi hồ sơ.</p>
        </div>
    </header>

    <section class="coach-toolbar">
        <input id="q" placeholder="Tìm theo tên giải" />
        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <div id="pageMessage" class="coach-message"></div>

    <div class="table-wrap">
        <table class="coach-table">
            <thead>
                <tr>
                    <th>Tên giải</th>
                    <th>Thời gian đăng ký</th>
                    <th>Thời gian thi đấu</th>
                    <th>Quy mô</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody"><tr><td colspan="6" class="empty">Đang tải dữ liệu...</td></tr></tbody>
        </table>
    </div>
</section>

<div class="coach-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h2 id="d_name">Chi tiết giải đấu</h2>
            <button class="icon" id="d_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="coach-grid">
            <div>
                <label for="d_registerTime">Thời gian đăng ký</label>
                <input id="d_registerTime" disabled />
            </div>
            <div>
                <label for="d_playTime">Thời gian thi đấu</label>
                <input id="d_playTime" disabled />
            </div>
            <div>
                <label for="d_team">Đội đăng ký *</label>
                <select id="d_team"></select>
                <div id="d_teamRegistration" class="team-registration-state hidden"></div>
            </div>
            <div>
                <label for="d_lineup">Đội hình đăng ký *</label>
                <select id="d_lineup"></select>
                <p class="hint">Có thể chọn bất kỳ đội hình đã chốt/cập nhật đúng giới tính giải, không bắt buộc là đội hình chính.</p>
            </div>
            <div>
                <label for="d_status">Trạng thái</label>
                <input id="d_status" disabled />
            </div>
            <div>
                <label for="d_gender">Giới tính giải đấu</label>
                <input id="d_gender" disabled />
            </div>
            <div class="colspan">
                <label>Mô tả giải đấu</label>
                <div id="d_desc" class="readonly-block"></div>
            </div>
            <div class="colspan">
                <label id="d_ruleTitle">Điều lệ giải đấu do Ban tổ chức công bố</label>
                <div id="d_ruleContent" class="readonly-block rule-block"></div>
            </div>
        </div>

        <div id="d_alert" class="coach-alert hidden"></div>

        <div class="coach-actions">
            <button class="btn" id="d_cancel" type="button">Đóng</button>
            <button class="btn primary" id="btnRegister" type="button">Đăng ký giải</button>
        </div>

        <p class="hint">Huấn luyện viên chỉ xem điều lệ giải đấu. Khi đăng ký, hệ thống sẽ tạo yêu cầu tham gia với trạng thái Chờ duyệt.</p>
    </div>
</div>
