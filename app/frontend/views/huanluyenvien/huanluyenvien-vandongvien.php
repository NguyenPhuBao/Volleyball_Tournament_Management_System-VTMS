<section
    class="coach-page coach-players"
    data-athletes-api="<?= e(url('/api/coach/players')) ?>"
    data-teams-api="<?= e(url('/api/coach/teams')) ?>"
>
    <header class="coach-topbar">
        <div>
            <p class="eyebrow">HUẤN LUYỆN VIÊN</p>
            <h1>Tài khoản Vận động viên</h1>
            <p class="sub">Tạo tài khoản VĐV do huấn luyện viên trực tiếp quản lý.</p>
        </div>
        <button id="btnCreate" class="btn primary" type="button">+ Tạo tài khoản VĐV</button>
    </header>

    <div id="pageMessage" class="coach-message"></div>

    <div class="table-wrap">
        <table class="coach-table">
            <thead>
                <tr>
                    <th>ID VĐV</th>
                    <th>Họ tên</th>
                    <th>Ngày sinh</th>
                    <th>Vị trí</th>
                    <th>Email</th>
                    <th>SĐT</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr><td colspan="7" class="empty">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<div class="coach-modal hidden" id="createModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h2>Tạo tài khoản Vận động viên</h2>
            <button class="icon" id="m_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="coach-grid">
            <div>
                <label for="m_name">Họ và tên *</label>
                <input id="m_name" />
            </div>
            <div>
                <label for="m_username">Username *</label>
                <input id="m_username" />
            </div>
            <div>
                <label for="m_password">Mật khẩu *</label>
                <input id="m_password" type="password" />
            </div>
            <div>
                <label for="m_gender">Giới tính *</label>
                <select id="m_gender">
                    <option value="NAM">Nam</option>
                    <option value="NU">Nữ</option>
                    <option value="KHAC">Khác</option>
                </select>
            </div>
            <div>
                <label for="m_dob">Ngày sinh *</label>
                <input id="m_dob" type="date" />
            </div>
            <div>
                <label for="m_position">Vị trí *</label>
                <select id="m_position">
                    <option value="CHU_CONG">Chủ công</option>
                    <option value="PHU_CONG">Phụ công</option>
                    <option value="CHUYEN_HAI">Chuyền hai</option>
                    <option value="DOI_CHUYEN">Đối chuyền</option>
                    <option value="LIBERO">Libero</option>
                    <option value="DOI_TRU">Đội trụ</option>
                </select>
            </div>
            <div>
                <label for="m_email">Email *</label>
                <input id="m_email" type="email" />
            </div>
            <div>
                <label for="m_phone">Số điện thoại *</label>
                <input id="m_phone" />
            </div>
            <div>
                <label for="m_team">Đội bóng</label>
                <select id="m_team">
                    <option value="">Không gắn đội</option>
                </select>
            </div>
            <div>
                <label for="m_teamRole">Vai trò trong đội</label>
                <select id="m_teamRole">
                    <option value="THANH_VIEN">Thành viên</option>
                    <option value="DOI_TRUONG">Đội trưởng</option>
                    <option value="DU_BI">Dự bị</option>
                </select>
            </div>
        </div>

        <div id="m_alert" class="coach-alert hidden"></div>

        <div class="coach-actions">
            <button class="btn" id="m_cancel" type="button">Hủy</button>
            <button class="btn primary" id="m_submit" type="button">Tạo</button>
        </div>
    </div>
</div>
