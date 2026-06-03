@extends('layouts.main')

@section('content')
    <section
        class="admin-profile-users"
        data-users-api="{{ url('/api/admin/users') }}"
    >
        <header class="profile-users-topbar">
            <div>
                <p class="eyebrow">ADMIN</p>
                <h1>Ho so nguoi dung</h1>
            </div>
        </header>

        <section class="profile-users-toolbar" aria-label="Bo loc nguoi dung">
            <input id="q" type="text" placeholder="Tim theo ho ten / username / email" />
            <select id="roleFilter">
                <option value="">Tat ca vai tro</option>
                <option value="ADMIN">ADMIN</option>
                <option value="BAN_TO_CHUC">BAN TO CHUC</option>
                <option value="TRONG_TAI">TRONG TAI</option>
                <option value="HUAN_LUYEN_VIEN">HLV</option>
                <option value="VAN_DONG_VIEN">VDV</option>
            </select>
            <select id="statusFilter">
                <option value="">Tat ca trang thai</option>
                <option value="HOAT_DONG">HOAT DONG</option>
                <option value="CHUA_KICH_HOAT">CHUA KICH HOAT</option>
                <option value="CHO_DUYET">CHO DUYET</option>
                <option value="TAM_KHOA">TAM KHOA</option>
                <option value="DA_HUY">DA HUY</option>
            </select>
        </section>

        <div class="profile-table-wrap">
            <table class="profile-users-table">
                <thead>
                    <tr>
                        <th>Ho ten</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Vai tro</th>
                        <th>Trang thai</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr>
                        <td colspan="6" class="empty">Dang tai du lieu...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="profile-message" id="pageMessage" role="status"></p>
    </section>

    <div class="profile-modal hidden" id="detailModal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
            <h2 id="detailTitle">Chi tiet nguoi dung</h2>

            <h3>Thong tin tai khoan</h3>
            <label for="m_username">Username</label>
            <input id="m_username" disabled />

            <label for="m_email">Email</label>
            <input id="m_email" type="email" />

            <label for="m_role">Vai tro</label>
            <input id="m_role" disabled />

            <label for="m_status">Trang thai</label>
            <select id="m_status">
                <option value="HOAT_DONG">HOAT DONG</option>
                <option value="CHUA_KICH_HOAT">CHUA KICH HOAT</option>
                <option value="CHO_DUYET">CHO DUYET</option>
                <option value="TAM_KHOA">TAM KHOA</option>
                <option value="DA_HUY">DA HUY</option>
            </select>

            <h3>Ho so ca nhan</h3>
            <label for="m_hodem">Ho dem</label>
            <input id="m_hodem" />

            <label for="m_ten">Ten</label>
            <input id="m_ten" />

            <label for="m_gioitinh">Gioi tinh</label>
            <select id="m_gioitinh">
                <option value="NAM">NAM</option>
                <option value="NU">NU</option>
                <option value="KHAC">KHAC</option>
            </select>

            <label for="m_ngaysinh">Ngay sinh</label>
            <input type="date" id="m_ngaysinh" />

            <label for="m_quequan">Que quan</label>
            <input id="m_quequan" />

            <p class="profile-message" id="modalMessage" role="alert"></p>

            <div class="modal-actions">
                <button id="btnClose" class="btn" type="button">Dong</button>
                <button id="btnSave" class="btn primary" type="button">Luu thay doi</button>
            </div>
        </div>
    </div>
@endsection
