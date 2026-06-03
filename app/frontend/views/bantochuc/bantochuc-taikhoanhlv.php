<section
    class="organizer-coach-accounts"
    data-accounts-api="<?= e(url('/api/organizer/coach-accounts')) ?>"
>
    <header class="accounts-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Duyệt tài khoản Huấn luyện viên</h1>
            <p class="sub">Quản lý và xét duyệt các tài khoản đăng ký với vai trò Huấn luyện viên.</p>
        </div>
    </header>

    <section class="accounts-toolbar" aria-label="Bộ lọc tài khoản">
        <input id="q" type="text" placeholder="Tìm theo username / email / tên" />

        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="CHO_DUYET">Chờ duyệt</option>
            <option value="HOAT_DONG">Hoạt động</option>
            <option value="DA_HUY">Đã hủy</option>
            <option value="CHUA_KICH_HOAT">Chưa kích hoạt</option>
            <option value="TAM_KHOA">Tạm khóa</option>
        </select>

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section class="accounts-stats" aria-label="Thống kê tài khoản">
        <div class="stat"><span id="sPending">0</span><small>Chờ duyệt</small></div>
        <div class="stat"><span id="sActive">0</span><small>Hoạt động</small></div>
        <div class="stat"><span id="sCanceled">0</span><small>Đã hủy</small></div>
    </section>

    <div class="accounts-table-wrap">
        <table class="accounts-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>SĐT</th>
                    <th>Trạng thái</th>
                    <th>Ngày đăng ký</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr>
                    <td colspan="8" class="empty">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="accounts-message" id="pageMessage" role="status"></p>
</section>

<div class="accounts-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
        <div class="modal-head">
            <div>
                <h2 id="m_title">Chi tiết tài khoản HLV</h2>
                <p class="sub" id="m_sub">-</p>
            </div>
            <button id="m_close" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="accounts-grid">
            <div>
                <label for="m_id">ID Tài khoản</label>
                <input id="m_id" disabled />
            </div>
            <div>
                <label for="m_status">Trạng thái tài khoản</label>
                <input id="m_status" disabled />
            </div>

            <div>
                <label for="m_username">Username</label>
                <input id="m_username" disabled />
            </div>
            <div>
                <label for="m_email">Email</label>
                <input id="m_email" disabled />
            </div>

            <div>
                <label for="m_phone">SĐT</label>
                <input id="m_phone" disabled />
            </div>
            <div>
                <label for="m_name">Họ tên</label>
                <input id="m_name" disabled />
            </div>

            <div>
                <label for="m_created">Ngày tạo</label>
                <input id="m_created" disabled />
            </div>
            <div>
                <label for="m_updated">Ngày cập nhật</label>
                <input id="m_updated" disabled />
            </div>
        </div>

        <div id="m_alert" class="accounts-alert hidden"></div>

        <div class="modal-actions">
            <button id="m_reject" class="btn danger" type="button">Từ chối</button>
            <button id="m_approve" class="btn primary" type="button">Duyệt tài khoản</button>
            <button id="m_closeBtn" class="btn" type="button">Đóng</button>
        </div>
    </div>
</div>
