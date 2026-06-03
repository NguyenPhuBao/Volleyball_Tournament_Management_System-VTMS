<section
    class="organizer-athletes"
    data-athletes-api="<?= e(url('/api/organizer/athletes')) ?>"
>
    <header class="athletes-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Quản lý tư cách thi đấu Vận động viên</h1>
            <p class="sub">Xác nhận hoặc hủy tư cách thi đấu dựa trên hồ sơ và yêu cầu xác nhận.</p>
        </div>
    </header>

    <section class="athletes-toolbar" aria-label="Bộ lọc vận động viên">
        <input id="q" type="text" placeholder="Tìm theo tên / mã VĐV / email / SĐT" />

        <select id="statusFilter">
            <option value="">Tất cả trạng thái</option>
            <option value="CHO_XAC_NHAN">Chờ xác nhận</option>
            <option value="DU_DIEU_KIEN">Đủ điều kiện</option>
            <option value="BI_HUY_TU_CACH">Bị hủy tư cách</option>
            <option value="DANG_NGHI_PHEP">Đang nghỉ phép</option>
        </select>

        <select id="requestFilter">
            <option value="">Tất cả yêu cầu</option>
            <option value="HAS_REQUEST">Có yêu cầu xác nhận</option>
            <option value="NO_REQUEST">Không có yêu cầu</option>
        </select>

        <input type="date" id="fromDate" aria-label="Từ ngày" />
        <input type="date" id="toDate" aria-label="Đến ngày" />

        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section class="athletes-stats" aria-label="Thống kê vận động viên">
        <div class="stat"><span id="sPending">0</span><small>Chờ xác nhận</small></div>
        <div class="stat"><span id="sOk">0</span><small>Đủ điều kiện</small></div>
        <div class="stat"><span id="sRevoked">0</span><small>Bị hủy tư cách</small></div>
    </section>

    <div class="athletes-table-wrap">
        <table class="athletes-table">
            <thead>
                <tr>
                    <th>ID VĐV</th>
                    <th>Mã VĐV</th>
                    <th>Họ tên</th>
                    <th>Đội bóng</th>
                    <th>HLV đội</th>
                    <th>Liên hệ</th>
                    <th>Vị trí</th>
                    <th>Tư cách thi đấu</th>
                    <th>Yêu cầu</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr>
                    <td colspan="10" class="empty">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="athletes-message" id="pageMessage" role="status"></p>
</section>

<div class="athletes-modal hidden" id="detailModal" aria-hidden="true">
    <div class="modal-content wide" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
        <div class="modal-head">
            <div>
                <h2 id="m_name">Chi tiết vận động viên</h2>
                <p class="sub" id="m_sub">-</p>
            </div>
            <button id="m_close" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <div class="athletes-grid">
            <div>
                <label for="m_id">ID VĐV</label>
                <input id="m_id" disabled />
            </div>
            <div>
                <label for="m_status">Tư cách thi đấu</label>
                <input id="m_status" disabled />
            </div>

            <div>
                <label for="m_code">Mã VĐV</label>
                <input id="m_code" disabled />
            </div>
            <div>
                <label for="m_pos">Vị trí</label>
                <input id="m_pos" disabled />
            </div>

            <div>
                <label for="m_username">Username</label>
                <input id="m_username" disabled />
            </div>
            <div>
                <label for="m_email">Email</label>
                <input id="m_email" disabled />
            </div>

            <div>
                <label for="m_phone">SĐT</label>
                <input id="m_phone" disabled />
            </div>
            <div>
                <label for="m_gender">Giới tính</label>
                <input id="m_gender" disabled />
            </div>

            <div>
                <label for="m_dob">Ngày sinh</label>
                <input id="m_dob" disabled />
            </div>
            <div>
                <label for="m_hometown">Quê quán</label>
                <input id="m_hometown" disabled />
            </div>

            <div>
                <label for="m_height">Chiều cao (m)</label>
                <input id="m_height" disabled />
            </div>
            <div>
                <label for="m_weight">Cân nặng (kg)</label>
                <input id="m_weight" disabled />
            </div>

            <div class="colspan">
                <label for="m_address">Địa chỉ</label>
                <input id="m_address" disabled />
            </div>
        </div>

        <hr />

        <h3>Yêu cầu xác nhận</h3>
        <div class="athletes-grid">
            <div>
                <label for="m_reqId">Mã yêu cầu</label>
                <input id="m_reqId" disabled placeholder="-" />
            </div>
            <div>
                <label for="m_reqStatus">Trạng thái yêu cầu</label>
                <input id="m_reqStatus" disabled placeholder="-" />
            </div>
            <div class="colspan">
                <label for="m_reqContent">Nội dung yêu cầu</label>
                <textarea id="m_reqContent" rows="3" disabled placeholder="-"></textarea>
            </div>
        </div>

        <div id="m_alert" class="athletes-alert hidden"></div>

        <div class="modal-actions">
            <button id="m_revoke" class="btn danger" type="button">Hủy tư cách</button>
            <button id="m_approve" class="btn primary" type="button">Xác nhận tư cách</button>
            <button id="m_closeBtn" class="btn" type="button">Đóng</button>
        </div>
    </div>
</div>

<div class="athletes-modal hidden" id="revokeModal" aria-hidden="true">
    <div class="modal-content small" role="dialog" aria-modal="true" aria-labelledby="revokeTitle">
        <div class="modal-head">
            <h2 id="revokeTitle">Hủy tư cách thi đấu</h2>
            <button id="rv_close" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <p class="sub" id="rv_info">-</p>

        <label for="rv_reason">Lý do hủy</label>
        <textarea id="rv_reason" rows="4" placeholder="VD: Không đủ điều kiện hồ sơ / vi phạm quy định..."></textarea>

        <div id="rv_alert" class="athletes-alert hidden"></div>

        <div class="modal-actions">
            <button id="rv_cancel" class="btn" type="button">Hủy</button>
            <button id="rv_confirm" class="btn danger" type="button">Xác nhận hủy</button>
        </div>
    </div>
</div>
