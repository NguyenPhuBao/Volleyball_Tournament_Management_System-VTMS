<section
    class="organizer-teams"
    data-tournaments-api="<?= e(url('/api/organizer/tournaments')) ?>"
    data-teams-api="<?= e(url('/api/organizer/teams')) ?>"
>
    <header class="teams-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Xem hồ sơ đội bóng tham gia</h1>
            <p class="sub">Xem hồ sơ đội bóng, trạng thái đăng ký và danh sách vận động viên đang tham gia đội.</p>
        </div>
    </header>

    <section class="teams-toolbar" aria-label="Bộ lọc hồ sơ đội bóng">
        <input id="q" type="text" placeholder="Tìm theo tên đội / địa phương / HLV" />

        <select id="tournamentFilter">
            <option value="">Tất cả giải đấu</option>
        </select>

        <select id="teamStatusFilter">
            <option value="">Tất cả trạng thái đội</option>
            <option value="CHO_DUYET">Chờ duyệt</option>
            <option value="HOAT_DONG">Hoạt động</option>
            <option value="TAM_KHOA">Tạm khóa</option>
            <option value="GIAI_THE">Giải thể</option>
        </select>

        <select id="regStatusFilter">
            <option value="">Tất cả trạng thái đăng ký</option>
            <option value="CHO_DUYET">ĐK: Chờ duyệt</option>
            <option value="DA_DUYET">ĐK: Đã duyệt</option>
            <option value="TU_CHOI">ĐK: Từ chối</option>
            <option value="DA_HUY">ĐK: Đã hủy</option>
        </select>

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <div class="teams-table-wrap">
        <table class="teams-table">
            <thead>
                <tr>
                    <th>Mã đội</th>
                    <th>Đội bóng</th>
                    <th>Giải đấu</th>
                    <th>Địa phương</th>
                    <th>HLV</th>
                    <th>Trạng thái đội</th>
                    <th>Trạng thái ĐK</th>
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

    <p class="teams-message" id="pageMessage" role="status"></p>
</section>

<div class="team-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="m_teamName">
        <div class="modal-head">
            <div>
                <h2 id="m_teamName">Chi tiết đội bóng</h2>
                <p class="sub" id="m_teamSub">-</p>
            </div>
            <button id="m_close" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="team-grid">
            <div>
                <label for="m_teamId">ID đội</label>
                <input id="m_teamId" disabled />
            </div>
            <div>
                <label for="m_coach">HLV quản lý</label>
                <input id="m_coach" disabled />
            </div>
            <div>
                <label for="m_local">Địa phương</label>
                <input id="m_local" readonly />
            </div>
            <div>
                <label for="m_logo">Logo (URL)</label>
                <input id="m_logo" placeholder="https://..." readonly />
            </div>
            <div class="colspan">
                <label for="m_desc">Mô tả</label>
                <textarea id="m_desc" rows="3" readonly></textarea>
            </div>
            <div>
                <label for="m_status">Trạng thái đội</label>
                <select id="m_status" disabled>
                    <option value="CHO_DUYET">Chờ duyệt</option>
                    <option value="HOAT_DONG">Hoạt động</option>
                    <option value="TAM_KHOA">Tạm khóa</option>
                    <option value="GIAI_THE">Giải thể</option>
                </select>
            </div>
            <div>
                <label for="m_tournament">Giải đang xem</label>
                <select id="m_tournament">
                    <option value="">-</option>
                </select>
            </div>
        </div>

        <div id="m_alert" class="team-alert hidden"></div>

        <h3>Danh sách thành viên đội</h3>
        <div class="teams-toolbar small">
            <select id="m_memberStatus">
                <option value="">Tất cả trạng thái tài khoản</option>
                <option value="HOAT_DONG">Hoạt động</option>
                <option value="CHUA_KICH_HOAT">Chưa kích hoạt</option>
                <option value="CHO_DUYET">Chờ duyệt</option>
                <option value="TAM_KHOA">Tạm khóa</option>
                <option value="DA_HUY">Đã hủy</option>
            </select>
            <select id="m_memberRole">
                <option value="">Tất cả vai trò</option>
                <option value="DOI_TRUONG">Đội trưởng</option>
                <option value="THANH_VIEN">Thành viên</option>
                <option value="DU_BI">Dự bị</option>
            </select>
            <input id="m_memberQ" placeholder="Tìm theo mã VĐV / tên" />
        </div>

        <div class="teams-table-wrap compact">
            <table class="teams-table">
                <thead>
                    <tr>
                        <th>ID TV</th>
                        <th>Mã VĐV</th>
                        <th>Họ tên</th>
                        <th>Vị trí</th>
                        <th>Vai trò</th>
                        <th>Trạng thái tài khoản</th>
                        <th>Ngày tham gia</th>
                        <th>Ngày rời</th>
                    </tr>
                </thead>
                <tbody id="m_members">
                    <tr>
                        <td colspan="8" class="empty">Chưa có dữ liệu.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="modal-actions">
            <button id="m_closeBtn" class="btn" type="button">Đóng</button>
        </div>
    </div>
</div>
