<section class="account-password-page" data-password-api="<?= e(url('/api/account/password')) ?>">
    <div class="account-password-hero">
        <div>
            <span class="account-password-kicker" data-i18n-text>Bảo mật tài khoản</span>
            <h2 data-i18n-text>Đổi mật khẩu đăng nhập</h2>
            <p data-i18n-text>Cập nhật mật khẩu định kỳ giúp bảo vệ tài khoản và dữ liệu nghiệp vụ trong hệ thống VTMS.</p>
        </div>
        <div class="account-password-badge">
            <strong data-i18n-text>Đang hoạt động</strong>
            <span data-i18n-text>Tài khoản hiện tại</span>
        </div>
    </div>

    <div class="account-password-grid">
        <form class="account-password-card" id="passwordForm" autocomplete="off">
            <div class="account-password-card__head">
                <h3 data-i18n-text>Thông tin đổi mật khẩu</h3>
                <p data-i18n-text>Nhập mật khẩu hiện tại và mật khẩu mới để xác nhận thay đổi.</p>
            </div>

            <label class="account-password-field" for="currentPassword">
                <span data-i18n-text>Mật khẩu hiện tại</span>
                <input id="currentPassword" name="current_password" type="password" autocomplete="current-password" required>
            </label>

            <label class="account-password-field" for="newPassword">
                <span data-i18n-text>Mật khẩu mới</span>
                <input id="newPassword" name="new_password" type="password" autocomplete="new-password" minlength="6" maxlength="72" required>
            </label>

            <label class="account-password-field" for="confirmPassword">
                <span data-i18n-text>Xác nhận mật khẩu mới</span>
                <input id="confirmPassword" name="new_password_confirmation" type="password" autocomplete="new-password" minlength="6" maxlength="72" required>
            </label>

            <div id="passwordAlert" class="account-password-alert hidden" role="alert"></div>

            <div class="account-password-actions">
                <button class="account-password-submit" id="btnPasswordSubmit" type="submit" data-i18n-text>Cập nhật mật khẩu</button>
            </div>
        </form>

        <aside class="account-password-policy">
            <h3 data-i18n-text>Quy tắc bảo mật</h3>
            <ul>
                <li data-i18n-text>Mật khẩu mới phải có ít nhất 6 ký tự.</li>
                <li data-i18n-text>Mật khẩu mới phải khác mật khẩu hiện tại.</li>
                <li data-i18n-text>Hệ thống lưu lịch sử mật khẩu cũ và ghi nhật ký nghiệp vụ.</li>
            </ul>
        </aside>
    </div>
</section>
