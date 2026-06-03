<section
    class="organizer-tournaments"
    data-tournaments-api="<?= e(url('/api/organizer/tournaments')) ?>"
    data-options-api="<?= e(url('/api/organizer/tournament-options')) ?>"
    data-eligibility-preview-api="<?= e(url('/api/organizer/tournament-eligibility-preview')) ?>"
>
    <header class="tournaments-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Quản lý giải đấu</h1>
            <p class="sub">Tạo, cập nhật giải chưa công bố, công bố, mở/đóng đăng ký và duyệt đội tham gia.</p>
        </div>
        <button id="btnCreate" class="btn primary" type="button">Tạo giải đấu</button>
    </header>

    <section class="tournaments-toolbar" aria-label="Bộ lọc giải đấu">
        <input id="q" type="text" placeholder="Tìm theo tên giải / cấp giải / khu vực" />

        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="NHAP">Nháp</option>
            <option value="CHUA_CONG_BO">Chưa công bố</option>
            <option value="DA_CONG_BO">Đã công bố</option>
            <option value="DANG_DIEN_RA">Đang diễn ra</option>
            <option value="DA_KET_THUC">Đã kết thúc</option>
            <option value="DA_HUY">Đã hủy</option>
        </select>

        <select id="regFilter">
            <option value="">Tất cả trạng thái đăng ký</option>
            <option value="CHUA_MO">Chưa mở</option>
            <option value="DANG_MO">Đang mở</option>
            <option value="DA_DONG">Đã đóng</option>
        </select>

        <input type="date" id="fromDate" aria-label="Từ ngày" />
        <input type="date" id="toDate" aria-label="Đến ngày" />

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <div class="tournaments-table-wrap">
        <table class="tournaments-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên giải đấu</th>
                    <th>Thời gian</th>
                    <th>Cấp / khu vực</th>
                    <th>Quy mô</th>
                    <th>Trạng thái</th>
                    <th>Đăng ký</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr>
                    <td colspan="8" class="empty">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="tournament-message" id="pageMessage" role="status"></p>
</section>

