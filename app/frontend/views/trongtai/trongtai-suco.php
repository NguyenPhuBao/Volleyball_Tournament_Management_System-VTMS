<section
    class="referee-incidents"
    data-reports-api="<?= e(url('/api/trongtai/incident-reports')) ?>"
    data-matches-api="<?= e(url('/api/trongtai/reportable-matches')) ?>"
>
    <header class="incidents-topbar">
        <div>
            <p class="eyebrow">TRỌNG TÀI</p>
            <h1>Báo cáo sự cố</h1>
            <p class="sub">Tạo và theo dõi báo cáo sự cố của trọng tài theo trận đấu và trạng thái xử lý.</p>
        </div>
        <button id="btnCreate" class="btn primary" type="button">+ Tạo báo cáo</button>
    </header>

    <section class="incidents-toolbar" aria-label="Bộ lọc báo cáo sự cố">
        <input id="q" placeholder="Tìm theo tiêu đề / nội dung / trận đấu" />

        <select id="matchFilter">
            <option value="">Tất cả trận đấu</option>
        </select>

        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="DA_GUI">Đã gửi</option>
            <option value="DA_TIEP_NHAN">Đã tiếp nhận</option>
            <option value="DA_XU_LY">Đã xử lý</option>
            <option value="TU_CHOI">Từ chối</option>
        </select>

        <input type="date" id="fromDate" />
        <input type="date" id="toDate" />

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section class="incidents-stats" aria-label="Thống kê báo cáo sự cố">
        <div class="stat"><span id="sTotal">0</span><small>Tổng báo cáo</small></div>
        <div class="stat"><span id="sNew">0</span><small>Đã gửi</small></div>
        <div class="stat"><span id="sDone">0</span><small>Đã xử lý</small></div>
    </section>

    <div class="table-wrap">
        <table class="incidents-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Trận đấu</th>
                    <th>Tiêu đề</th>
                    <th>Trạng thái</th>
                    <th>Ngày báo cáo</th>
                    <th>Minh chứng</th>
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

    <div id="pageAlert" class="incident-alert hidden" role="status"></div>
</section>

<div class="incident-modal hidden" id="reportModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="m_titleLabel">
        <div class="modal-head">
            <div>
                <h2 id="m_titleLabel">Tạo báo cáo sự cố</h2>
                <p class="sub">Nhập thông tin báo cáo và nhấn “Báo cáo”.</p>
            </div>
            <button class="icon" id="m_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="incidents-grid">
            <div class="colspan">
                <label for="m_match">Trận đấu liên quan</label>
                <select id="m_match">
                    <option value="">Chọn trận đấu</option>
                </select>
            </div>

            <div class="colspan">
                <label for="m_title">Tiêu đề</label>
                <input id="m_title" placeholder="VD: Sự cố bóng hỏng / sự cố ánh sáng..." />
            </div>

            <div class="colspan">
                <label for="m_content">Nội dung báo cáo</label>
                <textarea id="m_content" rows="6" placeholder="Mô tả chi tiết sự cố, thời điểm xảy ra, ảnh hưởng, hướng xử lý..."></textarea>
            </div>

            <div class="colspan">
                <label for="m_evidence">Minh chứng (URL) (tùy chọn)</label>
                <input id="m_evidence" placeholder="https://..." />
            </div>
        </div>

        <div id="m_alert" class="incident-alert hidden"></div>

        <div class="modal-actions">
            <button id="m_cancel" class="btn" type="button">Hủy</button>
            <button id="m_submit" class="btn primary" type="button">Báo cáo</button>
        </div>

        <p class="hint">Khi gửi, backend insert vào Baocaosuco với trạng thái DA_GUI, ngày báo cáo hiện tại và ghi nhật ký hệ thống.</p>
    </div>
</div>

<div class="incident-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="d_titleLabel">
        <div class="modal-head">
            <div>
                <h2 id="d_titleLabel">Chi tiết báo cáo sự cố</h2>
                <p class="sub" id="d_sub">—</p>
            </div>
            <button class="icon" id="d_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="incidents-grid">
            <div>
                <label for="d_id">Mã báo cáo</label>
                <input id="d_id" disabled />
            </div>
            <div>
                <label for="d_status">Trạng thái</label>
                <input id="d_status" disabled />
            </div>

            <div class="colspan">
                <label for="d_match">Trận đấu</label>
                <input id="d_match" disabled />
            </div>

            <div>
                <label for="d_tournament">Giải đấu</label>
                <input id="d_tournament" disabled />
            </div>
            <div>
                <label for="d_created">Ngày báo cáo</label>
                <input id="d_created" disabled />
            </div>

            <div class="colspan">
                <label for="d_title">Tiêu đề</label>
                <input id="d_title" disabled />
            </div>

            <div class="colspan">
                <label for="d_content">Nội dung báo cáo</label>
                <textarea id="d_content" rows="6" disabled></textarea>
            </div>

            <div class="colspan">
                <label>Minh chứng</label>
                <div class="evidence" id="d_evidence">—</div>
            </div>
        </div>

        <div id="d_alert" class="incident-alert hidden"></div>

        <div class="modal-actions">
            <button id="d_closeBtn" class="btn" type="button">Đóng</button>
        </div>
    </div>
</div>
