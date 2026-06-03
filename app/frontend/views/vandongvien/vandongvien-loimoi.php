<section
    class="athlete-page athlete-invitations"
    data-invitations-api="<?= e(url('/api/athlete/team-invitations')) ?>"
    data-detail-url="<?= e(url('/van-dong-vien/loi-moi-doi-bong/chi-tiet')) ?>"
>
    <header class="athlete-topbar">
        <div>
            <h1>Lời mời tham gia đội bóng</h1>
            <p class="sub">Xem và phản hồi lời mời tham gia đội bóng.</p>
        </div>
    </header>

    <div id="pageMessage" class="athlete-message"></div>

    <section id="empty" class="athlete-card empty hidden">
        Hiện tại không có lời mời tham gia đội bóng
    </section>

    <div class="table-wrap">
        <table class="athlete-table" id="inviteTable">
            <thead>
                <tr>
                    <th>Đội bóng</th>
                    <th>Vai trò đề nghị</th>
                    <th>Huấn luyện viên</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr><td colspan="6" class="empty">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</section>
