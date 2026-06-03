<section
    class="admin-profile-users"
    data-users-api="<?= e(url('/api/admin/users')) ?>"
>
    <header class="profile-users-topbar">
        <div>
            <p class="eyebrow">ADMIN</p>
            <h1>Quản lý người dùng</h1>
        </div>
    </header>

    <section class="profile-users-toolbar" aria-label="Bộ lọc người dùng">
        <input id="q" type="text" placeholder="Tìm theo họ tên / username / email" />
        <select id="roleFilter">
            <option value="">Tất cả vai trò</option>
            <option value="ADMIN">ADMIN</option>
            <option value="BAN_TO_CHUC">BAN TỔ CHỨC</option>
            <option value="TRONG_TAI">TRỌNG TÀI</option>
            <option value="HUAN_LUYEN_VIEN">HLV</option>
            <option value="VAN_DONG_VIEN">VĐV</option>
        </select>
        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="HOAT_DONG">HOẠT ĐỘNG</option>
            <option value="CHUA_KICH_HOAT">CHƯA KÍCH HOẠT</option>
            <option value="CHO_DUYET">CHỜ DUYỆT</option>
            <option value="TAM_KHOA">TẠM KHÓA</option>
            <option value="DA_HUY">ĐÃ HỦY</option>
        </select>
    </section>

    <div class="profile-table-wrap">
        <table class="profile-users-table">
            <thead>
                <tr>
                    <th>Họ tên</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr>
                    <td colspan="6" class="empty">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="profile-message" id="pageMessage" role="status"></p>
</section>

<div class="profile-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
        <h2 id="detailTitle">Chi tiết người dùng</h2>

        <h3>Thông tin tài khoản</h3>
        <label for="m_username">Username</label>
        <input id="m_username" disabled />

        <label for="m_email">Email</label>
        <input id="m_email" type="email" />

        <label for="m_role">Vai trò</label>
        <input id="m_role" disabled />

        <label for="m_status">Trạng thái</label>
        <select id="m_status">
            <option value="HOAT_DONG">HOẠT ĐỘNG</option>
            <option value="CHUA_KICH_HOAT">CHƯA KÍCH HOẠT</option>
            <option value="CHO_DUYET">CHỜ DUYỆT</option>
            <option value="TAM_KHOA">TẠM KHÓA</option>
            <option value="DA_HUY">ĐÃ HỦY</option>
        </select>

        <h3>Hồ sơ cá nhân</h3>
        <label for="m_hodem">Họ đệm</label>
        <input id="m_hodem" />

        <label for="m_ten">Tên</label>
        <input id="m_ten" />

        <label for="m_gioitinh">Giới tính</label>
        <select id="m_gioitinh">
            <option value="NAM">NAM</option>
            <option value="NU">NỮ</option>
            <option value="KHAC">KHÁC</option>
        </select>

        <label for="m_ngaysinh">Ngày sinh</label>
        <input type="date" id="m_ngaysinh" />

        <label for="m_quequan">Quê quán</label>
        <input id="m_quequan" />

        <p class="profile-message" id="modalMessage" role="alert"></p>

        <div class="modal-actions">
            <button id="btnClose" class="btn" type="button">Đóng</button>
            <button id="btnSave" class="btn primary" type="button">Lưu thay đổi</button>
        </div>
    </div>
</div>
