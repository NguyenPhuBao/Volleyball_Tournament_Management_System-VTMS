<section
    class="organizer-profile-approvals"
    data-approvals-api="<?= e(url('/api/organizer/profile-change-requests')) ?>"
>
    <header class="profile-approvals-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Xác nhận thay đổi thông tin cá nhân</h1>
            <p class="sub">Dành cho BTC: xác nhận hoặc hủy yêu cầu cập nhật thông tin của ADMIN, Trọng tài, HLV.</p>
        </div>
    </header>

    <section class="profile-approvals-toolbar" aria-label="Bộ lọc yêu cầu xác nhận">
        <input id="q" type="text" placeholder="Tìm theo tên / username / bảng / trường" />

        <select id="roleFilter">
            <option value="">Tất cả vai trò</option>
            <option value="ADMIN">ADMIN</option>
            <option value="TRONG_TAI">TRỌNG TÀI</option>
            <option value="HUAN_LUYEN_VIEN">HUẤN LUYỆN VIÊN</option>
        </select>

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

    <section class="profile-approvals-stats" aria-label="Thống kê yêu cầu">
        <div class="stat"><span id="sPending">0</span><small>Chờ duyệt</small></div>
        <div class="stat"><span id="sApproved">0</span><small>Đã duyệt</small></div>
        <div class="stat"><span id="sRejected">0</span><small>Từ chối</small></div>
    </section>

    <div id="pageAlert" class="profile-approvals-alert hidden" role="status"></div>

    <section class="profile-approvals-panel">
        <div class="table-wrap">
            <table class="profile-approvals-table">
                <thead>
                    <tr>
                        <th>Mã YC</th>
                        <th>Người gửi</th>
                        <th>Vai trò</th>
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
                        <td colspan="10" class="empty">Đang tải danh sách yêu cầu...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="profile-approvals-modal hidden" id="detailModal" aria-hidden="true">
        <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="approvalDetailTitle">
            <div class="modal-head">
                <div>
                    <h2 id="approvalDetailTitle">Chi tiết yêu cầu xác nhận</h2>
                    <p class="sub" id="m_sub">-</p>
                </div>
                <button class="icon" id="m_close" type="button" aria-label="Đóng">x</button>
            </div>

            <div class="detail-grid">
                <div>
                    <label>Mã yêu cầu</label>
                    <input id="m_id" disabled />
                </div>
                <div>
                    <label>Trạng thái</label>
                    <input id="m_status" disabled />
                </div>

                <div>
                    <label>Người gửi</label>
                    <input id="m_sender" disabled />
                </div>
                <div>
                    <label>Vai trò</label>
                    <input id="m_role" disabled />
                </div>

                <div>
                    <label>Bảng liên quan</label>
                    <input id="m_table" disabled />
                </div>
                <div>
                    <label>Trường cập nhật</label>
                    <input id="m_field" disabled />
                </div>

                <div class="colspan">
                    <label>Giá trị cũ</label>
                    <textarea id="m_old" rows="3" disabled></textarea>
                </div>

                <div class="colspan">
                    <label>Giá trị mới</label>
                    <textarea id="m_new" rows="3" disabled></textarea>
                </div>

                <div class="colspan">
                    <label>Lý do của người gửi</label>
                    <textarea id="m_reason" rows="2" disabled></textarea>
                </div>

                <div>
                    <label>Ngày gửi</label>
                    <input id="m_sentAt" disabled />
                </div>
                <div>
                    <label>Ngày xử lý</label>
                    <input id="m_doneAt" disabled />
                </div>

                <div class="colspan">
                    <label>Ghi chú của BTC (bắt buộc khi hủy)</label>
                    <textarea id="m_note" rows="3" placeholder="Nhập ghi chú..."></textarea>
                </div>
            </div>

            <div id="m_alert" class="profile-approvals-alert is-error hidden" role="alert"></div>

            <div class="actions">
                <button id="btnReject" class="btn danger" type="button">Hủy (Từ chối)</button>
                <button id="btnApprove" class="btn primary" type="button">Xác nhận</button>
                <button id="btnClose" class="btn" type="button">Đóng</button>
            </div>
        </div>
    </div>
</section>