<div class="tournament-modal hidden" id="tournamentModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-head">
            <h2 id="modalTitle">Tạo giải đấu</h2>
            <button id="m_close" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="tournament-grid">
            <div class="colspan">
                <label for="m_name">Tên giải đấu</label>
                <input id="m_name" placeholder="VD: Giải bóng chuyền IUH 2026" />
            </div>

            <div>
                <label for="m_level">Cấp giải đấu</label>
                <select id="m_level">
                    <option value="">Đang tải cấp giải...</option>
                </select>
                <p id="m_level_hint" class="field-hint">BTC chỉ được tạo giải đúng cấp và khu vực quản lý.</p>
            </div>

            <div>
                <label for="m_scope_region">Khu vực phạm vi</label>
                <select id="m_scope_region">
                    <option value="">Đang tải khu vực...</option>
                </select>
            </div>

            <div>
                <label for="m_start">Thời gian bắt đầu</label>
                <input id="m_start" type="datetime-local" step="60" />
            </div>

            <div>
                <label for="m_end">Thời gian kết thúc</label>
                <input id="m_end" type="datetime-local" step="60" />
            </div>

            <div>
                <label for="m_law">Luật thi đấu</label>
                <select id="m_law">
                    <option value="">Đang tải luật thi đấu...</option>
                </select>
            </div>

            <div>
                <label for="m_gender">Giới tính giải đấu</label>
                <select id="m_gender">
                    <option value="NAM">Nam</option>
                    <option value="NU">Nữ</option>
                </select>
                <p class="field-hint">Giải nam và giải nữ được quản lý tách biệt.</p>
            </div>

            <div>
                <label for="m_nature">Tính chất giải</label>
                <select id="m_nature">
                    <option value="CHINH_THUC">Chính thức</option>
                    <option value="GIAO_HUU">Giao hữu</option>
                    <option value="PHONG_TRAO">Phong trào</option>
                    <option value="NOI_BO">Nội bộ</option>
                    <option value="MO_RONG">Mở rộng</option>
                </select>
            </div>

            <div>
                <label for="m_size">Quy mô (số đội)</label>
                <input id="m_size" type="number" min="2" value="10" />
            </div>

            <div>
                <label>Ảnh giải đấu tùy chọn</label>
                <div class="image-mode" role="group" aria-label="Kiểu ảnh giải đấu">
                    <label><input type="radio" name="m_image_mode" value="url" checked /> Gắn URL</label>
                    <label><input type="radio" name="m_image_mode" value="upload" /> Tải ảnh</label>
                </div>
                <input id="m_image" placeholder="https://..." />
                <input id="m_image_file" class="hidden" type="file" accept="image/*" />
                <p id="m_image_hint" class="field-hint">Nhập URL ảnh đã lưu trữ công khai.</p>
            </div>

            <div class="colspan">
                <label for="m_place_note">Ghi chú địa điểm dự kiến</label>
                <input id="m_place_note" placeholder="VD: dự kiến tổ chức tại nhiều sân thuộc IUH" />
                <p class="field-hint">Không chọn sân khi tạo giải. Vị trí thi đấu và sân đấu chỉ được chọn khi xếp lịch từng trận.</p>
            </div>

            <div class="colspan">
                <label for="m_desc">Mô tả</label>
                <textarea id="m_desc" rows="3" placeholder="Mô tả ngắn về giải đấu..."></textarea>
            </div>

            <div class="colspan form-section-title">Điều lệ giải đấu</div>

            <div>
                <label for="m_min_teams">Số đội tối thiểu</label>
                <select id="m_min_teams">
                    <option value="">Chọn số đội tối thiểu...</option>
                </select>
                <p id="m_team_count_hint" class="field-hint">Chọn cấp giải nguồn, thành tích và mùa giải được xét để hệ thống tính số đội hợp lệ.</p>
            </div>

            <div>
                <label for="m_max_teams">Số đội tối đa</label>
                <select id="m_max_teams">
                    <option value="">Chọn số đội tối đa...</option>
                </select>
            </div>

            <div>
                <label for="m_min_players">Số VĐV tối thiểu/đội</label>
                <select id="m_min_players"></select>
            </div>

            <div>
                <label for="m_max_players">Số VĐV tối đa/đội</label>
                <select id="m_max_players"></select>
            </div>

            <div>
                <label for="m_fee">Lệ phí tham gia</label>
                <input id="m_fee" type="number" min="0" step="1000" value="0" placeholder="VD: 500000" />
            </div>

            <div class="eligibility-achievement-source">
                <label for="m_achievement_level">Cấp giải nguồn của thành tích</label>
                <select id="m_achievement_level"></select>
                <p id="m_eligibility_hint" class="field-hint">Cấp đội tham gia được suy ra tự động từ cấp giải hiện tại và cấp giải nguồn thành tích.</p>
            </div>

            <div class="eligibility-achievements">
                <label>Thành tích trong giải được phép</label>
                <div class="achievement-options">
                    <label class="checkbox-field"><input type="checkbox" name="m_achievement_requirement" value="VO_DICH" /> Vô địch</label>
                    <label class="checkbox-field"><input type="checkbox" name="m_achievement_requirement" value="A_QUAN" /> Á quân</label>
                    <label class="checkbox-field"><input type="checkbox" name="m_achievement_requirement" value="HANG_BA" /> Hạng ba</label>
                </div>
                <p class="field-hint">Có thể chọn đồng thời nhiều thành tích, ví dụ Vô địch và Á quân.</p>
            </div>

            <div>
                <label for="m_recent_seasons">Số mùa gần nhất được xét</label>
                <select id="m_recent_seasons">
                    <option value="1">1 mùa gần nhất</option>
                    <option value="2">2 mùa gần nhất</option>
                    <option value="3">3 mùa gần nhất</option>
                    <option value="4">4 mùa gần nhất</option>
                    <option value="5">5 mùa gần nhất</option>
                </select>
            </div>

            <div class="colspan participation-flags">
                <label class="checkbox-field" for="m_official_only">
                    <input id="m_official_only" type="checkbox" checked />
                    Chỉ tính giải chính thức
                </label>
                <label class="checkbox-field" for="m_allow_exception">
                    <input id="m_allow_exception" type="checkbox" />
                    Cho phép BTC duyệt ngoại lệ
                </label>
            </div>

            <div class="colspan">
                <label for="m_rule_title">Tiêu đề điều lệ</label>
                <input id="m_rule_title" placeholder="VD: Điều lệ giải đấu" />
            </div>

            <div class="colspan">
                <label for="m_rule_content">Nội dung điều lệ</label>
                <textarea id="m_rule_content" rows="4" placeholder="Nhập nội dung điều lệ, quy định đăng ký, khiếu nại, bỏ cuộc..."></textarea>
            </div>

            <div class="colspan form-section-title">Thể thức thi đấu</div>

            <div>
                <label for="m_format_type">Loại vòng thi đấu</label>
                <select id="m_format_type">
                    <option value="VONG_DIEM">Vòng điểm</option>
                    <option value="VONG_LOAI">Vòng loại trực tiếp</option>
                    <option value="KET_HOP">Cả vòng điểm và vòng loại</option>
                </select>
            </div>

            <div>
                <label for="m_pairing">Quy tắc xếp cặp</label>
                <select id="m_pairing">
                    <option value="RANDOM">Hệ thống sắp xếp</option>
                    <option value="MANUAL">BTC xếp thủ công</option>
                    <option value="HYBRID">Áp dụng cả hai</option>
                </select>
            </div>
        </div>

        <div id="m_alert" class="tournament-alert hidden"></div>

        <div class="modal-actions">
            <button id="m_cancel" class="btn" type="button">Hủy</button>
            <button id="m_cancel_tournament" class="btn danger hidden" type="button">Hủy giải</button>
            <button id="m_save" class="btn primary" type="button">Lưu</button>
        </div>
    </div>
