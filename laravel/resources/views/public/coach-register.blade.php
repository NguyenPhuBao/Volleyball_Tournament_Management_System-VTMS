@extends('layouts.main')

@section('content')
    <section
        class="coach-page coach-register"
        data-register-api="{{ url('/api/coach/register') }}"
        data-options-api="{{ url('/api/coach/register/options') }}"
    >
        <header class="coach-topbar">
            <div>
                <p class="eyebrow">HUAN LUYEN VIEN</p>
                <h1>Dang ky tai khoan Huan luyen vien</h1>
                <p class="sub">Dien day du thong tin de gui yeu cau dang ky tai khoan HLV.</p>
            </div>
        </header>

        <main class="coach-card">
            <form id="registerForm">
                <div class="coach-grid">
                    <div>
                        <label for="fullname">Ho va ten *</label>
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
                        <label for="phone">So dien thoai *</label>
                        <input id="phone" required />
                    </div>
                    <div>
                        <label for="password">Mat khau *</label>
                        <input id="password" type="password" required />
                    </div>
                    <div>
                        <label for="passwordConfirmation">Xac nhan mat khau *</label>
                        <input id="passwordConfirmation" type="password" required />
                    </div>
                    <div>
                        <label for="dob">Ngay sinh *</label>
                        <input id="dob" type="date" required />
                    </div>
                    <div>
                        <label for="gender">Gioi tinh *</label>
                        <select id="gender" required>
                            <option value="">-- Chon --</option>
                            <option value="NAM">Nam</option>
                            <option value="NU">Nu</option>
                            <option value="KHAC">Khac</option>
                        </select>
                    </div>
                    <div>
                        <label for="workRegion">Khu vuc cong tac *</label>
                        <select id="workRegion" required>
                            <option value="">Dang tai khu vuc...</option>
                        </select>
                    </div>
                    <div>
                        <label for="workUnit">Don vi / Cau lac bo cong tac *</label>
                        <input id="workUnit" required />
                    </div>
                    <div>
                        <label for="degree">Trinh do / Bang cap *</label>
                        <input id="degree" required />
                    </div>
                    <div>
                        <label for="experience">So nam kinh nghiem *</label>
                        <input id="experience" type="number" min="0" required />
                    </div>
                    <div>
                        <label for="identityNumber">CCCD</label>
                        <input id="identityNumber" />
                    </div>
                    <div class="colspan">
                        <label for="note">Ghi chu</label>
                        <textarea id="note" rows="4"></textarea>
                    </div>
                </div>

                <div id="formAlert" class="coach-alert hidden"></div>
                <div id="formMessage" class="coach-message"></div>

                <div class="coach-actions">
                    <button type="submit" class="btn primary">Dang ky</button>
                </div>

                <p class="hint">Sau khi gui, yeu cau se o trang thai Cho xac nhan va duoc Ban to chuc xet duyet.</p>
            </form>
        </main>
    </section>
@endsection
