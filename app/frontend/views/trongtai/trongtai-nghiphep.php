<section
    class="referee-leaves"
    data-leaves-api="<?= e(url('/api/trongtai/leaves')) ?>"
>
    <header class="leaves-topbar">
        <div>
            <p class="eyebrow">TRỌNG TÀI</p>
            <h1>Xin nghỉ phép</h1>
            <p class="sub">Theo dõi số ngày nghỉ, danh sách đơn, tạo đơn xin nghỉ và hủy đơn khi còn chờ duyệt.</p>
        </div>
        <button id="btnCreate" class="btn primary" type="button">+ Tạo đơn xin nghỉ phép</button>
    </header>

    <section class="leaves-stats" aria-label="Thống kê đơn xin nghỉ">
        <div class="stat"><span id="sTotal">0</span><small>Tổng đơn</small></div>
        <div class="stat"><span id="sPending">0</span><small>Chờ duyệt</small></div>
        <div class="stat"><span id="sApproved">0</span><small>Đã duyệt</small></div>
        <div class="stat"><span id="sDays">0</span><small>Ngày nghỉ đã duyệt</small></div>
    </section>

    <section class="leaves-toolbar" aria-label="Bộ lọc đơn xin nghỉ">
        <input id="q" placeholder="Tìm theo lý do" />

        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="CHO_DUYET">Chờ duyệt</option>
            <option value="DA_DUYET">Đã duyệt</option>
            <option value="TU_CHOI">Từ chối</option>
            <option value="DA_HUY">Đã hủy</option>
        </select>

        <input type="date" id="fromDate" />
        <input type="date" id="toDate" />

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <div id="pageAlert" class="leave-alert hidden" role="status"></div>

    <div class="table-wrap">
        <table class="leaves-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Từ ngày</th>
                    <th>Đến ngày</th>
                    <th>Số ngày</th>
                    <th>Lý do</th>
                    <th>Trạng thái</th>
                    <th>Ngày gửi</th>
                    <th>Ngày xử lý</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr>
                    <td colspan="9" class="empty">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div class="leave-modal hidden" id="createModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="m_title">
        <div class="modal-head">
            <div>
                <h2 id="m_title">Tạo đơn xin nghỉ phép</h2>
                <p class="sub">Nhập thời gian nghỉ và lý do. Hệ thống sẽ kiểm tra hợp lệ trước khi gửi.</p>
            </div>
            <button class="icon" id="m_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="leaves-grid">
            <div>
                <label for="m_from">Từ ngày</label>
                <input id="m_from" type="date" />
            </div>
            <div>
                <label for="m_to">Đến ngày</label>
                <input id="m_to" type="date" />
            </div>
            <div class="colspan">
                <label for="m_reason">Lý do</label>
                <textarea id="m_reason" rows="5" placeholder="Nhập lý do xin nghỉ..."></textarea>
            </div>
        </div>

        <div id="m_alert" class="leave-alert hidden"></div>

        <div class="modal-actions">
            <button id="m_cancel" class="btn" type="button">Hủy</button>
            <button id="m_submit" class="btn primary" type="button">Xin nghỉ phép</button>
        </div>

        <p class="hint">Theo CSDL VTMS: denngay >= tungay và trạng thái mặc định CHO_DUYET. Mọi thao tác gửi/hủy/xem đều được ghi nhật ký.</p>
    </div>
</div>

<div class="leave-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="d_title">
        <div class="modal-head">
            <h2 id="d_title">Chi tiết đơn xin nghỉ</h2>
            <button class="icon" id="d_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="leaves-grid">
            <div>
                <label for="d_id">Mã đơn</label>
                <input id="d_id" disabled />
            </div>
            <div>
                <label for="d_status">Trạng thái</label>
                <input id="d_status" disabled />
            </div>
            <div>
                <label for="d_from">Từ ngày</label>
                <input id="d_from" disabled />
            </div>
            <div>
                <label for="d_to">Đến ngày</label>
                <input id="d_to" disabled />
            </div>
            <div>
                <label for="d_days">Số ngày</label>
                <input id="d_days" disabled />
            </div>
            <div>
                <label for="d_sent">Ngày gửi</label>
                <input id="d_sent" disabled />
            </div>
            <div class="colspan">
                <label for="d_reason">Lý do</label>
                <textarea id="d_reason" rows="4" disabled></textarea>
            </div>
            <div>
                <label for="d_done">Ngày xử lý</label>
                <input id="d_done" disabled />
            </div>
        </div>

        <div id="d_alert" class="leave-alert hidden"></div>

        <div class="modal-actions">
            <button class="btn" id="d_closeBtn" type="button">Đóng</button>
        </div>
    </div>
</div>
