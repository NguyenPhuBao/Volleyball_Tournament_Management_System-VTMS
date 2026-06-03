<section
    class="athlete-page athlete-lineup-view"
    data-lineups-api="<?= e(url('/api/athlete/lineups')) ?>"
>
    <header class="athlete-topbar">
        <div>
            <h1>Đội hình đội bóng</h1>
            <p class="sub">Xem đội hình đã được huấn luyện viên thiết lập.</p>
        </div>
    </header>

    <div id="pageMessage" class="athlete-message"></div>

    <section id="empty" class="athlete-card empty hidden">
        Đội hình chưa được thiết lập
    </section>

    <section id="lineupHeader" class="athlete-card hidden"></section>
    <section class="lineup-grid" id="lineup"></section>
</section>
