@extends('layouts.main')

@section('content')
    <section
        class="admin-users"
        data-accounts-api="{{ url('/api/admin/accounts') }}"
        data-roles-api="{{ url('/api/admin/roles') }}"
    >
        <header class="users-topbar">
            <div>
                <p class="eyebrow">ADMIN</p>
                <h1>Quan tri tai khoan</h1>
            </div>
            <button class="btn primary" id="btnAdd" type="button">+ Them tai khoan</button>
        </header>

        <section class="users-toolbar" aria-label="Bo loc tai khoan">
            <input type="text" id="searchInput" placeholder="Tim theo username / email..." />
            <select id="filterRole">
                <option value="">-- Tat ca vai tro --</option>
                <option value="ADMIN">ADMIN</option>
                <option value="BAN_TO_CHUC">BAN TO CHUC</option>
                <option value="TRONG_TAI">TRONG TAI</option>
                <option value="HUAN_LUYEN_VIEN">HLV</option>
                <option value="VAN_DONG_VIEN">VDV</option>
            </select>
        </section>

        <div class="table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Vai tro</th>
                        <th>Trang thai</th>
                        <th>Hanh dong</th>
                    </tr>
                </thead>
                <tbody id="userTable">
                    <tr>
                        <td colspan="5" class="empty">Dang tai du lieu...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="form-message" id="pageMessage" role="status"></p>
    </section>

    <div class="user-modal hidden" id="userModal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <h2 id="modalTitle">Them tai khoan</h2>

            <label for="username">Username</label>
            <input id="username" autocomplete="username" />

            <label for="email">Email</label>
            <input id="email" type="email" autocomplete="email" />

            <label for="password">Mat khau</label>
            <input id="password" type="password" autocomplete="new-password" />
            <p class="field-hint" id="passwordHint">De trong neu khong doi mat khau.</p>

            <label for="role">Vai tro</label>
            <select id="role">
                <option value="ADMIN">ADMIN</option>
                <option value="BAN_TO_CHUC">BAN TO CHUC</option>
                <option value="TRONG_TAI">TRONG TAI</option>
                <option value="HUAN_LUYEN_VIEN">HLV</option>
                <option value="VAN_DONG_VIEN">VDV</option>
            </select>

            <label for="status">Trang thai</label>
            <select id="status">
                <option value="HOAT_DONG">Hoat dong</option>
                <option value="CHO_DUYET">Cho duyet</option>
                <option value="TAM_KHOA">Tam khoa</option>
                <option value="DA_HUY">Da huy</option>
            </select>

            <p class="form-message" id="modalMessage" role="alert"></p>

            <div class="modal-actions">
                <button class="btn" id="btnCancel" type="button">Huy</button>
                <button class="btn primary" id="btnSave" type="button">Luu</button>
            </div>
        </div>
    </div>
@endsection
