(function () {
    const root = document.querySelector(".coach-results");
    if (!root) return;

    const ui = window.CoachUI;
    const resultsApi = root.dataset.resultsApi || "/api/coach/results";
    const teamsApi = root.dataset.teamsApi || "/api/coach/teams";

    const teamFilter = document.getElementById("teamFilter");
    const tbody = document.getElementById("tbody");
    const empty = document.getElementById("empty");
    const table = document.getElementById("resultsTable");
    const pageMessage = document.getElementById("pageMessage");
    const modal = document.getElementById("complaintModal");
    const modalAlert = document.getElementById("modalAlert");
    const complaintSub = document.getElementById("complaintSub");
    const matchInfo = document.getElementById("matchInfo");
    const scoreInfo = document.getElementById("scoreInfo");
    const complaintContent = document.getElementById("complaintContent");
    const complaintEvidence = document.getElementById("complaintEvidence");
    const btnSubmitComplaint = document.getElementById("btnSubmitComplaint");

    let results = [];
    let current = null;

    function statusLabel(status) {
        const map = {
            DA_CONG_BO: "Đã công bố",
            DA_DIEU_CHINH: "Đã điều chỉnh",
        };

        return map[status] || status || "-";
    }

    function matchLabel(item) {
        const code = item.ma_tran || `#${item.idtrandau}`;
        const teams = [item.doi1, item.doi2].filter(Boolean).join(" vs ");
        return `${code}${teams ? ` - ${teams}` : ""}`;
    }

    function scoreLabel(item) {
        return `${item.diemdoi1 ?? "-"}-${item.diemdoi2 ?? "-"} / set ${item.sosetdoi1 ?? "-"}-${item.sosetdoi2 ?? "-"}`;
    }

    async function loadTeams() {
        const payload = await ui.requestJson(teamsApi);
        const teams = payload.data || [];
        ui.fillSelect(teamFilter, teams, "iddoibong", "tendoibong", "Tất cả đội bóng");
    }

    async function loadResults() {
        const params = {
            q: document.getElementById("q").value.trim(),
            status: document.getElementById("statusFilter").value,
            team_id: teamFilter.value,
            from: document.getElementById("fromDate").value,
            to: document.getElementById("toDate").value,
        };
        const payload = await ui.requestJson(ui.apiUrl(resultsApi, params));
        results = payload.data || [];

        if (results.length === 0) {
            table.classList.add("hidden");
            empty.classList.remove("hidden");
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Chưa có kết quả thi đấu.</td></tr>';
            return;
        }

        empty.classList.add("hidden");
        table.classList.remove("hidden");
        tbody.innerHTML = results.map((item) => {
            const [badgeClass] = ui.badge(item.trangthai);
            const round = item.vongdau || item.tenbang || "";

            return `
                <tr>
                    <td>${ui.escapeHtml(item.ma_tran || `#${item.idtrandau}`)}</td>
                    <td>${ui.escapeHtml(item.thoigianbatdau || "")}</td>
                    <td>${ui.escapeHtml(item.tengiaidau || "")}</td>
                    <td>
                        <strong>${ui.escapeHtml([item.doi1, item.doi2].filter(Boolean).join(" vs "))}</strong>
                        <div class="hint">${ui.escapeHtml(round)}</div>
                    </td>
                    <td>${ui.escapeHtml(scoreLabel(item))}</td>
                    <td>${ui.escapeHtml(item.doithang || "-")}</td>
                    <td><span class="badge ${badgeClass}">${ui.escapeHtml(statusLabel(item.trangthai))}</span></td>
                    <td><button class="btn" type="button" data-action="complain" data-id="${ui.escapeHtml(item.idketqua)}">Khiếu nại</button></td>
                </tr>
            `;
        }).join("");
    }

    async function refresh() {
        ui.show(pageMessage, "Đang tải dữ liệu...");
        await loadResults();
        ui.show(pageMessage, "");
    }

    function openComplaint(resultId) {
        current = results.find((item) => String(item.idketqua) === String(resultId)) || null;
        if (!current) return;

        ui.hideAlert(modalAlert);
        complaintSub.textContent = current.tengiaidau || "";
        matchInfo.value = matchLabel(current);
        scoreInfo.value = scoreLabel(current);
        complaintContent.value = "";
        complaintEvidence.value = "";
        modal.classList.remove("hidden");
        complaintContent.focus();
    }

    function closeComplaint() {
        modal.classList.add("hidden");
        current = null;
    }

    async function submitComplaint() {
        if (!current) return;

        const content = complaintContent.value.trim();
        if (!content) {
            ui.showAlert(modalAlert, "Vui lòng nhập nội dung khiếu nại.");
            return;
        }

        btnSubmitComplaint.disabled = true;
        ui.hideAlert(modalAlert);

        try {
            await ui.requestJson(`${resultsApi}/${current.idketqua}/complaints`, {
                method: "POST",
                body: JSON.stringify({
                    noidung: content,
                    minhchung: complaintEvidence.value.trim(),
                }),
            });
            closeComplaint();
            ui.show(pageMessage, "Đã gửi khiếu nại đến BTC tổ chức giải đấu.");
        } catch (error) {
            ui.showAlert(modalAlert, ui.errorsText(error));
        } finally {
            btnSubmitComplaint.disabled = false;
        }
    }

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("[data-action='complain']");
        if (button) {
            openComplaint(button.dataset.id);
        }
    });

    document.getElementById("btnRefresh").addEventListener("click", () => refresh().catch((error) => ui.show(pageMessage, ui.errorsText(error), true)));
    document.getElementById("btnCloseModal").addEventListener("click", closeComplaint);
    document.getElementById("btnCancelComplaint").addEventListener("click", closeComplaint);
    btnSubmitComplaint.addEventListener("click", submitComplaint);

    ["q", "statusFilter", "fromDate", "toDate", "teamFilter"].forEach((id) => {
        document.getElementById(id).addEventListener("change", () => refresh().catch(() => {}));
    });
    document.getElementById("q").addEventListener("input", () => refresh().catch(() => {}));

    loadTeams()
        .then(refresh)
        .catch((error) => ui.show(pageMessage, ui.errorsText(error), true));
})();
