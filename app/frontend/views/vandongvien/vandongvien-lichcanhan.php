<section
    class="athlete-page athlete-personal-schedule"
    data-schedule-api="<?= e(url('/api/athlete/schedule')) ?>"
>
    <header class="athlete-topbar">
        <div>
            <h1>Lịch thi đấu cá nhân</h1>
            <p class="sub">Các trận đấu liên quan đến đội bóng bạn đang tham gia.</p>
        </div>
    </header>

    <section id="empty" class="athlete-card empty hidden">
        Chưa có lịch thi đấu liên quan
    </section>

    <div class="table-wrap">
        <table class="athlete-table" id="scheduleTable">
            <thead>
                <tr>
                    <th>Ngày & Giờ</th>
                    <th>Giải đấu</th>
                    <th>Trận đấu</th>
                    <th>Sân</th>
                    <th>Vòng</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr><td colspan="6" class="empty">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</section>
