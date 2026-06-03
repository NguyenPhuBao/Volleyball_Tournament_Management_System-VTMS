<section
    class="admin-approvals"
    data-approvals-api="<?= e(url('/api/admin/organizer-change-requests')) ?>"
>
    <header class="approvals-topbar">
        <div>
            <p class="eyebrow">ADMIN</p>
            <h1>Xác nhận thay đổi thông tin Ban tổ chức</h1>
            <p class="sub">Duyệt hoặc từ chối các yêu cầu cập nhật hồ sơ của Ban tổ chức.</p>
        </div>
    </header>

    <section class="approvals-toolbar" aria-label="Bộ lọc yêu cầu xác nhận">
        <input id="q" type="text" placeholder="Tìm theo tên / đơn vị / trường cập nhật" />

        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="CHO_DUYET">Chờ duyệt</option>
            <option value="DA_DUYET">Đã duyệt</option>
            <option value="TU_CHOI">Từ chối</option>
        </select>

        <input type="date" id="fromDate" aria-label="Từ ngày" />
        <input type="date" id="toDate" aria-label="Đến ngày" />

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section class="approval-stats" aria-label="Thống kê yêu cầu xác nhận">
        <div class="stat"><span id="sPending">0</span><small>Chờ duyệt</small></div>
        <div class="stat"><span id="sApproved">0</span><small>Đã duyệt</small></div>
        <div class="stat"><span id="sRejected">0</span><small>Từ chối</small></div>
    </section>

    <div class="approvals-table-wrap">
        <table class="approvals-table">
            <thead>
                <tr>
                    <th>Mã YC</th>
                    <th>Người gửi</th>
                    <th>Đơn vị</th>
                    <th>Bảng</th>
                    <th>Trường</th>
                    <th>Giá trị cũ</th>
                    <th>Giá trị mới</th>
                    <th>Trạng thái</th>
                    <th>Ngày gửi</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr>
                    <td colspan="10" class="empty">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="approval-message" id="pageMessage" role="status"></p>
</section>

<div class="approval-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
        <div class="modal-head">
            <h2 id="detailTitle">Chi tiết yêu cầu</h2>
            <button id="btnClose" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="approval-grid">
            <div>
                <label for="m_id">Mã yêu cầu</label>
                <input id="m_id" disabled />
            </div>
            <div>
                <label for="m_status">Trạng thái</label>
                <input id="m_status" disabled />
            </div>
            <div>
                <label for="m_sender">Người gửi</label>
                <input id="m_sender" disabled />
            </div>
            <div>
                <label for="m_donvi">Đơn vị</label>
                <input id="m_donvi" disabled />
            </div>
        </div>

        <hr />

        <div class="approval-grid">
            <div>
                <label for="m_table">Bảng liên quan</label>
                <input id="m_table" disabled />
            </div>
            <div>
                <label for="m_field">Trường cập nhật</label>
                <input id="m_field" disabled />
            </div>
            <div class="colspan">
                <label for="m_old">Giá trị cũ</label>
                <textarea id="m_old" rows="3" disabled></textarea>
            </div>
            <div class="colspan">
                <label for="m_new">Giá trị mới</label>
                <textarea id="m_new" rows="3" disabled></textarea>
            </div>
            <div class="colspan">
                <label for="m_reason">Lý do/Ghi chú của người gửi</label>
                <textarea id="m_reason" rows="2" disabled></textarea>
            </div>
        </div>

        <div id="detailAlert" class="approval-alert hidden"></div>

        <div class="modal-actions">
            <button id="btnReject" class="btn danger" type="button">Từ chối</button>
            <button id="btnApprove" class="btn primary" type="button">Xác nhận</button>
        </div>
    </div>
</div>

<div class="approval-modal hidden" id="rejectModal" aria-hidden="true">
    <div class="modal-content small" role="dialog" aria-modal="true" aria-labelledby="rejectTitle">
        <div class="modal-head">
            <h2 id="rejectTitle">Từ chối yêu cầu</h2>
            <button id="btnRejectClose" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <label for="r_note">Lý do từ chối</label>
        <textarea id="r_note" rows="4" placeholder="Nhập lý do..."></textarea>

        <div class="modal-actions">
            <button id="btnRejectCancel" class="btn" type="button">Hủy</button>
            <button id="btnRejectConfirm" class="btn danger" type="button">Xác nhận từ chối</button>
        </div>
    </div>
</div>
