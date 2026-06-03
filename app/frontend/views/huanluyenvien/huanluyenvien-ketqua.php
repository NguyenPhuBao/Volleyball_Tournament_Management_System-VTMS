<section
    class="coach-page coach-results"
    data-results-api="<?= e(url('/api/coach/results')) ?>"
    data-teams-api="<?= e(url('/api/coach/teams')) ?>"
>
    <header class="coach-topbar">
        <div>
            <p class="eyebrow">HUẤN LUYỆN VIÊN</p>
            <h1>Kết quả thi đấu</h1>
            <p class="sub">Xem kết quả các trận của đội và gửi khiếu nại nếu cần điều chỉnh.</p>
        </div>
    </header>

    <section class="coach-toolbar">
        <select id="teamFilter">
            <option value="">Tất cả đội bóng</option>
        </select>
        <input id="q" placeholder="Tìm theo giải / mã trận / đội / sân" />
        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="DA_CONG_BO">Đã công bố</option>
            <option value="DA_DIEU_CHINH">Đã điều chỉnh</option>
        </select>
        <input type="date" id="fromDate" />
        <input type="date" id="toDate" />
        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <div id="pageMessage" class="coach-message" role="status"></div>

    <section id="empty" class="coach-card empty hidden">Chưa có kết quả thi đấu.</section>

    <div class="table-wrap">
        <table class="coach-table" id="resultsTable">
            <thead>
                <tr>
                    <th>Mã trận</th>
                    <th>Thời gian</th>
                    <th>Giải đấu</th>
                    <th>Trận đấu</th>
                    <th>Tỷ số</th>
                    <th>Đội thắng</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr><td colspan="8" class="empty">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<div class="coach-modal hidden" id="complaintModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="complaintTitle">
        <div class="modal-head">
            <div>
                <h2 id="complaintTitle">Khiếu nại kết quả</h2>
                <p class="sub" id="complaintSub">—</p>
            </div>
            <button class="icon" id="btnCloseModal" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="coach-grid">
            <div>
                <label for="matchInfo">Trận đấu</label>
                <input id="matchInfo" disabled />
            </div>
            <div>
                <label for="scoreInfo">Kết quả hiện tại</label>
                <input id="scoreInfo" disabled />
            </div>
            <div class="colspan">
                <label for="complaintContent">Nội dung khiếu nại</label>
                <textarea id="complaintContent" rows="5" placeholder="Nhập rõ nội dung cần BTC xem xét."></textarea>
            </div>
            <div class="colspan">
                <label for="complaintEvidence">Minh chứng nếu có</label>
                <input id="complaintEvidence" placeholder="Đường dẫn minh chứng hoặc ghi chú ngắn" />
            </div>
        </div>

        <div id="modalAlert" class="coach-alert hidden"></div>

        <div class="coach-actions">
            <button id="btnCancelComplaint" class="btn" type="button">Đóng</button>
            <button id="btnSubmitComplaint" class="btn primary" type="button">Gửi khiếu nại</button>
        </div>
    </div>
</div>
