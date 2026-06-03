<main class="auth">
    <section class="auth__left" aria-hidden="true">
        <div class="brand">
            <div class="brand__logo">VT</div>
            <div class="brand__name">
                <h1>VTMS</h1>
                <p>Volleyball Tournament Management System</p>
            </div>
        </div>

        <div class="hero">
            <h2>Quản lý giải đấu bóng chuyền hiệu quả</h2>
            <p>
                Đăng nhập để quản trị giải đấu, đội bóng, lịch thi đấu, trọng tài, kết quả và xếp hạng.
            </p>
            <ul class="hero__list">
                <li>Phân quyền theo vai trò</li>
                <li>Quản lý lịch & sân đấu</li>
                <li>Công bố kết quả & bảng xếp hạng</li>
            </ul>
        </div>

        <footer class="auth__footer">
            <span>&copy; <span id="year"></span> VTMS</span>
        </footer>
    </section>

    <section class="auth__right">
        <div class="card" role="region" aria-label="Biểu mẫu đăng nhập">
            <header class="card__header">
                <h2>Đăng nhập</h2>
                <p>Vui lòng nhập thông tin tài khoản của bạn.</p>
            </header>

            <form id="loginForm" class="form" method="post" action="<?= e(url('/login')) ?>" novalidate>
                <?= csrf_field() ?>

                <div class="form__group">
                    <label for="identifier">Tài khoản hoặc Email</label>
                    <input
                        id="identifier"
                        name="identifier"
                        type="text"
                        inputmode="email"
                        autocomplete="username"
                        placeholder="vd: admin01 hoặc admin@vtms.local"
                        required
                    >
                    <small class="form__hint">Bạn có thể dùng username hoặc email.</small>
                </div>

                <div class="form__group">
                    <label for="password">Mật khẩu</label>
                    <div class="input__wrap">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Nhập mật khẩu"
                            required
                            minlength="6"
                        >
                        <button type="button" class="iconbtn" id="togglePw" aria-label="Hiện/ẩn mật khẩu">
                            👁
                        </button>
                    </div>
                </div>

                <div class="form__row">
                    <label class="checkbox">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <span>Ghi nhớ đăng nhập</span>
                    </label>
                    <a class="link" href="<?= e(url('/forgot-password')) ?>">Quên mật khẩu?</a>
                </div>

                <div
                    id="alert"
                    class="alert"
                    role="alert"
                    aria-live="polite"
                    data-server-error="<?= e($error ?? '') ?>"
                    <?= empty($error) ? 'hidden' : '' ?>
                ><?= e($error ?? '') ?></div>
                <?php unset($_SESSION['login_error']); ?>

                <button id="submitBtn" class="btn btn--primary" type="submit">
                    <span class="btn__text">Đăng nhập</span>
                    <span class="btn__loader" aria-hidden="true"></span>
                </button>

                <div class="divider"><span>hoặc</span></div>

                <button class="btn btn--ghost" type="button" id="demoBtn">
                    Dùng tài khoản mẫu
                </button>

                <p class="note">
                    Bằng việc đăng nhập, bạn đồng ý với <a class="link" href="<?= e(url('/terms')) ?>">Điều khoản</a> và
                    <a class="link" href="<?= e(url('/privacy')) ?>">Chính sách</a>.
                </p>
            </form>
        </div>

        <p class="meta">
            Chưa có tài khoản? <a class="link" href="<?= e(url('/huan-luyen-vien/dang-ky')) ?>">Đăng ký HLV</a>
            hoặc <a class="link" href="<?= e(url('/trong-tai/dang-ky')) ?>">Đăng ký trọng tài</a>.
        </p>
    </section>
</main>
