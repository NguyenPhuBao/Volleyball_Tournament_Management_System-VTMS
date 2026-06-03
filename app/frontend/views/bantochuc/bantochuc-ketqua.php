<section
    class="organizer-results"
    data-results-api="<?= e(url('/api/organizer/match-results')) ?>"
    data-tournaments-api="<?= e(url('/api/organizer/tournaments')) ?>"
>
    <header class="results-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Quản lý kết quả trận đấu</h1>
            <p class="sub">Danh sách trận đã kết thúc, điều chỉnh kết quả và công bố kết quả.</p>
        </div>
    </header>

    <section class="results-toolbar" aria-label="Bộ lọc kết quả trận đấu">
        <input id="q" type="text" placeholder="Tìm theo đội / mã trận / giải đấu" />

        <select id="tournamentFilter">
            <option value="">Tất cả giải đấu</option>
        </select>

        <select id="publishFilter">
            <option value="">Tất cả trạng thái công bố</option>
            <option value="CHO_CONG_BO">Chờ công bố</option>
            <option value="DA_DIEU_CHINH">Đã điều chỉnh</option>
            <option value="DA_CONG_BO">Đã công bố</option>
            <option value="BI_HUY">Bị hủy</option>
        </select>

        <input type="date" id="fromDate" />
        <input type="date" id="toDate" />

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section class="results-stats" aria-label="Thống kê kết quả trận đấu">
        <div class="stat"><span id="sEnded">0</span><small>Trận đã kết thúc</small></div>
        <div class="stat"><span id="sUnpub">0</span><small>Chưa công bố</small></div>
        <div class="stat"><span id="sPub">0</span><small>Đã công bố</small></div>
    </section>

    <div class="table-wrap">
        <table class="results-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Giải</th>
                    <th>Trận</th>
                    <th>Thời gian</th>
                    <th>Sân</th>
                    <th>Kết quả set</th>
                    <th>Trạng thái</th>
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

    <p id="pageMessage" class="results-message" role="status"></p>
</section>

<div class="result-modal hidden" id="editModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="m_title">
        <div class="modal-head">
            <div>
                <h2 id="m_title">Chỉnh sửa kết quả trận đấu</h2>
                <p class="sub" id="m_sub">—</p>
            </div>
            <button class="icon" id="m_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="results-grid">
            <div>
                <label for="m_matchId">Mã trận</label>
                <input id="m_matchId" disabled />
            </div>
            <div>
                <label for="m_publish">Trạng thái công bố</label>
                <input id="m_publish" disabled />
            </div>

            <div class="colspan">
                <label for="m_team1">Đội 1</label>
                <input id="m_team1" disabled />
            </div>
            <div class="colspan">
                <label for="m_team2">Đội 2</label>
                <input id="m_team2" disabled />
            </div>
        </div>

        <hr />

        <h3>Điểm theo set</h3>
        <p class="hint">Nhập điểm từng set theo Bo5. Kết quả hợp lệ là 3-0, 3-1 hoặc 3-2; điểm mỗi set không được hòa.</p>

        <div class="sets" id="sets"></div>

        <div class="set-actions">
            <button id="btnAddSet" class="btn" type="button">Thêm set</button>
            <button id="btnRemoveSet" class="btn" type="button">Bớt set</button>
        </div>

        <div class="results-grid">
            <div>
                <label for="m_setScore">Tỷ số set</label>
                <input id="m_setScore" disabled />
            </div>
            <div>
                <label for="m_winner">Đội thắng</label>
                <input id="m_winner" disabled />
            </div>

            <div class="colspan">
                <label for="m_reason">Lý do điều chỉnh</label>
                <textarea id="m_reason" rows="3" placeholder="Bắt buộc khi cập nhật kết quả."></textarea>
            </div>

            <div class="colspan">
                <label for="m_evidence">Minh chứng URL tùy chọn</label>
                <input id="m_evidence" placeholder="https://..." />
            </div>
        </div>

        <div id="m_alert" class="results-alert hidden"></div>

        <div class="modal-actions">
            <button id="m_cancel" class="btn" type="button">Hủy</button>
            <button id="m_save" class="btn primary" type="button">Cập nhật kết quả</button>
        </div>
    </div>
</div>
