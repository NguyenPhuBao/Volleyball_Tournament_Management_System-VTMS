(function () {
    const root = document.querySelector(".coach-lineup");
    if (!root) return;

    const ui = window.CoachUI;
    const teamsApi = root.dataset.teamsApi || "/api/coach/teams";
    const registrationsApi = root.dataset.registrationsApi || "/api/coach/tournament-registrations";
    const lineupsApi = root.dataset.lineupsApi || "/api/coach/lineups";
    const lineupEditorUrl = root.dataset.lineupEditorUrl || "/huan-luyen-vien/doi-hinh/chinh-sua";
    const teamSelect = document.getElementById("teamSelect");
    const tournamentSelect = document.getElementById("tournamentSelect");
    const container = document.getElementById("lineupInfo");
    const pageMessage = document.getElementById("pageMessage");

    let teams = [];
    let registrations = [];

    function genderLabel(value) {
        return value === "NU" ? "Nữ" : "Nam";
    }

    function tournamentOptions() {
        const seen = new Set();
        return registrations
            .filter((registration) => registration.trangthai === "DA_DUYET")
            .filter((registration) => {
                if (seen.has(registration.idgiaidau)) return false;
                seen.add(registration.idgiaidau);
                return true;
            })
            .map((registration) => ({ idgiaidau: registration.idgiaidau, tengiaidau: registration.tengiaidau }));
    }

    function refreshTournamentSelect() {
        ui.fillSelect(tournamentSelect, tournamentOptions(), "idgiaidau", "tengiaidau", "Tất cả giải đấu");
    }

    function editUrl(lineup) {
        const params = new URLSearchParams();
        params.set("team_id", lineup.iddoibong);
        params.set("lineup_id", lineup.iddoihinh);
        return `${lineupEditorUrl}?${params.toString()}`;
    }

    async function loadBase() {
        const [teamsPayload, registrationsPayload] = await Promise.all([
            ui.requestJson(teamsApi),
            ui.requestJson(registrationsApi),
        ]);
        teams = teamsPayload.data || [];
        registrations = registrationsPayload.data || [];
        ui.fillSelect(teamSelect, teams, "iddoibong", "tendoibong", "Tất cả đội bóng");
        refreshTournamentSelect();
    }

    async function loadLineups() {
        const params = new URLSearchParams();
        if (teamSelect.value) params.set("team_id", teamSelect.value);
        if (tournamentSelect.value) params.set("tournament_id", tournamentSelect.value);

        const query = params.toString();
        const payload = await ui.requestJson(query ? `${lineupsApi}?${query}` : lineupsApi);
        const lineups = payload.data || [];
        const details = payload.details || [];

        if (lineups.length === 0) {
            container.innerHTML = '<p class="empty">Chưa có đội hình phù hợp.</p>';
            return;
        }

        container.innerHTML = lineups.map((lineup) => {
            const [badgeClass, label] = ui.badge(lineup.trangthai);
            const items = details.filter((detail) => String(detail.iddoihinh) === String(lineup.iddoihinh));
            const tournamentName = lineup.tengiaidau || "Không gắn giải đấu";
            const mainBadge = Number(lineup.la_doihinh_chinh || 0) === 1
                ? '<span class="badge ok">Đội hình chính</span>'
                : "";
            return `
                <article class="lineup-block">
                    <div class="lineup-head">
                        <div>
                            <h3>${ui.escapeHtml(lineup.tendoihinh)} <span class="badge ${badgeClass}">${ui.escapeHtml(label)}</span> ${mainBadge}</h3>
                            <p class="sub">${ui.escapeHtml(lineup.tendoibong || "-")} • ${ui.escapeHtml(genderLabel(lineup.gioitinh))} • ${ui.escapeHtml(tournamentName)}</p>
                        </div>
                        <a class="btn" href="${ui.escapeHtml(editUrl(lineup))}">Sửa</a>
                    </div>
                    <table class="coach-table compact">
                        <thead><tr><th>STT</th><th>VĐV</th><th>Vị trí</th><th>Ghi chú</th></tr></thead>
                        <tbody>
                            ${items.map((item) => `
                                <tr>
                                    <td>${ui.escapeHtml(item.sothutu)}</td>
                                    <td>${ui.escapeHtml(item.hoten)}</td>
                                    <td>${ui.escapeHtml(item.vitri)}</td>
                                    <td>${ui.escapeHtml(item.ghichu || "")}</td>
                                </tr>
                            `).join("") || '<tr><td colspan="4" class="empty">Không có VĐV.</td></tr>'}
                        </tbody>
                    </table>
                </article>
            `;
        }).join("");
    }

    document.getElementById("btnSearch").addEventListener("click", () => {
        loadLineups().catch((error) => ui.show(pageMessage, ui.errorsText(error), true));
    });

    document.getElementById("btnReset").addEventListener("click", async () => {
        try {
            await loadBase();
            teamSelect.value = "";
            tournamentSelect.value = "";
            await loadLineups();
        } catch (error) {
            ui.show(pageMessage, ui.errorsText(error), true);
        }
    });

    loadBase()
        .then(loadLineups)
        .catch((error) => ui.show(pageMessage, ui.errorsText(error), true));
})();
