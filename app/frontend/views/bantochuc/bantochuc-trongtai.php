<section
    class="organizer-referees"
    data-referees-api="<?= e(url('/api/organizer/referees')) ?>"
    data-tournaments-api="<?= e(url('/api/organizer/tournaments')) ?>"
    data-matches-api="<?= e(url('/api/organizer/referee-matches')) ?>"
    data-leaves-api="<?= e(url('/api/organizer/referee-leaves')) ?>"
>
    <header class="refs-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Quản lý Trọng tài</h1>
            <p class="sub">Thêm trọng tài, phân công trận đấu và tạo đơn nghỉ trọng tài.</p>
        </div>
        <button id="btnAddRef" class="btn primary" type="button">Thêm trọng tài</button>
    </header>

    <nav class="refs-tabs" aria-label="Chức năng quản lý trọng tài">
        <button class="tab active" data-tab="tab-referees" type="button">Danh sách trọng tài</button>
        <button class="tab" data-tab="tab-assign" type="button">Phân công trọng tài</button>
        <button class="tab" data-tab="tab-leave" type="button">Cho nghỉ trọng tài</button>
    </nav>

    <section id="tab-referees" class="refs-panel">
        <div class="refs-toolbar">
            <input id="r_q" placeholder="Tìm theo tên / username / cấp bậc" />
            <select id="r_status">
                <option value="">Tất cả trạng thái</option>
                <option value="HOAT_DONG">Hoạt động</option>
                <option value="CHO_DUYET">Chờ duyệt</option>
                <option value="DANG_NGHI">Đang nghỉ</option>
                <option value="NGUNG_HOAT_DONG">Ngưng hoạt động</option>
            </select>
            <button id="r_refresh" class="btn" type="button">Làm mới</button>
        </div>

        <div class="refs-table-wrap">
            <table class="refs-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Username</th>
                        <th>Cấp bậc</th>
                        <th>Kinh nghiệm</th>
                        <th>Trạng thái</th>
                        <th>Tài khoản</th>
                    </tr>
                </thead>
                <tbody id="r_tbody">
                    <tr>
                        <td colspan="7" class="empty">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section id="tab-assign" class="refs-panel hidden">
        <div class="refs-toolbar">
            <select id="a_tournament">
                <option value="">Tất cả giải đấu</option>
            </select>
            <select id="a_match">
                <option value="">Chọn trận đấu</option>
            </select>
            <button id="btnAssign" class="btn primary" type="button">Phân công</button>
        </div>

        <p class="hint">Chọn giải và trận đấu để xem hoặc thiết lập trọng tài chính, trọng tài phụ, giám sát.</p>

        <div class="refs-table-wrap">
            <table class="refs-table">
                <thead>
                    <tr>
                        <th>ID PC</th>
                        <th>Trọng tài</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Ngày phân công</th>
                    </tr>
                </thead>
                <tbody id="a_tbody">
                    <tr>
                        <td colspan="5" class="empty">Chọn trận đấu để xem phân công.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section id="tab-leave" class="refs-panel hidden">
        <div class="refs-toolbar">
            <select id="l_ref">
                <option value="">Chọn trọng tài</option>
            </select>
            <button id="btnCreateLeave" class="btn primary" type="button">Tạo đơn nghỉ</button>

            <select id="l_status">
                <option value="">Tất cả trạng thái</option>
                <option value="CHO_DUYET">Chờ duyệt</option>
                <option value="DA_DUYET">Đã duyệt</option>
                <option value="TU_CHOI">Từ chối</option>
                <option value="DA_HUY">Đã hủy</option>
            </select>

            <input type="date" id="l_from" aria-label="Từ ngày" />
            <input type="date" id="l_to" aria-label="Đến ngày" />

            <button id="l_refresh" class="btn" type="button">Làm mới</button>
        </div>

        <div class="refs-table-wrap">
            <table class="refs-table">
                <thead>
                    <tr>
                        <th>ID Đơn</th>
                        <th>Trọng tài</th>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
                        <th>Lý do</th>
                        <th>Trạng thái</th>
                        <th>Ngày gửi</th>
                    </tr>
                </thead>
                <tbody id="l_tbody">
                    <tr>
                        <td colspan="7" class="empty">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <p class="refs-message" id="pageMessage" role="status"></p>
