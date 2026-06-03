<section
    class="athlete-page athlete-team-detail"
    data-teams-api="<?= e(url('/api/athlete/teams')) ?>"
>
    <header class="athlete-topbar">
        <div>
            <h1>Đội bóng của tôi</h1>
            <p class="sub">Thông tin đội bóng, ban huấn luyện và danh sách thành viên.</p>
        </div>
    </header>

    <div id="pageMessage" class="athlete-message"></div>

    <section id="teamInfo" class="athlete-card">
        <p class="empty">Đang tải thông tin đội bóng...</p>
    </section>

    <section class="athlete-card">
        <h3>Ban huấn luyện</h3>
        <p id="coach">—</p>
    </section>

    <section class="athlete-card">
        <h3>Danh sách thành viên</h3>
        <div class="table-wrap">
            <table class="athlete-table">
                <thead>
                    <tr>
                        <th>Họ tên</th>
                        <th>Vai trò</th>
                        <th>Vị trí</th>
                        <th>Email / SĐT</th>
                    </tr>
                </thead>
                <tbody id="members">
                    <tr><td colspan="4" class="empty">—</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</section>
