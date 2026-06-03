(function () {
    const root = document.querySelector(".coach-team-schedule");
    if (!root) return;

    const ui = window.CoachUI;
    const teamsApi = root.dataset.teamsApi || "/api/coach/teams";
    const teamSelect = document.getElementById("teamSelect");
    const tbody = document.getElementById("tbody");
    const empty = document.getElementById("empty");
    const table = document.getElementById("scheduleTable");
    const pageMessage = document.getElementById("pageMessage");

    let teams = [];

    async function loadTeams() {
        const payload = await ui.requestJson(teamsApi);
        teams = payload.data || [];
        ui.fillSelect(teamSelect, teams, "iddoibong", "tendoibong", "Chọn đội bóng");

        if (!teamSelect.value && teams.length > 0) {
            teamSelect.value = teams[0].iddoibong;
        }
    }

    async function loadSchedule() {
        if (!teamSelect.value) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty">Chưa có đội bóng.</td></tr>';
            return;
        }

        const params = {
            q: document.getElementById("q").value.trim(),
            status: document.getElementById("statusFilter").value,
            from: document.getElementById("fromDate").value,
            to: document.getElementById("toDate").value,
        };
        const payload = await ui.requestJson(ui.apiUrl(`${teamsApi}/${teamSelect.value}/schedule`, params));
        const matches = payload.data || [];

        if (matches.length === 0) {
            table.classList.add("hidden");
            empty.classList.remove("hidden");
            return;
        }

        empty.classList.add("hidden");
        table.classList.remove("hidden");
        tbody.innerHTML = matches.map((match) => {
            const opponent = match.phia_doi_bong === "DOI_1" ? match.doi2 : match.doi1;
            const [badgeClass, label] = ui.badge(match.trangthai);
            return `
                <tr>
                    <td>${ui.escapeHtml(match.thoigianbatdau || "")}</td>
                    <td>${ui.escapeHtml(match.tengiaidau || "")}</td>
                    <td>${ui.escapeHtml(opponent || "")}</td>
                    <td>${ui.escapeHtml(match.tensandau || "")}</td>
                    <td>${ui.escapeHtml(match.vongdau || match.tenbang || "")}</td>
                    <td><span class="badge ${badgeClass}">${ui.escapeHtml(label)}</span></td>
                </tr>
            `;
        }).join("");
    }

    async function refresh() {
        ui.show(pageMessage, "");
        await loadSchedule();
    }

    teamSelect.addEventListener("change", () => refresh().catch((error) => ui.show(pageMessage, ui.errorsText(error), true)));
    document.getElementById("btnRefresh").addEventListener("click", () => refresh().catch((error) => ui.show(pageMessage, ui.errorsText(error), true)));
    ["q", "statusFilter", "fromDate", "toDate"].forEach((id) => {
        document.getElementById(id).addEventListener("change", () => refresh().catch(() => {}));
        document.getElementById(id).addEventListener("input", () => refresh().catch(() => {}));
    });

    loadTeams()
        .then(refresh)
        .catch((error) => ui.show(pageMessage, ui.errorsText(error), true));
})();
