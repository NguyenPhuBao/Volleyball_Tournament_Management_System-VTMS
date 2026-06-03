<section class="error-page">
    <h1>404</h1>
    <p>Không tìm thấy đường dẫn <?= e($path ?? '') ?>.</p>
    <a class="button" href="<?= e(url('/')) ?>">Về trang chủ</a>
</section>
