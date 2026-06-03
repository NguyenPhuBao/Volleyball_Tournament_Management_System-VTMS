<section
    class="athlete-page athlete-leave"
    data-leaves-api="<?= e(url('/api/athlete/leave-requests')) ?>"
    data-schedule-api="<?= e(url('/api/athlete/schedule')) ?>"
>
    <header class="athlete-topbar">
        <div>
            <h1>Xin nghỉ phép thi đấu</h1>
            <p class="sub">Tạo yêu cầu xin nghỉ theo trận đấu hoặc khoảng thời gian.</p>
        </div>
        <button class="btn primary" id="btnOpen" type="button">+ Tạo yêu cầu xin nghỉ</button>
    </header>

    <div id="pageMessage" class="athlete-message"></div>

    <section class="athlete-card">
        <h3>Danh sách yêu cầu</h3>
        <div class="table-wrap">
            <table class="athlete-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Trận đấu</th>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
                        <th>Lý do</th>
                        <th>Trạng thái</th>
                        <th>Ngày gửi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr><td colspan="8" class="empty">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="athlete-modal hidden" id="leaveModal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true">
            <div class="modal-head">
                <h2>Yêu cầu xin nghỉ phép thi đấu</h2>
                <button class="icon" id="m_close" type="button" aria-label="Đóng">×</button>
            </div>

            <label for="match">Chọn trận đấu</label>
            <select id="match">
                <option value="">-- Không gắn với trận --</option>
            </select>

            <label>Hoặc khoảng thời gian *</label>
            <div class="range">
                <input type="date" id="from" />
                <input type="date" id="to" />
            </div>

            <label for="reason">Lý do xin nghỉ *</label>
            <textarea id="reason" rows="3"></textarea>

            <div id="modalAlert" class="athlete-alert hidden"></div>

            <div class="athlete-actions">
                <button class="btn" id="m_cancel" type="button">Hủy</button>
                <button class="btn primary" id="m_submit" type="button">Gửi yêu cầu</button>
            </div>
        </div>
    </div>
</section>
