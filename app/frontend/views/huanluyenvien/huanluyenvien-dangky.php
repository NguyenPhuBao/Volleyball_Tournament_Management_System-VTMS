<section
    class="coach-page coach-register"
    data-register-api="<?= e(url('/api/coach/register')) ?>"
    data-options-api="<?= e(url('/api/coach/register/options')) ?>"
>
    <header class="coach-topbar">
        <div>
            <p class="eyebrow">HUẤN LUYỆN VIÊN</p>
            <h1>Đăng ký tài khoản Huấn luyện viên</h1>
            <p class="sub">Điền đầy đủ thông tin để gửi yêu cầu đăng ký tài khoản HLV.</p>
        </div>
    </header>

    <main class="coach-card">
        <form id="registerForm">
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
                    <label for="workRegion">Khu vực công tác *</label>
                    <select id="workRegion" required>
                        <option value="">Đang tải khu vực...</option>
                    </select>
                </div>
                <div>
                    <label for="workUnit">Đơn vị / Câu lạc bộ công tác *</label>
                    <input id="workUnit" required />
                </div>
                <div>
                    <label for="degree">Trình độ / Bằng cấp *</label>
                    <input id="degree" required />
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

            <p class="hint">Sau khi gửi, yêu cầu sẽ ở trạng thái Chờ xác nhận và được Ban tổ chức xét duyệt.</p>
        </form>
    </main>
</section>
