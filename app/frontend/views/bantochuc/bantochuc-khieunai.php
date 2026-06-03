<section
    class="organizer-complaints"
    data-complaints-api="<?= e(url('/api/organizer/complaints')) ?>"
    data-tournaments-api="<?= e(url('/api/organizer/tournaments')) ?>"
>
    <header class="complaints-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Tiếp nhận &amp; Quản lý khiếu nại</h1>
            <p class="sub">Xem danh sách khiếu nại, tiếp nhận, từ chối, đánh dấu đã xử lý hoặc không xử lý.</p>
        </div>
    </header>

    <section class="complaints-toolbar" aria-label="Bộ lọc khiếu nại">
        <input id="q" type="text" placeholder="Tìm theo tiêu đề / nội dung / người gửi / trận đấu" />

        <select id="tournamentFilter">
            <option value="">Tất cả giải đấu</option>
        </select>

        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="CHO_TIEP_NHAN">Chờ tiếp nhận</option>
            <option value="DANG_XU_LY">Chờ xử lý</option>
            <option value="DA_XU_LY">Đã xử lý</option>
            <option value="TU_CHOI">Từ chối tiếp nhận</option>
            <option value="KHONG_XU_LY">Không xử lý</option>
        </select>

        <input type="date" id="fromDate" />
        <input type="date" id="toDate" />

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section class="complaints-stats" aria-label="Thống kê khiếu nại">
        <div class="stat"><span id="sNew">0</span><small>Chờ tiếp nhận</small></div>
        <div class="stat"><span id="sPending">0</span><small>Chờ xử lý</small></div>
        <div class="stat"><span id="sDone">0</span><small>Đã xử lý</small></div>
    </section>

    <div class="table-wrap">
        <table class="complaints-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Thời gian</th>
                    <th>Giải</th>
                    <th>Liên quan</th>
                    <th>Người gửi</th>
                    <th>Tiêu đề</th>
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

    <p id="pageMessage" class="complaints-message" role="status"></p>
</section>

<div class="complaint-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="m_title">
        <div class="modal-head">
            <div>
                <h2 id="m_title">Chi tiết khiếu nại</h2>
                <p class="sub" id="m_sub">—</p>
            </div>
            <button class="icon" id="m_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="complaint-grid">
            <div>
                <label for="m_id">Mã khiếu nại</label>
                <input id="m_id" disabled />
            </div>
            <div>
                <label for="m_status">Trạng thái</label>
                <input id="m_status" disabled />
            </div>

            <div>
                <label for="m_tournament">Giải đấu</label>
                <input id="m_tournament" disabled />
            </div>
            <div>
                <label for="m_related">Liên quan</label>
                <input id="m_related" disabled />
            </div>

            <div>
                <label for="m_sender">Người gửi</label>
                <input id="m_sender" disabled />
            </div>
            <div>
                <label for="m_created">Thời gian gửi</label>
                <input id="m_created" disabled />
            </div>

            <div class="colspan">
                <label for="m_content">Nội dung khiếu nại</label>
                <textarea id="m_content" rows="6" disabled></textarea>
            </div>

            <div class="colspan">
                <label>Minh chứng nếu có</label>
                <div class="evidence">
                    <a id="m_evidence" href="#" target="_blank" rel="noopener">—</a>
                </div>
            </div>
        </div>

        <hr />

        <h3>Xử lý khiếu nại</h3>
        <div class="complaint-grid">
            <div class="colspan">
                <label for="m_note">Ghi chú xử lý / phản hồi</label>
                <textarea id="m_note" rows="4" placeholder="Bắt buộc khi tiếp nhận, từ chối, xác nhận xử lý hoặc không xử lý."></textarea>
            </div>
        </div>

        <div id="m_result_block" class="complaint-result-block hidden">
            <h3>Điều chỉnh tỷ số trận đấu</h3>
            <div class="complaint-grid">
                <div>
                    <label for="m_score1" id="m_score1_label">Điểm đội 1</label>
                    <input id="m_score1" type="number" min="0" />
                </div>
                <div>
                    <label for="m_score2" id="m_score2_label">Điểm đội 2</label>
                    <input id="m_score2" type="number" min="0" />
                </div>
                <div>
                    <label for="m_sets1" id="m_sets1_label">Số set đội 1</label>
                    <input id="m_sets1" type="number" min="0" max="3" />
                </div>
                <div>
                    <label for="m_sets2" id="m_sets2_label">Số set đội 2</label>
                    <input id="m_sets2" type="number" min="0" max="3" />
                </div>
                <div class="colspan">
                    <label for="m_winner">Đội thắng</label>
                    <select id="m_winner"></select>
                </div>
            </div>
        </div>

        <div id="m_alert" class="complaint-alert hidden"></div>

        <div class="modal-actions">
            <button id="btnReject" class="btn danger" type="button">Từ chối tiếp nhận</button>
            <button id="btnAccept" class="btn primary" type="button">Tiếp nhận khiếu nại</button>
            <button id="btnNoAction" class="btn" type="button">Không xử lý</button>
            <button id="btnResolved" class="btn primary" type="button">Đã xử lý</button>
            <button id="btnClose" class="btn" type="button">Đóng</button>
        </div>
    </div>
</div>
