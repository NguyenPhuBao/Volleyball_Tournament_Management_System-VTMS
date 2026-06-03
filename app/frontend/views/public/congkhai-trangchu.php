<section class="hero">
    <div>
        <p class="eyebrow">Volleyball Tournament Management System</p>
        <h1>Hệ thống quản lý giải đấu bóng chuyền</h1>
        <p class="lead">Nền tảng hỗ trợ Liên đoàn, Ban tổ chức, huấn luyện viên, trọng tài và vận động viên phối hợp trong toàn bộ mùa giải bóng chuyền.</p>
        <div class="actions">
            <?php if ($user): ?>
                <a class="button primary" href="<?= e(url('/dashboard')) ?>">Vào dashboard</a>
            <?php else: ?>
                <a class="button primary" href="<?= e(url('/login')) ?>">Đăng nhập</a>
                <a class="button" href="<?= e(url('/huan-luyen-vien/dang-ky')) ?>">Đăng ký HLV</a>
                <a class="button" href="<?= e(url('/trong-tai/dang-ky')) ?>">Đăng ký trọng tài</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="grid">
    <article class="card">
        <h2>Quản lý giải đấu</h2>
        <p>Tạo giải đấu, thiết lập phạm vi tổ chức, thời gian thi đấu, sân đấu, lịch thi đấu và trạng thái đăng ký đội bóng.</p>
    </article>
    <article class="card">
        <h2>Đội bóng và vận động viên</h2>
        <p>Huấn luyện viên quản lý hồ sơ đội, danh sách thành viên, đội hình thi đấu và yêu cầu xác nhận vận động viên.</p>
    </article>
    <article class="card">
        <h2>Trọng tài và kết quả</h2>
        <p>Ban tổ chức phân công trọng tài, theo dõi trận đấu, ghi nhận kết quả, xử lý khiếu nại và công bố bảng xếp hạng.</p>
    </article>
</section>