</section>

<div class="refs-modal hidden" id="addModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="addTitle">
        <div class="modal-head">
            <h2 id="addTitle">Thêm trọng tài</h2>
            <button class="icon" id="add_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="refs-grid">
            <div>
                <label for="add_username">Username</label>
                <input id="add_username" placeholder="vd: tt03" />
            </div>
            <div>
                <label for="add_email">Email</label>
                <input id="add_email" placeholder="vd: tt03@vtms.local" />
            </div>
            <div>
                <label for="add_phone">SĐT tùy chọn</label>
                <input id="add_phone" placeholder="09..." />
            </div>
            <div>
                <label for="add_password">Mật khẩu</label>
                <input id="add_password" type="password" placeholder="Tối thiểu 6 ký tự" />
            </div>

            <div>
                <label for="add_hodem">Họ đệm</label>
                <input id="add_hodem" />
            </div>
            <div>
                <label for="add_ten">Tên</label>
                <input id="add_ten" />
            </div>
            <div>
                <label for="add_gioitinh">Giới tính</label>
                <select id="add_gioitinh">
                    <option value="NAM">NAM</option>
                    <option value="NU">NỮ</option>
                    <option value="KHAC">KHÁC</option>
                </select>
            </div>
            <div>
                <label for="add_ngaysinh">Ngày sinh</label>
                <input id="add_ngaysinh" type="date" />
            </div>

            <div>
                <label for="add_capbac">Cấp bậc</label>
                <input id="add_capbac" placeholder="VD: Cấp thành phố" />
            </div>
            <div>
                <label for="add_kinhnghiem">Kinh nghiệm (năm)</label>
                <input id="add_kinhnghiem" type="number" min="0" value="0" />
            </div>

            <div class="colspan">
                <label for="add_diachi">Địa chỉ tùy chọn</label>
                <input id="add_diachi" />
            </div>
        </div>

        <div id="add_alert" class="refs-alert hidden"></div>

        <div class="modal-actions">
            <button class="btn" id="add_cancel" type="button">Hủy</button>
            <button class="btn primary" id="add_save" type="button">Tạo trọng tài</button>
        </div>
    </div>
</div>

<div class="refs-modal hidden" id="assignModal" aria-hidden="true">
    <div class="modal-content small" role="dialog" aria-modal="true" aria-labelledby="assignTitle">
        <div class="modal-head">
            <h2 id="assignTitle">Phân công trọng tài</h2>
            <button class="icon" id="as_close" type="button" aria-label="Đóng">×</button>
        </div>

        <label for="as_ref">Trọng tài</label>
        <select id="as_ref"></select>

        <label for="as_role">Vai trò</label>
        <select id="as_role">
            <option value="TRONG_TAI_CHINH">Trọng tài chính</option>
            <option value="TRONG_TAI_PHU">Trọng tài phụ</option>
            <option value="GIAM_SAT">Giám sát</option>
        </select>

        <div id="as_alert" class="refs-alert hidden"></div>

        <div class="modal-actions">
            <button class="btn" id="as_cancel" type="button">Hủy</button>
            <button class="btn primary" id="as_save" type="button">Phân công</button>
        </div>
    </div>
</div>

<div class="refs-modal hidden" id="leaveModal" aria-hidden="true">
    <div class="modal-content small" role="dialog" aria-modal="true" aria-labelledby="leaveTitle">
        <div class="modal-head">
            <h2 id="leaveTitle">Tạo đơn nghỉ trọng tài</h2>
            <button class="icon" id="lv_close" type="button" aria-label="Đóng">×</button>
        </div>

        <label for="lv_ref">Trọng tài</label>
        <select id="lv_ref"></select>

        <label for="lv_from">Từ ngày</label>
        <input id="lv_from" type="date" />

        <label for="lv_to">Đến ngày</label>
        <input id="lv_to" type="date" />

        <label for="lv_reason">Lý do</label>
        <textarea id="lv_reason" rows="4" placeholder="Nhập lý do..."></textarea>

        <div id="lv_alert" class="refs-alert hidden"></div>

        <div class="modal-actions">
            <button class="btn" id="lv_cancel" type="button">Hủy</button>
            <button class="btn primary" id="lv_save" type="button">Gửi đơn</button>
        </div>
    </div>
</div>
