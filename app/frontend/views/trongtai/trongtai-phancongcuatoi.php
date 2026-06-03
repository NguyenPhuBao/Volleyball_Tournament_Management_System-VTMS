<section
    class="referee-assignments"
    data-assignments-api="<?= e(url('/api/trongtai/assignments')) ?>"
    data-tournaments-api="<?= e(url('/api/trongtai/tournaments-of-me')) ?>"
    data-venues-api="<?= e(url('/api/trongtai/venues-of-me')) ?>"
    data-match-detail-api="<?= e(url('/api/trongtai/matches')) ?>"
    data-supervision-url="<?= e(url('/trong-tai/giam-sat')) ?>"
>
    <header class="assignments-topbar">
        <div>
            <p class="eyebrow">TRỌNG TÀI</p>
            <h1>Lịch phân công trận đấu</h1>
            <p class="sub">Xem các trận được phân công, lọc theo ngày, giải, sân, vai trò và trạng thái.</p>
        </div>
    </header>

    <section class="assignments-toolbar" aria-label="Bộ lọc lịch phân công">
        <input id="q" type="text" placeholder="Tìm theo tên giải / tên đội / sân" />

        <select id="tournamentFilter">
            <option value="">Tất cả giải đấu</option>
        </select>

        <select id="venueFilter">
            <option value="">Tất cả sân đấu</option>
        </select>

        <select id="roleFilter">
            <option value="">Tất cả vai trò</option>
            <option value="TRONG_TAI_CHINH">Trọng tài chính</option>
            <option value="TRONG_TAI_PHU">Trọng tài phụ</option>
            <option value="GIAM_SAT">Giám sát</option>
        </select>

        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="CHO_XAC_NHAN">Chờ xác nhận</option>
            <option value="DA_XAC_NHAN">Đã xác nhận</option>
            <option value="TU_CHOI">Đã hủy xác nhận</option>
            <option value="DA_HUY">Đã hủy</option>
        </select>

        <input id="fromDate" type="date" />
        <input id="toDate" type="date" />

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section class="assignments-stats" aria-label="Thống kê lịch phân công">
        <div class="stat"><span id="sTotal">0</span><small>Tổng phân công</small></div>
        <div class="stat"><span id="sUpcoming">0</span><small>Sắp diễn ra</small></div>
        <div class="stat"><span id="sNeedConfirm">0</span><small>Đã xác nhận</small></div>
    </section>

    <div class="table-wrap">
        <table class="assignments-table">
            <thead>
                <tr>
                    <th>Giải</th>
                    <th>Trận</th>
                    <th>Thời gian</th>
                    <th>Sân</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr>
                    <td colspan="7" class="empty">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p id="pageMessage" class="assignments-message" role="status"></p>
</section>

<div class="assignment-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="m_title">
        <div class="modal-head">
            <div>
                <h2 id="m_title">Chi tiết phân công</h2>
                <p class="sub" id="m_sub">—</p>
            </div>
            <button class="icon" id="m_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="assignments-grid">
            <div>
                <label for="m_assignId">ID phân công</label>
                <input id="m_assignId" disabled />
            </div>
            <div>
                <label for="m_status">Trạng thái</label>
                <input id="m_status" disabled />
            </div>

            <div class="colspan">
                <label for="m_tournament">Giải đấu</label>
                <input id="m_tournament" disabled />
            </div>

            <div class="colspan">
                <label for="m_match">Trận đấu</label>
                <input id="m_match" disabled />
            </div>

            <div>
                <label for="m_start">Thời gian bắt đầu</label>
                <input id="m_start" disabled />
            </div>
            <div>
                <label for="m_end">Thời gian kết thúc</label>
                <input id="m_end" disabled />
            </div>

            <div class="colspan">
                <label for="m_venue">Sân đấu</label>
                <input id="m_venue" disabled />
            </div>

            <div>
                <label for="m_role">Vai trò</label>
                <input id="m_role" disabled />
            </div>
            <div>
                <label for="m_assignedAt">Ngày phân công</label>
                <input id="m_assignedAt" disabled />
            </div>

            <div class="colspan">
                <label for="m_note">Ghi chú</label>
                <textarea id="m_note" rows="3" disabled placeholder="(Nếu backend có ghi chú phân công)"></textarea>
            </div>
            <div class="colspan hidden" id="m_cancelReasonWrap">
                <label for="m_cancelReason">Lý do hủy xác nhận</label>
                <textarea id="m_cancelReason" rows="3" placeholder="Nhập lý do không tham gia trận đấu..."></textarea>
            </div>
        </div>

        <div id="m_alert" class="assignments-alert hidden"></div>

        <div class="modal-actions">
            <button id="btnDecline" class="btn danger" type="button">Hủy xác nhận</button>
            <button id="btnConfirm" class="btn primary" type="button" hidden>Nhận phân công</button>
            <button id="btnClose" class="btn" type="button">Đóng</button>
        </div>
    </div>
</div>

<div class="assignment-modal hidden" id="matchDetailModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="md_title">
        <div class="modal-head">
            <div>
                <h2 id="md_title">Thông tin chi tiết trận đấu</h2>
                <p class="sub" id="md_sub">—</p>
            </div>
            <button class="icon" id="md_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="assignments-grid">
            <div>
                <label for="md_matchId">Mã trận</label>
                <input id="md_matchId" disabled />
            </div>
            <div>
                <label for="md_matchStatus">Trạng thái trận</label>
                <input id="md_matchStatus" disabled />
            </div>

            <div class="colspan">
                <label for="md_tournament">Giải đấu</label>
                <input id="md_tournament" disabled />
            </div>

            <div>
                <label for="md_round">Vòng đấu</label>
                <input id="md_round" disabled />
            </div>
            <div>
                <label for="md_group">Bảng đấu (nếu có)</label>
                <input id="md_group" disabled placeholder="(Không thuộc bảng)" />
            </div>

            <div class="colspan">
                <label for="md_team1">Đội 1</label>
                <input id="md_team1" disabled />
            </div>
            <div class="colspan">
                <label for="md_team2">Đội 2</label>
                <input id="md_team2" disabled />
            </div>

            <div>
                <label for="md_start">Thời gian bắt đầu</label>
                <input id="md_start" disabled />
            </div>
            <div>
                <label for="md_end">Thời gian kết thúc</label>
                <input id="md_end" disabled />
            </div>

            <div class="colspan">
                <label for="md_venue">Sân đấu</label>
                <input id="md_venue" disabled />
            </div>

            <div class="colspan">
                <label for="md_venueAddr">Địa chỉ sân (nếu có)</label>
                <input id="md_venueAddr" disabled />
            </div>
        </div>

        <hr />

        <h3>Tổ trọng tài của trận</h3>
        <p class="hint">Danh sách trọng tài được phân công theo trận đấu.</p>

        <div class="table-wrap">
            <table class="assignments-table compact">
                <thead>
                    <tr>
                        <th>Trọng tài</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Ngày phân công</th>
                    </tr>
                </thead>
                <tbody id="md_refs">
                    <tr>
                        <td colspan="4" class="empty">Chưa có dữ liệu.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <section class="match-result hidden" id="md_resultSection" aria-live="polite">
            <hr />
            <h3>Tỷ số trận đấu</h3>
            <div class="match-result-score" id="md_resultScore"></div>
            <div class="match-result-sets" id="md_resultSets"></div>
        </section>

        <div id="md_alert" class="assignments-alert hidden"></div>

        <div class="modal-actions">
            <button class="btn" id="md_closeBtn" type="button">Đóng</button>
        </div>
    </div>
</div>
