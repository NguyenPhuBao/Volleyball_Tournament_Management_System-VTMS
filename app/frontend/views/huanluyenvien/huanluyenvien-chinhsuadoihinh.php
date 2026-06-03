<section
    class="coach-page coach-lineup-editor"
    data-teams-api="<?= e(url('/api/coach/teams')) ?>"
>
    <header class="coach-topbar">
        <div>
            <p class="eyebrow">HUẤN LUYỆN VIÊN</p>
            <h1>Tạo / Cập nhật đội hình</h1>
            <p class="sub">Chọn đội bóng và danh sách VĐV trong đội hình.</p>
        </div>
    </header>

    <section class="coach-card">
        <div class="coach-grid">
            <div>
                <label for="teamSelect">Đội bóng *</label>
                <select id="teamSelect"></select>
            </div>
            <div>
                <label for="lineupSelect">Đội hình hiện có</label>
                <select id="lineupSelect"><option value="">Tạo đội hình mới</option></select>
            </div>
            <div>
                <label for="lineupName">Tên đội hình *</label>
                <input id="lineupName" placeholder="VD: Đội hình vòng bảng" />
            </div>
            <div>
                <label for="lineupGender">Giới tính đội hình *</label>
                <select id="lineupGender">
                    <option value="NAM">Nam</option>
                    <option value="NU">Nữ</option>
                </select>
            </div>
            <div>
                <label for="lineupStatus">Trạng thái</label>
                <select id="lineupStatus">
                    <option value="BAN_NHAP">Bản nháp</option>
                    <option value="DA_CHOT">Đã chốt</option>
                    <option value="DA_CAP_NHAT">Đã cập nhật</option>
                </select>
            </div>
            <div>
                <label for="lineupMain">Đội hình chính thức</label>
                <label class="inline-check">
                    <input id="lineupMain" type="checkbox" />
                    Đặt làm đội hình thi đấu chính cho giới tính này
                </label>
            </div>
        </div>
    </section>

    <section class="coach-grid two-col">
        <div class="coach-card">
            <h3>Danh sách VĐV</h3>
            <ul class="coach-list" id="playerList"></ul>
        </div>

        <div class="coach-card">
            <h3>Đội hình thi đấu</h3>
            <table class="coach-table compact">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>VĐV</th>
                        <th>Vị trí</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="lineupBody"><tr><td colspan="4" class="empty">Chưa chọn VĐV.</td></tr></tbody>
            </table>
        </div>
    </section>

    <div id="pageMessage" class="coach-message"></div>
    <div id="alert" class="coach-alert hidden"></div>

    <div class="coach-actions">
        <a class="btn" href="<?= e(url('/huan-luyen-vien/doi-hinh')) ?>">Hủy</a>
        <button class="btn primary" id="btnSave" type="button">Lưu đội hình</button>
    </div>
</section>
