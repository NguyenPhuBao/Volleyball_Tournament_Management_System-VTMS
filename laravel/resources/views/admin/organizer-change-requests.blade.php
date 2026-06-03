@extends('layouts.main')

@section('content')
    <section
        class="admin-approvals"
        data-approvals-api="{{ url('/api/admin/organizer-change-requests') }}"
    >
        <header class="approvals-topbar">
            <div>
                <p class="eyebrow">ADMIN</p>
                <h1>Xac nhan thong tin ban to chuc</h1>
                <p class="sub">Duyet hoac tu choi cac yeu cau cap nhat ho so cua ban to chuc.</p>
            </div>
        </header>

        <section class="approvals-toolbar" aria-label="Bo loc yeu cau xac nhan">
            <input id="q" type="text" placeholder="Tim theo ten / don vi / truong cap nhat" />
            <select id="statusFilter">
                <option value="">Tat ca trang thai</option>
                <option value="CHO_DUYET">Cho duyet</option>
                <option value="DA_DUYET">Da duyet</option>
                <option value="TU_CHOI">Tu choi</option>
            </select>
            <input type="date" id="fromDate" aria-label="Tu ngay" />
            <input type="date" id="toDate" aria-label="Den ngay" />
            <button id="btnRefresh" class="btn" type="button">Lam moi</button>
        </section>

        <section class="approval-stats" aria-label="Thong ke yeu cau xac nhan">
            <div class="stat"><span id="sPending">0</span><small>Cho duyet</small></div>
            <div class="stat"><span id="sApproved">0</span><small>Da duyet</small></div>
            <div class="stat"><span id="sRejected">0</span><small>Tu choi</small></div>
        </section>

        <div class="approvals-table-wrap">
            <table class="approvals-table">
                <thead>
                    <tr>
                        <th>Ma YC</th>
                        <th>Nguoi gui</th>
                        <th>Don vi</th>
                        <th>Bang</th>
                        <th>Truong</th>
                        <th>Gia tri cu</th>
                        <th>Gia tri moi</th>
                        <th>Trang thai</th>
                        <th>Ngay gui</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr>
                        <td colspan="10" class="empty">Dang tai du lieu...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="approval-message" id="pageMessage" role="status"></p>
    </section>

    <div class="approval-modal hidden" id="detailModal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
            <div class="modal-head">
                <h2 id="detailTitle">Chi tiet yeu cau</h2>
                <button id="btnClose" class="icon" type="button" aria-label="Dong">x</button>
            </div>

            <div class="approval-grid">
                <div>
                    <label for="m_id">Ma yeu cau</label>
                    <input id="m_id" disabled />
                </div>
                <div>
                    <label for="m_status">Trang thai</label>
                    <input id="m_status" disabled />
                </div>
                <div>
                    <label for="m_sender">Nguoi gui</label>
                    <input id="m_sender" disabled />
                </div>
                <div>
                    <label for="m_donvi">Don vi</label>
                    <input id="m_donvi" disabled />
                </div>
            </div>

            <hr />

            <div class="approval-grid">
                <div>
                    <label for="m_table">Bang lien quan</label>
                    <input id="m_table" disabled />
                </div>
                <div>
                    <label for="m_field">Truong cap nhat</label>
                    <input id="m_field" disabled />
                </div>
                <div class="colspan">
                    <label for="m_old">Gia tri cu</label>
                    <textarea id="m_old" rows="3" disabled></textarea>
                </div>
                <div class="colspan">
                    <label for="m_new">Gia tri moi</label>
                    <textarea id="m_new" rows="3" disabled></textarea>
                </div>
                <div class="colspan">
                    <label for="m_reason">Ly do/Ghi chu cua nguoi gui</label>
                    <textarea id="m_reason" rows="2" disabled></textarea>
                </div>
            </div>

            <div id="detailAlert" class="approval-alert hidden"></div>

            <div class="modal-actions">
                <button id="btnReject" class="btn danger" type="button">Tu choi</button>
                <button id="btnApprove" class="btn primary" type="button">Xac nhan</button>
            </div>
        </div>
    </div>

    <div class="approval-modal hidden" id="rejectModal" aria-hidden="true">
        <div class="modal-content small" role="dialog" aria-modal="true" aria-labelledby="rejectTitle">
            <div class="modal-head">
                <h2 id="rejectTitle">Tu choi yeu cau</h2>
                <button id="btnRejectClose" class="icon" type="button" aria-label="Dong">x</button>
            </div>

            <label for="r_note">Ly do tu choi</label>
            <textarea id="r_note" rows="4" placeholder="Nhap ly do..."></textarea>

            <div class="modal-actions">
                <button id="btnRejectCancel" class="btn" type="button">Huy</button>
                <button id="btnRejectConfirm" class="btn danger" type="button">Xac nhan tu choi</button>
            </div>
        </div>
    </div>
@endsection
