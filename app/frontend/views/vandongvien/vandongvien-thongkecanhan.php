<section
    class="athlete-page athlete-personal-stats"
    data-stats-api="<?= e(url('/api/athlete/stats')) ?>"
>
    <header class="athlete-topbar">
        <div>
            <h1>Thống kê cá nhân</h1>
            <p class="sub">Các chỉ số thi đấu của bạn.</p>
        </div>
    </header>

    <div id="pageMessage" class="athlete-message"></div>

    <section class="athlete-grid four">
        <div class="athlete-card athlete-stat"><small>Số trận đã thi đấu</small><span id="matches">0</span></div>
        <div class="athlete-card athlete-stat"><small>Tổng điểm</small><span id="points">0</span></div>
        <div class="athlete-card athlete-stat"><small>Lần phát bóng</small><span id="serves">0</span></div>
        <div class="athlete-card athlete-stat"><small>Lần ghi điểm</small><span id="scores">0</span></div>
    </section>

    <section class="athlete-card">
        <h3>Chi tiết theo trận đấu</h3>
        <div class="table-wrap">
            <table class="athlete-table">
                <thead>
                    <tr>
                        <th>Giải đấu</th>
                        <th>Trận đấu</th>
                        <th>Điểm</th>
                        <th>Phát bóng</th>
                        <th>Chắn bóng</th>
                        <th>Ghi điểm</th>
                    </tr>
                </thead>
                <tbody id="detail">
                    <tr><td colspan="6" class="empty">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</section>
