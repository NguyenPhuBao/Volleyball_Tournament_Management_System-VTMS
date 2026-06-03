<section class="coach-page coach-members" data-teams-api="<?= e(url('/api/coach/teams')) ?>">
    <header class="coach-topbar">
        <div>
            <p class="eyebrow">HUẤN LUYỆN VIÊN</p>
            <h1>Thành viên đội bóng</h1>
            <p class="sub">Thêm, xóa và chuyển đổi thành viên giữa các đội bóng của HLV.</p>
        </div>
        <button id="btnAdd" class="btn primary" type="button">+ Thêm thành viên</button>
    </header>

    <section class="coach-toolbar">
        <select id="teamSelect"></select>
        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <div id="pageMessage" class="coach-message"></div>

    <div class="table-wrap">
        <table class="coach-table">
            <thead>
                <tr>
                    <th>ID TV</th>
                    <th>Họ tên</th>
                    <th>Vai trò</th>
                    <th>Email / SĐT</th>
                    <th>Vị trí</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="tbody"><tr><td colspan="7" class="empty">Đang tải dữ liệu...</td></tr></tbody>
        </table>
    </div>
</section>

<div class="coach-modal hidden" id="addModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h2>Thêm thành viên</h2>
            <button class="icon" id="a_close" type="button" aria-label="Đóng">×</button>
        </div>
        <label for="a_account">ID VĐV *</label>
        <input id="a_account" placeholder="VD: 12" />

        <label for="a_role">Vai trò *</label>
        <select id="a_role">
            <option value="THANH_VIEN">Thành viên</option>
            <option value="DOI_TRUONG">Đội trưởng</option>
            <option value="DU_BI">Dự bị</option>
        </select>

        <div id="a_alert" class="coach-alert hidden"></div>
        <div class="coach-actions">
            <button id="a_cancel" class="btn" type="button">Hủy</button>
            <button id="a_submit" class="btn primary" type="button">Thêm</button>
        </div>
    </div>
</div>

<div class="coach-modal hidden" id="switchModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h2>Chuyển đổi thành viên</h2>
            <button class="icon" id="s_close" type="button" aria-label="Đóng">×</button>
        </div>
        <p id="s_name" class="sub"></p>

        <label for="s_team">Đội đích *</label>
        <select id="s_team"></select>

        <label for="s_role">Vai trò mới *</label>
        <select id="s_role">
            <option value="THANH_VIEN">Thành viên</option>
            <option value="DOI_TRUONG">Đội trưởng</option>
            <option value="DU_BI">Dự bị</option>
        </select>

        <label for="s_reason">Lý do</label>
        <textarea id="s_reason" rows="3"></textarea>

        <div id="s_alert" class="coach-alert hidden"></div>
        <div class="coach-actions">
            <button id="s_cancel" class="btn" type="button">Hủy</button>
            <button id="s_submit" class="btn primary" type="button">Chuyển</button>
        </div>
    </div>
</div>
