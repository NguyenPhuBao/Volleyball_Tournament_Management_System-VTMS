<section
    class="organizer-schedule"
    data-schedule-tournaments-api="<?= e(url('/api/organizer/schedules/tournaments')) ?>"
    data-tournament-api-base="<?= e(url('/api/organizer/tournaments')) ?>"
>
    <header class="schedule-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Quản lý lịch thi đấu</h1>
            <p class="sub">Xem cấu trúc vòng đấu của các giải chưa bị hủy; sau khi đóng đăng ký có thể tạo bảng, sinh trận và xếp lịch.</p>
        </div>
    </header>

    <div class="schedule-layout">
        <aside class="schedule-sidebar">
            <div class="sidebar-head">
                <h2>Giải đấu</h2>
                <p class="sub">Hiển thị các giải chưa bị hủy do ban tổ chức quản lý.</p>
            </div>

            <div class="sidebar-tools">
                <input id="t_q" type="text" placeholder="Tìm giải..." />
                <button id="t_refresh" class="btn" type="button">Làm mới</button>
            </div>

            <ul id="t_list" class="schedule-list">
                <li class="empty">Đang tải dữ liệu...</li>
            </ul>
        </aside>

        <main class="schedule-main">
            <section class="schedule-card">
                <div class="card-head">
                    <div>
                        <h2 id="tour_name">Chưa chọn giải đấu</h2>
                        <p class="sub" id="tour_sub">Chọn một giải đấu ở cột bên trái.</p>
                    </div>
                    <div class="card-actions">
                        <button id="btnGenerateStandard" class="btn" type="button" disabled>Tạo trận tự động</button>
                    </div>
                </div>

                <div class="schedule-split">
                    <section class="schedule-pane">
                        <div class="pane-head">
                            <h3>Vòng đấu</h3>
                            <div class="pane-tools">
                                <select id="v_round">
                                    <option value="">Tất cả vòng đấu</option>
                                </select>
                            </div>
                        </div>

                        <div id="v_rounds" class="round-strip">
                            <div class="empty">Chưa chọn giải đấu.</div>
                        </div>
                    </section>

                    <section class="schedule-pane">
                        <div class="pane-head">
                            <h3>Bảng đấu</h3>
                            <div class="pane-tools">
                                <button id="btnAddGroup" class="btn primary" type="button" disabled>Thêm bảng đấu</button>
                                <input id="g_q" type="text" placeholder="Tìm bảng..." />
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên bảng</th>
                                        <th>Trạng thái</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="g_tbody">
                                    <tr>
                                        <td colspan="4" class="empty">Chưa chọn giải đấu.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="schedule-pane">
                        <div class="pane-head">
                            <h3>Trận đấu</h3>
                            <div class="pane-tools">
                                <select id="m_group">
                                    <option value="">Tất cả, gồm trận ngoài bảng</option>
                                </select>
                                <button id="btnAddMatch" class="btn primary" type="button" disabled>Thêm trận đấu</button>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Đội 1</th>
                                        <th>Đội 2</th>
                                        <th>Sân</th>
                                        <th>Bắt đầu</th>
                                        <th>Kết thúc</th>
                                        <th>Vòng</th>
                                        <th>Bảng</th>
                                        <th>Trạng thái</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="m_tbody">
                                    <tr>
                                        <td colspan="10" class="empty">Chưa chọn giải đấu.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="hint">Backend kiểm tra đội 1 khác đội 2, thời gian kết thúc lớn hơn bắt đầu, sân không trùng lịch và đội không trùng trận.</p>
                    </section>
                </div>

                <p id="page_alert" class="schedule-message" role="status"></p>
            </section>
        </main>
    </div>
</section>

<div class="schedule-modal hidden" id="groupModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="gm_title">
        <div class="modal-head">
            <h2 id="gm_title">Thêm bảng đấu</h2>
            <button class="icon" id="gm_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="schedule-grid">
            <div>
                <label for="gm_round">Vòng đấu</label>
                <select id="gm_round"></select>
            </div>

            <div>
                <label for="gm_name">Tên bảng</label>
                <input id="gm_name" placeholder="VD: Bảng A" />
            </div>

            <div>
                <label for="gm_status">Trạng thái</label>
                <select id="gm_status">
                    <option value="HOAT_DONG">Hoạt động</option>
                    <option value="DA_KHOA">Đã khóa</option>
                    <option value="DA_XOA">Đã xóa mềm</option>
                </select>
            </div>

            <div>
                <label for="gm_start">Thời gian bắt đầu</label>
                <input id="gm_start" type="date" disabled />
            </div>

            <div>
                <label for="gm_end">Thời gian kết thúc</label>
                <input id="gm_end" type="date" />
            </div>

            <div class="colspan">
                <label for="gm_team_picker">Đội trong bảng</label>
                <select id="gm_team_picker">
                    <option value="">Chọn đội tham gia bảng</option>
                </select>
                <div id="gm_selected_teams" class="selected-team-list"></div>
                <small class="field-hint">Có thể chọn nhiều đội. Các đội đã chọn sẽ hiển thị bên dưới; bấm × để loại khỏi bảng.</small>
            </div>

            <div class="colspan">
                <label for="gm_desc">Mô tả</label>
                <textarea id="gm_desc" rows="3"></textarea>
            </div>
        </div>

        <div id="gm_alert" class="schedule-alert hidden"></div>

        <div class="modal-actions">
            <button class="btn" id="gm_cancel" type="button">Hủy</button>
            <button class="btn danger" id="gm_delete" type="button">Xóa bảng</button>
            <button class="btn primary" id="gm_save" type="button">Lưu</button>
        </div>
    </div>
