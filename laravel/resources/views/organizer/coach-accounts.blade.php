@extends('layouts.main')

@section('content')
    <section
        class="organizer-coach-accounts"
        data-accounts-api="{{ url('/api/organizer/coach-accounts') }}"
    >
        <header class="accounts-topbar">
            <div>
                <p class="eyebrow">BAN TO CHUC</p>
                <h1>Duyet tai khoan HLV</h1>
                <p class="sub">Quan ly va xet duyet cac tai khoan dang ky voi vai tro Huan luyen vien.</p>
            </div>
        </header>

        <section class="accounts-toolbar" aria-label="Bo loc tai khoan">
            <input id="q" type="text" placeholder="Tim theo username / email / ten">

            <select id="statusFilter">
                <option value="">Tat ca trang thai</option>
                <option value="CHO_DUYET">Cho duyet</option>
                <option value="HOAT_DONG">Hoat dong</option>
                <option value="DA_HUY">Da huy</option>
                <option value="CHUA_KICH_HOAT">Chua kich hoat</option>
                <option value="TAM_KHOA">Tam khoa</option>
            </select>

            <button id="btnRefresh" class="btn" type="button">Lam moi</button>
        </section>

        <section class="accounts-stats" aria-label="Thong ke tai khoan">
            <div class="stat"><span id="sPending">0</span><small>Cho duyet</small></div>
            <div class="stat"><span id="sActive">0</span><small>Hoat dong</small></div>
            <div class="stat"><span id="sCanceled">0</span><small>Da huy</small></div>
        </section>

        <div class="accounts-table-wrap">
            <table class="accounts-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Ho ten</th>
                        <th>Email</th>
                        <th>SDT</th>
                        <th>Trang thai</th>
                        <th>Ngay dang ky</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr>
                        <td colspan="8" class="empty">Dang tai du lieu...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="accounts-message" id="pageMessage" role="status"></p>
    </section>

    <div class="accounts-modal hidden" id="detailModal" aria-hidden="true">
        <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
            <div class="modal-head">
                <div>
                    <h2 id="m_title">Chi tiet tai khoan HLV</h2>
                    <p class="sub" id="m_sub">-</p>
                </div>
                <button id="m_close" class="icon" type="button" aria-label="Dong">x</button>
            </div>

            <div class="accounts-grid">
                <div>
                    <label for="m_id">ID Tai khoan</label>
                    <input id="m_id" disabled>
                </div>
                <div>
                    <label for="m_status">Trang thai tai khoan</label>
                    <input id="m_status" disabled>
                </div>

                <div>
                    <label for="m_username">Username</label>
                    <input id="m_username" disabled>
                </div>
                <div>
                    <label for="m_email">Email</label>
                    <input id="m_email" disabled>
                </div>

                <div>
                    <label for="m_phone">SDT</label>
                    <input id="m_phone" disabled>
                </div>
                <div>
                    <label for="m_name">Ho ten</label>
                    <input id="m_name" disabled>
                </div>

                <div>
                    <label for="m_created">Ngay tao</label>
                    <input id="m_created" disabled>
                </div>
                <div>
                    <label for="m_updated">Ngay cap nhat</label>
                    <input id="m_updated" disabled>
                </div>
            </div>

            <div id="m_alert" class="accounts-alert hidden"></div>

            <div class="modal-actions">
                <button id="m_reject" class="btn danger" type="button">Tu choi</button>
                <button id="m_approve" class="btn primary" type="button">Duyet tai khoan</button>
                <button id="m_closeBtn" class="btn" type="button">Dong</button>
            </div>
        </div>
    </div>
@endsection
