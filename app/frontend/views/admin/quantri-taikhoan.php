<section
    class="admin-users"
    data-accounts-api="<?= e(url('/api/admin/accounts')) ?>"
    data-roles-api="<?= e(url('/api/admin/roles')) ?>"
>
    <header class="users-topbar">
        <div>
            <p class="eyebrow">ADMIN</p>
            <h1>Quản lý tài khoản</h1>
        </div>
        <button class="btn primary" id="btnAdd" type="button">+ Thêm tài khoản</button>
    </header>

    <section class="users-toolbar" aria-label="Bộ lọc tài khoản">
        <input type="text" id="searchInput" placeholder="Tìm theo username / email..." />
        <select id="filterRole">
            <option value="">-- Tất cả vai trò --</option>
            <option value="ADMIN">ADMIN</option>
            <option value="BAN_TO_CHUC">BAN TỔ CHỨC</option>
            <option value="TRONG_TAI">TRỌNG TÀI</option>
            <option value="HUAN_LUYEN_VIEN">HLV</option>
            <option value="VAN_DONG_VIEN">VĐV</option>
        </select>
    </section>

    <div class="table-wrap">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="userTable">
                <tr>
                    <td colspan="5" class="empty">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="form-message" id="pageMessage" role="status"></p>
</section>

<div class="user-modal hidden" id="userModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <h2 id="modalTitle">Thêm tài khoản</h2>

        <label for="username">Username</label>
        <input id="username" autocomplete="username" />

        <label for="email">Email</label>
        <input id="email" type="email" autocomplete="email" />

        <label for="password">Mật khẩu</label>
        <input id="password" type="password" autocomplete="new-password" />
        <p class="field-hint" id="passwordHint">Để trống nếu không đổi mật khẩu.</p>

        <label for="role">Vai trò</label>
        <select id="role">
            <option value="ADMIN">ADMIN</option>
            <option value="BAN_TO_CHUC">BAN TỔ CHỨC</option>
            <option value="TRONG_TAI">TRỌNG TÀI</option>
            <option value="HUAN_LUYEN_VIEN">HLV</option>
            <option value="VAN_DONG_VIEN">VĐV</option>
        </select>

        <label for="status">Trạng thái</label>
        <select id="status">
            <option value="HOAT_DONG">Hoạt động</option>
            <option value="CHO_DUYET">Chờ duyệt</option>
            <option value="TAM_KHOA">Tạm khóa</option>
            <option value="DA_HUY">Đã hủy</option>
        </select>

        <p class="form-message" id="modalMessage" role="alert"></p>

        <div class="modal-actions">
            <button class="btn" id="btnCancel" type="button">Hủy</button>
            <button class="btn primary" id="btnSave" type="button">Lưu</button>
        </div>
    </div>
</div>
