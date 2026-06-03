<section
    class="admin-logs"
    data-logs-api="<?= e(url('/api/admin/system-logs')) ?>"
    data-options-api="<?= e(url('/api/admin/system-logs/options')) ?>"
>
    <header class="logs-topbar">
        <div>
            <p class="eyebrow">ADMIN</p>
            <h1>Nhật ký hệ thống</h1>
        </div>
    </header>

    <section class="logs-toolbar" aria-label="Bộ lọc nhật ký hệ thống">
        <input id="q" type="text" placeholder="Tìm theo hành động / bảng / ghi chú" />

        <select id="userFilter">
            <option value="">Tất cả người dùng</option>
        </select>

        <input type="date" id="fromDate" aria-label="Từ ngày" />
        <input type="date" id="toDate" aria-label="Đến ngày" />
        <button class="btn" id="btnReset" type="button">Xóa lọc</button>
    </section>

    <div class="logs-table-wrap">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Người thực hiện</th>
                    <th>Hành động</th>
                    <th>Bảng tác động</th>
                    <th>ID đối tượng</th>
                    <th>IP</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr>
                    <td colspan="7" class="empty">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <footer class="logs-footer">
        <p class="logs-message" id="pageMessage" role="status"></p>
        <div class="logs-pagination" aria-label="Phân trang nhật ký">
            <span id="pageInfo">Trang 1 / 1</span>
            <button class="btn" id="prevPage" type="button">Trước</button>
            <button class="btn" id="nextPage" type="button">Sau</button>
        </div>
    </footer>
</section>
