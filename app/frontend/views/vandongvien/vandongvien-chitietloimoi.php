<section
    class="athlete-page athlete-invitation-detail"
    data-invitations-api="<?= e(url('/api/athlete/team-invitations')) ?>"
    data-list-url="<?= e(url('/van-dong-vien/loi-moi-doi-bong')) ?>"
>
    <header class="athlete-topbar">
        <div>
            <h1>Chi tiết lời mời</h1>
            <p class="sub" id="inviteSub">—</p>
        </div>
        <a class="btn" href="<?= e(url('/van-dong-vien/loi-moi-doi-bong')) ?>">Quay lại</a>
    </header>

    <section class="athlete-card">
        <p><b>Đội bóng:</b> <span id="teamName">—</span></p>
        <p><b>Huấn luyện viên:</b> <span id="coachName">—</span></p>
        <p><b>Vai trò đề nghị:</b> <span id="role">—</span></p>
        <p><b>Trạng thái:</b> <span id="status">—</span></p>
        <p><b>Mô tả đội bóng:</b></p>
        <p id="desc">—</p>
    </section>

    <div id="pageMessage" class="athlete-message"></div>

    <div class="athlete-actions">
        <button class="btn danger" id="btnReject" type="button">Từ chối</button>
        <button class="btn primary" id="btnAccept" type="button">Đồng ý</button>
    </div>
</section>