</div>

<div class="tournament-modal hidden" id="regModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="regTitle">
        <div class="modal-head">
            <div>
                <h2 id="regTitle">Quản lý đăng ký giải đấu</h2>
                <p class="sub" id="r_tourName">-</p>
            </div>
            <button id="r_close" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="tournaments-toolbar small">
            <select id="r_status">
                <option value="">Tất cả trạng thái</option>
                <option value="CHO_DUYET">Chờ duyệt</option>
                <option value="DA_DUYET">Đã duyệt</option>
                <option value="TU_CHOI">Từ chối</option>
                <option value="DA_HUY">Đã hủy</option>
            </select>
            <input id="r_q" type="text" placeholder="Tìm đội bóng / HLV" />
        </div>

        <div class="tournaments-table-wrap compact">
            <table class="tournaments-table">
                <thead>
                    <tr>
                        <th>Mã ĐK</th>
                        <th>Đội bóng</th>
                        <th>HLV</th>
                        <th>Ngày đăng ký</th>
                        <th>Trạng thái</th>
                        <th>Lý do từ chối</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="r_tbody">
                    <tr>
                        <td colspan="7" class="empty">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="modal-actions">
            <button id="r_closeBtn" class="btn" type="button">Đóng</button>
        </div>
    </div>
</div>

<div class="tournament-modal hidden" id="rejectModal" aria-hidden="true">
    <div class="modal-content small" role="dialog" aria-modal="true" aria-labelledby="rejectTitle">
        <div class="modal-head">
            <h2 id="rejectTitle">Từ chối đăng ký</h2>
            <button id="rej_close" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <p class="sub" id="rej_info">-</p>

        <label for="rej_reason">Lý do từ chối (bắt buộc)</label>
        <textarea id="rej_reason" rows="4" placeholder="VD: Hồ sơ đội chưa đầy đủ..."></textarea>

        <div id="rej_alert" class="tournament-alert hidden"></div>

        <div class="modal-actions">
            <button id="rej_cancel" class="btn" type="button">Hủy</button>
            <button id="rej_confirm" class="btn danger" type="button">Xác nhận từ chối</button>
        </div>
    </div>
</div>
