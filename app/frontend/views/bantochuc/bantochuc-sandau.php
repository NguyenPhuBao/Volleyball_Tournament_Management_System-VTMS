<section
    class="organizer-venues"
    data-venues-api="<?= e(url('/api/organizer/venues')) ?>"
    data-locations-api="<?= e(url('/api/organizer/competition-locations')) ?>"
>
    <header class="venues-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Quản lý sân đấu</h1>
            <p class="sub">Bổ sung, cập nhật và loại bỏ sân đấu bằng cách chuyển sang trạng thái ngưng sử dụng.</p>
        </div>
        <button id="btnAdd" class="btn primary" type="button">Bổ sung sân đấu</button>
    </header>

    <section class="venues-toolbar" aria-label="Bộ lọc sân đấu">
        <input id="q" type="text" placeholder="Tìm theo tên sân / địa chỉ" />

        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="HOAT_DONG">Hoạt động</option>
            <option value="DANG_BAO_TRI">Đang bảo trì</option>
            <option value="NGUNG_SU_DUNG">Ngưng sử dụng</option>
        </select>

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <div class="venues-table-wrap">
        <table class="venues-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên sân</th>
                    <th>Vị trí thi đấu</th>
                    <th>Địa chỉ</th>
                    <th>Sức chứa</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
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

    <p class="venues-message" id="pageMessage" role="status"></p>
</section>

<div class="venue-modal hidden" id="venueModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-head">
            <h2 id="modalTitle">Bổ sung sân đấu</h2>
            <button id="m_close" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="venue-grid">
            <div class="colspan">
                <label for="m_name">Tên sân</label>
                <input id="m_name" placeholder="VD: Sân A - Nhà thi đấu IUH" />
            </div>

            <div class="colspan">
                <label for="m_location">Vị trí thi đấu</label>
                <select id="m_location"></select>
            </div>

            <div>
                <label for="m_capacity">Sức chứa</label>
                <input id="m_capacity" type="number" min="0" value="0" />
            </div>

            <div>
                <label for="m_status">Trạng thái</label>
                <select id="m_status">
                    <option value="HOAT_DONG">Hoạt động</option>
                    <option value="DANG_BAO_TRI">Đang bảo trì</option>
                    <option value="NGUNG_SU_DUNG">Ngưng sử dụng</option>
                </select>
            </div>

            <div class="colspan">
                <label for="m_note">Mô tả / Ghi chú tùy chọn</label>
                <textarea id="m_note" rows="3" placeholder="VD: Sân thi đấu chính..."></textarea>
            </div>
        </div>

        <div id="m_alert" class="venue-alert hidden"></div>

        <div class="modal-actions">
            <button id="m_cancel" class="btn" type="button">Hủy</button>
            <button id="m_remove" class="btn danger" type="button">Loại bỏ</button>
            <button id="m_save" class="btn primary" type="button">Lưu</button>
        </div>
    </div>
</div>
