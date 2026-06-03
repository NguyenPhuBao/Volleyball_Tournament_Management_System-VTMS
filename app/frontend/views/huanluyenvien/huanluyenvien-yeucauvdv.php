<section class="coach-page coach-requests"
    data-requests-api="<?= e(url('/api/coach/athlete-change-requests')) ?>"
    data-leaves-api="<?= e(url('/api/coach/athlete-leaves')) ?>">
    <header class="coach-topbar">
        <div>
            <p class="eyebrow">HUẤN LUYỆN VIÊN</p>
            <h1>Yêu cầu vận động viên</h1>
            <p class="sub">Duyệt thay đổi thông tin và đơn xin nghỉ phép từ VĐV thuộc đội bóng của HLV.</p>
        </div>
    </header>

    <h2>Yêu cầu xác nhận thay đổi thông tin</h2>
    <section class="coach-toolbar">
        <input id="q" placeholder="Tìm theo VĐV / bảng / trường / giá trị" />
        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="CHO_DUYET">Chờ duyệt</option>
            <option value="DA_DUYET">Đã duyệt</option>
            <option value="TU_CHOI">Từ chối</option>
        </select>
        <input type="date" id="fromDate" />
        <input type="date" id="toDate" />
        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section id="empty" class="coach-card empty hidden">Hiện tại không có yêu cầu.</section>

    <div id="pageMessage" class="coach-message"></div>

    <div class="table-wrap">
        <table class="coach-table" id="requestTable">
            <thead>
                <tr>
                    <th>Mã YC</th>
                    <th>Vận động viên</th>
                    <th>Bảng</th>
                    <th>Trường</th>
                    <th>Giá trị cũ</th>
                    <th>Giá trị mới</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody"><tr><td colspan="9" class="empty">Đang tải dữ liệu...</td></tr></tbody>
        </table>
    </div>

    <h2>Đơn xin nghỉ phép VĐV</h2>
    <p class="sub">HLV chỉ thấy và xử lý đơn của VĐV thuộc đội đang quản lý.</p>

    <section class="coach-toolbar">
        <input id="leaveQ" placeholder="Tìm theo VĐV / đội / trận / lý do" />
        <select id="leaveStatusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="CHO_DUYET">Chờ duyệt</option>
            <option value="DA_DUYET">Đã duyệt</option>
            <option value="TU_CHOI">Từ chối</option>
            <option value="DA_HUY">Đã hủy</option>
        </select>
        <input type="date" id="leaveFromDate" />
        <input type="date" id="leaveToDate" />
        <button id="btnLeaveRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section id="leaveEmpty" class="coach-card empty hidden">Hiện tại không có đơn xin nghỉ phép.</section>

    <div id="leaveMessage" class="coach-message"></div>

    <div class="table-wrap">
        <table class="coach-table" id="leaveTable">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Vận động viên</th>
                    <th>Đội</th>
                    <th>Trận / Giải</th>
                    <th>Từ ngày</th>
                    <th>Đến ngày</th>
                    <th>Lý do</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="leaveTbody"><tr><td colspan="9" class="empty">Đang tải dữ liệu...</td></tr></tbody>
        </table>
    </div>
</section>

<div class="coach-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h2>Chi tiết thay đổi thông tin</h2>
            <button class="icon" id="m_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="compare">
            <div>
                <h4>Thông tin hiện tại</h4>
                <pre id="oldInfo"></pre>
            </div>
            <div>
                <h4>Thông tin đề xuất</h4>
                <pre id="newInfo"></pre>
            </div>
        </div>

        <label for="m_note">Ghi chú khi từ chối</label>
        <textarea id="m_note" rows="3"></textarea>
        <div id="m_alert" class="coach-alert hidden"></div>

        <div class="coach-actions">
            <button class="btn" id="btnReject" type="button">Từ chối</button>
            <button class="btn primary" id="btnApprove" type="button">Duyệt</button>
        </div>
    </div>
</div>

<div class="coach-modal hidden" id="leaveDetailModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h2>Chi tiết đơn xin nghỉ phép</h2>
            <button class="icon" id="leave_m_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="compare">
            <div>
                <h4>Thông tin VĐV</h4>
                <pre id="leaveAthleteInfo"></pre>
            </div>
            <div>
                <h4>Thông tin nghỉ phép</h4>
                <pre id="leaveInfo"></pre>
            </div>
        </div>

        <label for="leave_m_note">Ghi chú khi từ chối</label>
        <textarea id="leave_m_note" rows="3"></textarea>
        <div id="leave_m_alert" class="coach-alert hidden"></div>

        <div class="coach-actions">
            <button class="btn" id="btnLeaveReject" type="button">Từ chối</button>
            <button class="btn primary" id="btnLeaveApprove" type="button">Duyệt nghỉ phép</button>
        </div>
    </div>
</div>
