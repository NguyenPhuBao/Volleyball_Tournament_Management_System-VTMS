(function () {
    const root = document.querySelector(".coach-team-profile");
    if (!root) return;

    const ui = window.CoachUI;
    const teamsApi = root.dataset.teamsApi || "/api/coach/teams";
    const teamSelect = document.getElementById("teamSelect");
    const teamInfo = document.getElementById("teamInfo");
    const pageMessage = document.getElementById("pageMessage");
    const modal = document.getElementById("teamModal");
    const alertBox = document.getElementById("m_alert");

    let teams = [];
    let current = null;

    function renderCurrentLevel(team) {
        const currentLevel = team.tencapgiaidau_hien_tai || team.macapgiaidau_hien_tai || "—";
        const sourceLevel = team.tencapgiaidau_nguon || team.macapgiaidau_nguon || "";
        const approvedLevel = team.tencapgiaidau_duoc_tham_gia || team.macapgiaidau_duoc_tham_gia || "";
        const note = approvedLevel && sourceLevel && approvedLevel !== sourceLevel
            ? `<span class="team-level-note">Nguồn: ${ui.escapeHtml(sourceLevel)}</span>`
            : "";

        return `<span class="badge proc">${ui.escapeHtml(currentLevel)}</span>${note}`;
    }

    function renderProposalBadge(status) {
        const map = {
            DU_DIEU_KIEN: ["ok", "Đủ điều kiện"],
            DA_DE_CU: ["wait", "Đã đề cử"],
            DA_XAC_NHAN: ["ok", "Đã xác nhận"],
        };
        const [className, label] = map[status] || ui.badge(status);

        return `<span class="badge ${className}">${ui.escapeHtml(label)}</span>`;
    }

    function renderNextLevel(team) {
        const nextLevel = team.tencapgiaidau_thi_tiep
            || team.tencapgiaidau_duoc_tham_gia
            || team.macapgiaidau_thi_tiep
            || team.macapgiaidau_duoc_tham_gia
            || "";

        if (!nextLevel) {
            return "—";
        }

        const status = team.trangthai_decu_thi_tiep ? renderProposalBadge(team.trangthai_decu_thi_tiep) : "";
        return `<span class="badge proc">${ui.escapeHtml(nextLevel)}</span>${status ? ` ${status}` : ""}`;
    }

    function renderRecommendedTournament(team) {
        const tournament = team.tengiaidau_decu_tham_gia || "";
        return tournament ? ui.escapeHtml(tournament) : "—";
    }

    function renderInfo() {
        if (!current) {
            teamInfo.innerHTML = '<p class="empty">Chưa có thông tin đội bóng. Vui lòng tạo đội.</p>';
            return;
        }

        const [badgeClass, label] = ui.badge(current.trangthai);
        const logo = current.logo ? `<img class="team-logo" src="${ui.escapeHtml(current.logo)}" alt="Logo đội">` : "";
        teamInfo.innerHTML = `
            <div class="team-profile-grid">
                <strong>Tên đội</strong><div>${ui.escapeHtml(current.tendoibong)}</div>
                <strong>Địa phương</strong><div>${ui.escapeHtml(current.diaphuong || "")}</div>
                <strong>Logo</strong><div>${logo || "—"}</div>
                <strong>Cấp độ thi đấu hiện tại</strong><div>${renderCurrentLevel(current)}</div>
                <strong>Cấp độ thi đấu tiếp</strong><div>${renderNextLevel(current)}</div>
                <strong>Giải được đề cử tham gia</strong><div>${renderRecommendedTournament(current)}</div>
                <strong>Trạng thái</strong><div><span class="badge ${badgeClass}">${ui.escapeHtml(label)}</span></div>
                <strong>Số thành viên</strong><div>${ui.escapeHtml(current.active_members ?? current.total_members ?? "—")}</div>
                <strong>Mô tả</strong><div>${ui.escapeHtml(current.mota || "")}</div>
            </div>
        `;
    }

    function fillTeams() {
        ui.fillSelect(teamSelect, teams, "iddoibong", "tendoibong", "Tạo đội mới");
        if (current) teamSelect.value = current.iddoibong;
    }

    async function load() {
        const payload = await ui.requestJson(teamsApi);
        teams = payload.data || [];
        current = teams.find((team) => String(team.iddoibong) === String(teamSelect.value)) || teams[0] || null;
        fillTeams();
        renderInfo();
    }

    function openModal() {
        ui.hideAlert(alertBox);
        const team = current;
        document.getElementById("m_name").value = team?.tendoibong || "";
        document.getElementById("m_location").value = team?.diaphuong || "";
        document.getElementById("m_logo").value = team?.logo || "";
        document.getElementById("m_color").value = "";
        document.getElementById("m_desc").value = team?.mota || "";
        document.getElementById("m_status").value = team?.trangthai || "HOAT_DONG";
        modal.classList.remove("hidden");
    }

    function closeModal() {
        modal.classList.add("hidden");
    }

    teamSelect.addEventListener("change", () => {
        current = teams.find((team) => String(team.iddoibong) === String(teamSelect.value)) || null;
        renderInfo();
    });
    document.getElementById("btnRefresh").addEventListener("click", () => load().catch((error) => ui.show(pageMessage, ui.errorsText(error), true)));
    document.getElementById("btnEdit").addEventListener("click", openModal);
    document.getElementById("m_close").addEventListener("click", closeModal);
    document.getElementById("m_cancel").addEventListener("click", closeModal);

    document.getElementById("m_save").addEventListener("click", async () => {
        ui.hideAlert(alertBox);
        const color = document.getElementById("m_color").value.trim();
        let note = document.getElementById("m_desc").value.trim();
        if (color) {
            note = note ? `${note}\nMàu áo: ${color}` : `Màu áo: ${color}`;
        }

        const payload = {
            tendoibong: document.getElementById("m_name").value.trim(),
            diaphuong: document.getElementById("m_location").value.trim(),
            logo: document.getElementById("m_logo").value.trim() || null,
            mota: note || null,
            trangthai: document.getElementById("m_status").value,
        };

        if (!payload.tendoibong || !payload.diaphuong) {
            ui.showAlert(alertBox, "Vui lòng nhập đầy đủ Tên đội và Địa phương.");
            return;
        }

        try {
            const url = current ? `${teamsApi}/${current.iddoibong}` : teamsApi;
            const method = current ? "PUT" : "POST";
            const result = await ui.requestJson(url, { method, body: JSON.stringify(payload) });
            closeModal();
            ui.show(pageMessage, result.message || "Lưu thông tin đội bóng thành công.");
            await load();
        } catch (error) {
            ui.showAlert(alertBox, ui.errorsText(error));
        }
    });

    load().catch((error) => ui.show(pageMessage, ui.errorsText(error), true));
})();
