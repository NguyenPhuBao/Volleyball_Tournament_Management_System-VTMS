(function () {
    const root = document.querySelector(".coach-members");
    if (!root) return;

    const ui = window.CoachUI;
    const teamsApi = root.dataset.teamsApi || "/api/coach/teams";
    const teamSelect = document.getElementById("teamSelect");
    const tbody = document.getElementById("tbody");
    const pageMessage = document.getElementById("pageMessage");
    const addModal = document.getElementById("addModal");
    const switchModal = document.getElementById("switchModal");
    const aAlert = document.getElementById("a_alert");
    const sAlert = document.getElementById("s_alert");
    const sTeam = document.getElementById("s_team");

    let teams = [];
    let members = [];
    let currentMember = null;

    function currentTeamId() {
        return teamSelect.value;
    }

    function render() {
        if (!currentTeamId()) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty">Chưa có đội bóng.</td></tr>';
            return;
        }
        if (members.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty">Đội chưa có thành viên.</td></tr>';
            return;
        }

        tbody.innerHTML = members.map((member) => {
            const [badgeClass, label] = ui.badge(member.trangthaithanhvien);
            const contact = [member.email, member.sodienthoai].filter(Boolean).join(" / ");
            return `
                <tr>
                    <td>${ui.escapeHtml(member.idthanhvien)}</td>
                    <td>${ui.escapeHtml(member.hoten)}</td>
                    <td>${ui.escapeHtml(member.vaitrotrongdoi)}</td>
                    <td>${ui.escapeHtml(contact)}</td>
                    <td>${ui.escapeHtml(member.vitri || "")}</td>
                    <td><span class="badge ${badgeClass}">${ui.escapeHtml(label)}</span></td>
                    <td>
                        <button class="btn" type="button" data-action="switch" data-id="${ui.escapeHtml(member.idthanhvien)}">Chuyển</button>
                        <button class="btn danger" type="button" data-action="remove" data-id="${ui.escapeHtml(member.idthanhvien)}">Xóa</button>
                    </td>
                </tr>
            `;
        }).join("");
    }

    async function loadTeams() {
        const payload = await ui.requestJson(teamsApi);
        teams = payload.data || [];
        ui.fillSelect(teamSelect, teams, "iddoibong", "tendoibong", "Chọn đội bóng");
        ui.fillSelect(sTeam, teams, "iddoibong", "tendoibong", "Chọn đội đích");

        if (!teamSelect.value && teams.length > 0) {
            teamSelect.value = teams[0].iddoibong;
        }
    }

    async function loadMembers() {
        if (!currentTeamId()) {
            members = [];
            render();
            return;
        }
        const payload = await ui.requestJson(`${teamsApi}/${currentTeamId()}/members`);
        members = payload.data || [];
        render();
    }

    async function init() {
        await loadTeams();
        await loadMembers();
    }

    teamSelect.addEventListener("change", () => loadMembers().catch((error) => ui.show(pageMessage, ui.errorsText(error), true)));
    document.getElementById("btnRefresh").addEventListener("click", () => init().catch((error) => ui.show(pageMessage, ui.errorsText(error), true)));
    document.getElementById("btnAdd").addEventListener("click", () => {
        ui.hideAlert(aAlert);
        addModal.classList.remove("hidden");
    });
    document.getElementById("a_close").addEventListener("click", () => addModal.classList.add("hidden"));
    document.getElementById("a_cancel").addEventListener("click", () => addModal.classList.add("hidden"));
    document.getElementById("s_close").addEventListener("click", () => switchModal.classList.add("hidden"));
    document.getElementById("s_cancel").addEventListener("click", () => switchModal.classList.add("hidden"));

    document.getElementById("a_submit").addEventListener("click", async () => {
        ui.hideAlert(aAlert);
        if (!currentTeamId()) {
            ui.showAlert(aAlert, "Vui lòng chọn đội bóng.");
            return;
        }
        const athleteId = document.getElementById("a_account").value.trim();
        if (!athleteId) {
            ui.showAlert(aAlert, "Vui lòng nhập ID VĐV.");
            return;
        }

        try {
            const result = await ui.requestJson(`${teamsApi}/${currentTeamId()}/members`, {
                method: "POST",
                body: JSON.stringify({
                    idvandongvien: athleteId,
                    vaitro: document.getElementById("a_role").value,
                }),
            });
            addModal.classList.add("hidden");
            ui.show(pageMessage, result.message || "Thêm thành viên thành công.");
            await loadMembers();
        } catch (error) {
            ui.showAlert(aAlert, ui.errorsText(error));
        }
    });

    tbody.addEventListener("click", async (event) => {
        const button = event.target.closest("button[data-action]");
        if (!button) return;
        currentMember = members.find((member) => String(member.idthanhvien) === String(button.dataset.id));
        if (!currentMember) return;

        if (button.dataset.action === "switch") {
            ui.hideAlert(sAlert);
            document.getElementById("s_name").textContent = `Chuyển thành viên: ${currentMember.hoten}`;
            document.getElementById("s_role").value = currentMember.vaitrotrongdoi || "THANH_VIEN";
            sTeam.value = "";
            switchModal.classList.remove("hidden");
            return;
        }

        if (!confirm("Xóa thành viên khỏi đội?")) return;

        try {
            const result = await ui.requestJson(`${teamsApi}/${currentTeamId()}/members/${currentMember.idthanhvien}/remove`, {
                method: "POST",
                body: JSON.stringify({ reason: "HLV xóa thành viên khỏi đội" }),
            });
            ui.show(pageMessage, result.message || "Xóa thành viên thành công.");
            await loadMembers();
        } catch (error) {
            ui.show(pageMessage, ui.errorsText(error), true);
        }
    });

    document.getElementById("s_submit").addEventListener("click", async () => {
        ui.hideAlert(sAlert);
        if (!currentMember || !sTeam.value) {
            ui.showAlert(sAlert, "Vui lòng chọn đội đích.");
            return;
        }

        try {
            const result = await ui.requestJson(`${teamsApi}/${currentTeamId()}/members/${currentMember.idthanhvien}/transfer`, {
                method: "POST",
                body: JSON.stringify({
                    target_team_id: sTeam.value,
                    vaitro: document.getElementById("s_role").value,
                    reason: document.getElementById("s_reason").value.trim() || null,
                }),
            });
            switchModal.classList.add("hidden");
            ui.show(pageMessage, result.message || "Chuyển đổi thành viên thành công.");
            await loadMembers();
        } catch (error) {
            ui.showAlert(sAlert, ui.errorsText(error));
        }
    });

    init().catch((error) => ui.show(pageMessage, ui.errorsText(error), true));
})();
