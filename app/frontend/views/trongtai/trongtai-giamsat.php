<section
    class="referee-supervise"
    data-supervision-api="<?= e(url('/api/trongtai/matches')) ?>"
    data-assignments-api="<?= e(url('/api/trongtai/assignments')) ?>"
    data-schedule-url="<?= e(url('/trong-tai/lich-phan-cong')) ?>"
>
    <header class="supervise-topbar">
        <div>
            <p class="eyebrow">TRỌNG TÀI</p>
            <h1>Giám sát trận đấu</h1>
            <p class="sub">Xác nhận tham gia, xác nhận tổ trọng tài, bắt đầu, tạm dừng, tiếp tục, kết thúc và ghi kết quả.</p>
        </div>
        <a class="btn" href="<?= e(url('/trong-tai/lich-phan-cong')) ?>">Về lịch phân công</a>
    </header>

    <section class="supervise-card">
        <div class="card-head">
            <div>
                <h2 id="matchTitle">Chưa tải trận đấu</h2>
                <p class="sub" id="matchSub">—</p>
            </div>
            <div class="pill" id="matchState">—</div>
        </div>

        <div class="supervise-grid">
            <div>
                <label for="m_matchId">Mã trận</label>
                <input id="m_matchId" disabled />
            </div>
            <div>
                <label for="m_tournament">Giải đấu</label>
                <input id="m_tournament" disabled />
            </div>
            <div>
                <label for="m_venue">Sân đấu</label>
                <input id="m_venue" disabled />
            </div>
            <div>
                <label for="m_teamOne">Đội số 1</label>
                <input id="m_teamOne" disabled />
            </div>
            <div>
                <label for="m_round">Vòng đấu</label>
                <input id="m_round" disabled />
            </div>
            <div>
                <label for="m_teamTwo">Đội số 2</label>
                <input id="m_teamTwo" disabled />
            </div>
            <div>
                <label for="m_start">Bắt đầu</label>
                <input id="m_start" disabled />
            </div>
            <div>
                <label for="m_end">Kết thúc</label>
                <input id="m_end" disabled />
            </div>
        </div>

        <div id="pageAlert" class="supervise-alert hidden"></div>

        <div class="supervise-actions">
            <button id="btnJoin" class="btn primary" type="button">Xác nhận tham gia trận đấu</button>
            <button id="btnPickRefs" class="btn" type="button" disabled>Chọn trọng tài tham gia</button>
            <button id="btnStart" class="btn primary" type="button" disabled>Bắt đầu</button>
            <button id="btnPause" class="btn" type="button" disabled>Tạm dừng</button>
            <button id="btnResume" class="btn" type="button" disabled>Tiếp tục</button>
            <button id="btnEnd" class="btn danger" type="button" disabled>Kết thúc</button>
        </div>
    </section>

    <section class="supervise-card hidden" id="resultCard">
        <div class="card-head">
            <div>
                <h2>Ghi nhận kết quả</h2>
                <p class="sub">Nhập điểm theo Bo5. Kết quả hợp lệ là 3-0, 3-1 hoặc 3-2; điểm mỗi set không được hòa.</p>
            </div>
            <div class="pill" id="resultState">Kết quả: —</div>
        </div>

        <div class="sets" id="sets"></div>

        <div class="row">
            <button id="btnAddSet" class="btn" type="button">+ Thêm set</button>
            <button id="btnRemoveSet" class="btn" type="button">- Bớt set</button>
        </div>

        <div class="supervise-grid">
            <div>
                <label for="setScore">Tỷ số set</label>
                <input id="setScore" disabled />
            </div>
            <div>
                <label for="winner">Đội thắng</label>
                <input id="winner" disabled />
            </div>
            <div class="colspan">
                <label for="resultNote">Ghi chú</label>
                <textarea id="resultNote" rows="3" placeholder="VD: Kết quả theo biên bản trọng tài..."></textarea>
            </div>
        </div>

    </section>
</section>

<div class="supervision-modal hidden" id="refsModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="refsTitle">
        <div class="modal-head">
            <div>
                <h2 id="refsTitle">Danh sách trọng tài tham gia trận</h2>
                <p class="sub">Chọn các trọng tài thực tế có mặt để tham gia điều hành hoặc giám sát.</p>
            </div>
            <button class="icon" id="r_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="table-wrap">
            <table class="supervise-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Trọng tài</th>
                        <th>Vai trò</th>
                        <th>Trạng thái tham gia</th>
                        <th>Ngày phân công</th>
                    </tr>
                </thead>
                <tbody id="r_tbody">
                    <tr>
                        <td colspan="5" class="empty">Chưa có dữ liệu.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="r_alert" class="supervise-alert hidden"></div>

        <div class="modal-actions">
            <button id="r_cancel" class="btn" type="button">Hủy</button>
            <button id="r_confirm" class="btn primary" type="button">Xác nhận</button>
        </div>
    </div>
</div>
