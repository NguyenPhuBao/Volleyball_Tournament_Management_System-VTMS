<section
    class="coach-page coach-register referee-register"
    data-register-api="<?= e(url('/api/referee/register')) ?>"
    data-options-api="<?= e(url('/api/referee/register/options')) ?>"
>
    <header class="coach-topbar">
        <div>
            <p class="eyebrow">TRỌNG TÀI</p>
            <h1>Đăng ký tài khoản Trọng tài</h1>
            <p class="sub">Điền đầy đủ thông tin để gửi yêu cầu đăng ký tài khoản trọng tài.</p>
        </div>
    </header>

    <main class="coach-card">
        <form id="refereeRegisterForm">
            <div class="coach-grid">
                <div>
                    <label for="fullname">Họ và tên *</label>
                    <input id="fullname" required />
                </div>
                <div>
                    <label for="username">Username *</label>
                    <input id="username" required />
                </div>
                <div>
                    <label for="email">Email *</label>
                    <input id="email" type="email" required />
                </div>
                <div>
                    <label for="phone">Số điện thoại *</label>
                    <input id="phone" required />
                </div>
                <div>
                    <label for="password">Mật khẩu *</label>
                    <input id="password" type="password" required />
                </div>
                <div>
                    <label for="passwordConfirmation">Xác nhận mật khẩu *</label>
                    <input id="passwordConfirmation" type="password" required />
                </div>
                <div>
                    <label for="dob">Ngày sinh *</label>
                    <input id="dob" type="date" required />
                </div>
                <div>
                    <label for="gender">Giới tính *</label>
                    <select id="gender" required>
                        <option value="">-- Chọn --</option>
                        <option value="NAM">Nam</option>
                        <option value="NU">Nữ</option>
                        <option value="KHAC">Khác</option>
                    </select>
                </div>
                <div>
                    <label for="level">Cấp bậc trọng tài *</label>
                    <select id="level" required>
                        <option value="">Đang tải cấp bậc...</option>
                    </select>
                </div>
                <div>
                    <label for="experience">Số năm kinh nghiệm *</label>
                    <input id="experience" type="number" min="0" required />
                </div>
                <div>
                    <label for="identityNumber">CCCD (tuỳ chọn)</label>
                    <input id="identityNumber" />
                </div>
                <div class="colspan">
                    <label for="note">Ghi chú</label>
                    <textarea id="note" rows="4"></textarea>
                </div>
            </div>

            <div id="formAlert" class="coach-alert hidden"></div>
            <div id="formMessage" class="coach-message"></div>

            <div class="coach-actions">
                <button type="submit" class="btn primary">Đăng ký</button>
            </div>

            <p class="hint">Sau khi gửi, yêu cầu sẽ ở trạng thái Chờ xác nhận và được BTC Liên đoàn bóng chuyền xét duyệt.</p>
        </form>
    </main>
</section>
