@extends('layouts.main')

@section('content')
    <section
        class="admin-logs"
        data-logs-api="{{ url('/api/admin/system-logs') }}"
        data-options-api="{{ url('/api/admin/system-logs/options') }}"
    >
        <header class="logs-topbar">
            <div>
                <p class="eyebrow">ADMIN</p>
                <h1>Nhat ky he thong</h1>
            </div>
        </header>

        <section class="logs-toolbar" aria-label="Bo loc nhat ky he thong">
            <input id="q" type="text" placeholder="Tim theo hanh dong / bang / ghi chu" />
            <select id="userFilter">
                <option value="">Tat ca nguoi dung</option>
            </select>
            <input type="date" id="fromDate" aria-label="Tu ngay" />
            <input type="date" id="toDate" aria-label="Den ngay" />
            <button class="btn" id="btnReset" type="button">Xoa loc</button>
        </section>

        <div class="logs-table-wrap">
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>Thoi gian</th>
                        <th>Nguoi thuc hien</th>
                        <th>Hanh dong</th>
                        <th>Bang tac dong</th>
                        <th>ID doi tuong</th>
                        <th>IP</th>
                        <th>Ghi chu</th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr>
                        <td colspan="7" class="empty">Dang tai du lieu...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <footer class="logs-footer">
            <p class="logs-message" id="pageMessage" role="status"></p>
            <div class="logs-pagination" aria-label="Phan trang nhat ky">
                <span id="pageInfo">Trang 1 / 1</span>
                <button class="btn" id="prevPage" type="button">Truoc</button>
                <button class="btn" id="nextPage" type="button">Sau</button>
            </div>
        </footer>
    </section>
@endsection
