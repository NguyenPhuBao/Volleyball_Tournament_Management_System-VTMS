<section
    class="organizer-standings"
    data-standings-api="<?= e(url('/api/organizer/standings')) ?>"
    data-tournaments-api="<?= e(url('/api/organizer/standings/tournaments')) ?>"
>
    <header class="standings-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Quản lý xếp hạng</h1>
            <p class="sub">Chọn giải đấu, tạo bảng xếp hạng từ kết quả đã công bố và công bố BXH.</p>
        </div>
    </header>

    <section class="standings-toolbar" aria-label="Tạo và công bố bảng xếp hạng">
        <select id="tournamentSelect">
            <option value="">Chọn giải đấu...</option>
        </select>

        <input id="rankName" type="text" placeholder="Tên bảng xếp hạng (VD: BXH vòng bảng)" />

        <button id="btnGenerate" class="btn primary" type="button" disabled>Tạo xếp hạng</button>
        <button id="btnPublish" class="btn" type="button" disabled>Công bố bảng xếp hạng</button>
        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section class="standings-summary" aria-label="Thông tin bảng xếp hạng">
        <div class="summary-card">
            <span class="label">Mã BXH</span>
            <strong id="bxhId">-</strong>
        </div>
        <div class="summary-card">
            <span class="label">Trạng thái</span>
            <strong id="bxhStatus">-</strong>
        </div>
        <div class="summary-card">
            <span class="label">Ngày tạo</span>
            <strong id="bxhCreated">-</strong>
        </div>
        <div class="summary-card">
            <span class="label">Ngày công bố</span>
            <strong id="bxhPublished">-</strong>
        </div>
    </section>

    <div id="pageAlert" class="standings-alert hidden" role="status"></div>

    <section class="standings-panel">
        <div class="panel-head">
            <div>
                <h2>Bảng xếp hạng</h2>
                <p class="sub">Sắp xếp theo điểm, số trận thắng, hiệu số set, set thắng và số trận thua.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="standings-table">
                <thead>
                    <tr>
                        <th>Hạng</th>
                        <th>Đội bóng</th>
                        <th>Số trận</th>
                        <th>Thắng</th>
                        <th>Thua</th>
                        <th>Set thắng</th>
                        <th>Set thua</th>
                        <th>Điểm</th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr>
                        <td colspan="8" class="empty">Chọn giải đấu để xem hoặc tạo bảng xếp hạng.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="hint">Chỉ tạo BXH khi có kết quả đã công bố và không còn trận đã kết thúc chưa công bố kết quả.</p>
    </section>

    <section class="standings-panel">
        <div class="panel-head">
            <div>
                <h2>Nhánh loại trực tiếp</h2>
                <p class="sub">Hệ thống chọn top 8 sau vòng sơ bộ; hạng 9 và 10 bị loại theo điều lệ.</p>
            </div>
        </div>

        <div id="knockoutPlan" class="knockout-plan">
            Chưa có dữ liệu nhánh đấu.
        </div>
    </section>
</section>
