<section class="coach-page coach-team-profile" data-teams-api="<?= e(url('/api/coach/teams')) ?>">
    <header class="coach-topbar">
        <div>
            <p class="eyebrow">HUẤN LUYỆN VIÊN</p>
            <h1>Đội bóng của tôi</h1>
            <p class="sub">Tạo mới hoặc cập nhật thông tin đội bóng do HLV quản lý.</p>
        </div>
        <button id="btnEdit" class="btn primary" type="button">Tạo đội / Chỉnh sửa</button>
    </header>

    <section class="coach-toolbar">
        <select id="teamSelect"></select>
        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <div id="pageMessage" class="coach-message"></div>

    <section class="coach-card" id="teamInfo">
        <p class="empty">Đang tải thông tin đội bóng...</p>
    </section>
</section>

<div class="coach-modal hidden" id="teamModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h2>Thông tin đội bóng</h2>
            <button class="icon" id="m_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="coach-grid">
            <div>
                <label for="m_name">Tên đội *</label>
                <input id="m_name" />
            </div>
            <div>
                <label for="m_location">Địa phương *</label>
                <input id="m_location" />
            </div>
            <div>
                <label for="m_logo">Logo (URL)</label>
                <input id="m_logo" placeholder="https://..." />
            </div>
            <div>
                <label for="m_color">Màu áo</label>
                <input id="m_color" placeholder="VD: Xanh - Trắng" />
            </div>
            <div>
                <label for="m_status">Trạng thái</label>
                <select id="m_status">
                    <option value="HOAT_DONG">Hoạt động</option>
                    <option value="CHO_DUYET">Chờ duyệt</option>
                    <option value="TAM_KHOA">Tạm khóa</option>
                    <option value="GIAI_THE">Giải thể</option>
                </select>
            </div>
            <div class="colspan">
                <label for="m_desc">Mô tả / Ghi chú</label>
                <textarea id="m_desc" rows="3"></textarea>
            </div>
        </div>

        <div id="m_alert" class="coach-alert hidden"></div>

        <div class="coach-actions">
            <button class="btn" id="m_cancel" type="button">Hủy</button>
            <button class="btn primary" id="m_save" type="button">Lưu</button>
        </div>
    </div>
</div>
