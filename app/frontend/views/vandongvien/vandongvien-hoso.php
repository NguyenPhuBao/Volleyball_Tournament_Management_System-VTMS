<section
    class="athlete-page athlete-profile"
    data-requests-api="<?= e(url('/api/athlete/identifier-change-requests')) ?>"
>
    <header class="athlete-topbar">
        <div>
            <h1>Hồ sơ cá nhân</h1>
            <p class="sub">Gửi yêu cầu sửa ID cá nhân để huấn luyện viên xét duyệt.</p>
        </div>
    </header>

    <section class="athlete-card">
        <p><b>Họ tên:</b> <span id="fullName">—</span></p>
        <p><b>ID cá nhân:</b> <span id="currentId">—</span>
            <button id="btnEditId" class="btn" type="button">Sửa ID cá nhân</button>
        </p>
        <p><b>CCCD:</b> <span id="currentCccd">—</span></p>
        <p><b>Email:</b> <span id="email">—</span></p>
        <p><b>Số điện thoại:</b> <span id="phone">—</span></p>
    </section>

    <div id="pageMessage" class="athlete-message"></div>

    <div class="athlete-modal hidden" id="editIdModal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true">
            <div class="modal-head">
                <h2>Yêu cầu sửa ID cá nhân</h2>
                <button class="icon" id="m_close" type="button" aria-label="Đóng">×</button>
            </div>

            <label for="newId">ID cá nhân mới *</label>
            <input id="newId" placeholder="VD: VDV-2026" />

            <label for="reason">Lý do sửa ID *</label>
            <textarea id="reason" rows="3"></textarea>

            <div id="modalAlert" class="athlete-alert hidden"></div>

            <div class="athlete-actions">
                <button class="btn" id="m_cancel" type="button">Hủy</button>
                <button class="btn primary" id="m_submit" type="button">Gửi yêu cầu</button>
            </div>
        </div>
    </div>
</section>
