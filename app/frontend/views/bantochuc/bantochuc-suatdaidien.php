<section
    class="organizer-teams"
    data-eligibility-api="<?= e(url('/api/organizer/higher-eligibility')) ?>"
>
    <header class="teams-topbar">
        <div>
            <p class="eyebrow">BAN TỔ CHỨC</p>
            <h1>Tư cách tham gia cấp trên</h1>
            <p class="sub">Xét đội có thành tích đủ điều kiện, đề cử lên BTC cấp cao hơn và xác nhận đề cử gửi đến.</p>
        </div>
    </header>

    <section class="teams-toolbar" aria-label="Bộ lọc tư cách tham gia">
        <input id="q" type="text" placeholder="Tìm đội bóng / giải nguồn / giải cấp trên" />
        <select id="sourceTournamentFilter">
            <option value="">Tất cả giải đấu nguồn</option>
        </select>
        <select id="achievementFilter">
            <option value="">Tất cả thành tích</option>
            <option value="VO_DICH">Vô địch</option>
            <option value="A_QUAN">Á quân</option>
            <option value="HANG_BA">Hạng ba</option>
            <option value="TOP_4">Top 4</option>
            <option value="TOP_8">Top 8</option>
            <option value="THAM_DU">Tham dự</option>
            <option value="KHAC">Khác</option>
        </select>
        <button id="btnRefresh" class="btn" type="button">Làm mới</button>
    </section>

    <section>
        <h2 class="section-title">Đội có thể đề cử</h2>
        <div class="teams-table-wrap">
            <table class="teams-table">
                <thead>
                    <tr>
                        <th>Đội bóng</th>
                        <th>Thành tích nguồn</th>
                        <th>Giải cấp trên</th>
                        <th>BTC nhận</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="candidateBody">
                    <tr>
                        <td colspan="6" class="empty">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2 class="section-title">Đề cử gửi đến BTC hiện tại</h2>
        <div class="teams-table-wrap">
            <table class="teams-table">
                <thead>
                    <tr>
                        <th>Đội bóng</th>
                        <th>BTC đề cử</th>
                        <th>Thành tích nguồn</th>
                        <th>Giải cấp trên</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="incomingBody">
                    <tr>
                        <td colspan="6" class="empty">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <p class="teams-message" id="pageMessage" role="status"></p>
</section>

<div class="team-modal hidden" id="reviewModal" aria-hidden="true">
    <div class="modal-content wide higher-review-modal" id="reviewModalContent" role="dialog" aria-modal="true" aria-labelledby="reviewTeamName">
        <div class="modal-head">
            <div>
                <h2 id="reviewTeamName">Xem xét đội bóng</h2>
                <p class="sub" id="reviewTeamSub">-</p>
            </div>
            <button id="reviewClose" class="icon" type="button" aria-label="Đóng">×</button>
        </div>

        <section class="review-summary" aria-label="Thông tin đội bóng">
            <div>
                <label>ID đội</label>
                <strong id="reviewTeamId">-</strong>
            </div>
            <div>
                <label>Địa phương</label>
                <strong id="reviewTeamLocal">-</strong>
            </div>
            <div>
                <label>Trạng thái đội</label>
                <strong id="reviewTeamStatus">-</strong>
            </div>
            <div>
                <label>Thành viên đang xét</label>
                <strong id="reviewMemberCount">0</strong>
            </div>
        </section>

        <section class="review-section" aria-labelledby="reviewCoachTitle">
            <h3 id="reviewCoachTitle">Huấn luyện viên</h3>
            <div id="reviewCoach"></div>
        </section>

        <section class="review-section" aria-labelledby="reviewAthleteTitle">
            <h3 id="reviewAthleteTitle">Vận động viên đang tham gia</h3>
            <div class="teams-table-wrap compact">
                <table class="teams-table review-members-table">
                    <thead>
                        <tr>
                            <th>Mã VĐV</th>
                            <th>Họ tên</th>
                            <th>Vị trí</th>
                            <th>Vai trò</th>
                            <th>Xem xét</th>
                        </tr>
                    </thead>
                    <tbody id="reviewMembers">
                        <tr>
                            <td colspan="5" class="empty">Chưa có dữ liệu.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="review-progress" aria-live="polite">
            <div>
                <strong id="reviewPercent">0%</strong>
                <span id="reviewProgressText">Cần xem và xác nhận HLV, VĐV.</span>
            </div>
            <progress id="reviewProgress" max="100" value="0">0%</progress>
        </section>

        <div id="reviewAlert" class="team-alert hidden"></div>

        <div class="modal-actions">
            <button id="reviewCloseBtn" class="btn" type="button">Đóng</button>
            <button id="reviewNominate" class="btn primary hidden" type="button">Đề cử</button>
            <button id="reviewMark" class="btn primary" type="button" disabled>Đủ điều kiện</button>
        </div>
    </div>
</div>

<div class="team-modal hidden person-modal" id="personModal" aria-hidden="true">
    <div class="modal-content person-modal-content" role="dialog" aria-modal="true" aria-labelledby="personTitle">
        <div class="modal-head">
            <div>
                <h2 id="personTitle">Thông tin cá nhân</h2>
                <p class="sub" id="personSub">-</p>
            </div>
            <button id="personClose" class="icon" type="button" aria-label="Đóng">×</button>
        </div>
        <dl id="personDetails" class="person-details"></dl>
        <div class="modal-actions">
            <button id="personCloseBtn" class="btn" type="button">Đóng</button>
        </div>
    </div>
</div>