</div>

<div class="schedule-modal hidden" id="matchModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="mm_title">
        <div class="modal-head">
            <h2 id="mm_title">Thêm trận đấu</h2>
            <button class="icon" id="mm_close" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="schedule-grid">
            <div>
                <label for="mm_group">Bảng đấu tùy chọn</label>
                <select id="mm_group">
                    <option value="">Không thuộc bảng</option>
                </select>
            </div>

            <div>
                <label for="mm_round_select">Vòng đấu</label>
                <select id="mm_round_select"></select>
                <input id="mm_round" type="hidden" />
            </div>

            <div>
                <label for="mm_team1">Đội 1</label>
                <select id="mm_team1"></select>
            </div>

            <div>
                <label for="mm_team2">Đội 2</label>
                <select id="mm_team2"></select>
            </div>

            <div>
                <label for="mm_slot1_source">Nguồn đội 1</label>
                <select id="mm_slot1_source">
                    <option value="TEAM">Đội cụ thể</option>
                    <option value="WINNER">Đội thắng trận trước</option>
                    <option value="LOSER">Đội thua trận trước</option>
                    <option value="SEED">Hạt giống</option>
                    <option value="BYE">Miễn đấu</option>
                </select>
            </div>

            <div>
                <label for="mm_slot2_source">Nguồn đội 2</label>
                <select id="mm_slot2_source">
                    <option value="TEAM">Đội cụ thể</option>
                    <option value="WINNER">Đội thắng trận trước</option>
                    <option value="LOSER">Đội thua trận trước</option>
                    <option value="SEED">Hạt giống</option>
                    <option value="BYE">Miễn đấu</option>
                </select>
            </div>

            <div>
                <label for="mm_slot1_match">Trận nguồn đội 1</label>
                <select id="mm_slot1_match"></select>
            </div>

            <div>
                <label for="mm_slot2_match">Trận nguồn đội 2</label>
                <select id="mm_slot2_match"></select>
            </div>

            <div>
                <label for="mm_slot1_seed">Hạt giống đội 1</label>
                <input id="mm_slot1_seed" type="number" min="1" />
            </div>

            <div>
                <label for="mm_slot2_seed">Hạt giống đội 2</label>
                <input id="mm_slot2_seed" type="number" min="1" />
            </div>

            <div>
                <label for="mm_venue">Sân đấu</label>
                <select id="mm_venue"></select>
            </div>

            <div>
                <label for="mm_status">Trạng thái</label>
                <select id="mm_status">
                    <option value="CHO_DOI_DOI">Chờ đội</option>
                    <option value="CHO_XEP_LICH">Chờ xếp lịch / phân công trọng tài</option>
                    <option value="DA_XEP_LICH">Đã xếp lịch</option>
                    <option value="CHUA_DIEN_RA">Chưa diễn ra</option>
                    <option value="SAP_DIEN_RA">Sắp diễn ra</option>
                    <option value="DANG_DIEN_RA">Đang diễn ra</option>
                    <option value="TAM_DUNG">Tạm dừng</option>
                    <option value="DA_KET_THUC">Đã kết thúc</option>
                    <option value="DA_HUY">Đã hủy</option>
                </select>
            </div>

            <div>
                <label for="mm_start">Thời gian bắt đầu</label>
                <input id="mm_start" type="datetime-local" />
            </div>

            <div>
                <label for="mm_end">Thời gian kết thúc tùy chọn</label>
                <input id="mm_end" type="datetime-local" />
            </div>

            <div class="colspan">
                <div class="inline-head">
                    <label>Trọng tài được phân công</label>
                    <button id="mm_add_referee" class="btn" type="button">+ Thêm trọng tài</button>
                </div>
                <div id="mm_referees" class="referee-list"></div>
                <small class="field-hint">Một trận có thể có nhiều trọng tài, mỗi trọng tài gắn với đúng một vai trò.</small>
            </div>

        </div>

        <div id="mm_alert" class="schedule-alert hidden"></div>

        <div class="modal-actions">
            <button class="btn" id="mm_cancel" type="button">Hủy</button>
            <button class="btn danger" id="mm_delete" type="button">Xóa trận</button>
            <button class="btn primary" id="mm_save" type="button">Lưu</button>
        </div>
    </div>
</div>
