<section class="error-page">
    <h1>403</h1>
    <p>Bạn không có quyền truy cập chức năng này.</p>
    <?php if (!empty($requiredRoles)): ?>
        <p class="hint">Vai tro yeu cau: <?= e(implode(', ', $requiredRoles)) ?></p>
    <?php endif; ?>
    <a class="button" href="<?= e(url('/dashboard')) ?>">Quay lai dashboard</a>
</section>
