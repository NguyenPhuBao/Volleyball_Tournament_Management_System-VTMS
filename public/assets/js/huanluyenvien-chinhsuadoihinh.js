(function () {
    const root = document.querySelector(".coach-lineup-editor");
    if (!root) return;

    const ui = window.CoachUI;
    const teamsApi = root.dataset.teamsApi || "/api/coach/teams";
    const query = new URLSearchParams(window.location.search);
    const requestedTeamId = query.get("team_id") || query.get("iddoibong");
    let requestedLineupId = query.get("lineup_id") || query.get("iddoihinh");

    const teamSelect = document.getElementById("teamSelect");
    const lineupSelect = document.getElementById("lineupSelect");
    const lineupGender = document.getElementById("lineupGender");
    const lineupMain = document.getElementById("lineupMain");
    const playerList = document.getElementById("playerList");
    const lineupBody = document.getElementById("lineupBody");
    const alertBox = document.getElementById("alert");
    const pageMessage = document.getElementById("pageMessage");

    let teams = [];
    let members = [];
    let lineups = [];
    let lineupDetails = [];
    let selected = [];

    function genderLabel(value) {
        return value === "NU" ? "Nữ" : "Nam";
    }

    function selectedGender() {
        return lineupGender?.value || "NAM";
    }

    function renderMembers() {
        const gender = selectedGender();
        const active = members.filter((member) => (
            member.trangthaithanhvien === "DANG_THAM_GIA"
            && String(member.gioitinh || "").toUpperCase() === gender
        ));
        if (active.length === 0) {
            playerList.innerHTML = `<li class="empty">Không có VĐV ${genderLabel(gender).toLowerCase()} đang tham gia.</li>`;
            return;
        }

        playerList.innerHTML = active.map((member) => `
            <li><button class="btn" type="button" data-id="${ui.escapeHtml(member.idvandongvien)}">${ui.escapeHtml(member.hoten)} - ${ui.escapeHtml(member.vitri || "")} (${genderLabel(member.gioitinh)})</button></li>
        `).join("");
    }

    function renderSelected() {
        if (selected.length === 0) {
            lineupBody.innerHTML = '<tr><td colspan="4" class="empty">Chưa chọn VĐV.</td></tr>';
            return;
        }

        lineupBody.innerHTML = selected.map((item, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>${ui.escapeHtml(item.hoten)}</td>
                <td>
                    <select data-id="${ui.escapeHtml(item.idvandongvien)}" data-field="position">
                        ${["CHU_CONG", "PHU_CONG", "CHUYEN_HAI", "DOI_CHUYEN", "LIBERO", "DOI_TRU"].map((position) => (
                            `<option value="${position}" ${position === item.vitri ? "selected" : ""}>${position}</option>`
                        )).join("")}
                    </select>
                </td>
                <td><button class="btn danger" type="button" data-remove="${ui.escapeHtml(item.idvandongvien)}">Xóa</button></td>
            </tr>
        `).join("");
    }

    function fillLineups(selectedValue = lineupSelect.value) {
        let html = '<option value="">Tạo đội hình mới</option>';
        html += lineups.map((lineup) => {
            const selectedAttr = String(lineup.iddoihinh) === String(selectedValue) ? "selected" : "";
            return `<option value="${ui.escapeHtml(lineup.iddoihinh)}" ${selectedAttr}>${ui.escapeHtml(lineup.tendoihinh)}</option>`;
        }).join("");
        lineupSelect.innerHTML = html;
    }

    async function loadBase() {
        const teamsPayload = await ui.requestJson(teamsApi);
        teams = teamsPayload.data || [];
        ui.fillSelect(teamSelect, teams, "iddoibong", "tendoibong", "Chọn đội bóng");

        if (requestedTeamId && teams.some((team) => String(team.iddoibong) === String(requestedTeamId))) {
            teamSelect.value = requestedTeamId;
        } else if (!teamSelect.value && teams.length > 0) {
            teamSelect.value = teams[0].iddoibong;
        }
    }

    async function loadTeamData() {
        if (!teamSelect.value) return;
        const memberPayload = await ui.requestJson(`${teamsApi}/${teamSelect.value}/members`);
        members = memberPayload.data || [];
        renderMembers();
    }

    async function loadLineups() {
        const currentLineupId = requestedLineupId || lineupSelect.value;
        lineups = [];
        lineupDetails = [];
        fillLineups();
        if (!teamSelect.value) return;
        const payload = await ui.requestJson(`${teamsApi}/${teamSelect.value}/lineups`);
        lineups = payload.data || [];
        lineupDetails = payload.details || [];
        const preferredLineupId = currentLineupId;
        const hasPreferredLineup = preferredLineupId && lineups.some((lineup) => String(lineup.iddoihinh) === String(preferredLineupId));
        fillLineups(hasPreferredLineup ? preferredLineupId : "");
        if (hasPreferredLineup) {
            requestedLineupId = null;
            hydrateExistingLineup();
        }
    }

    function hydrateExistingLineup() {
        const lineup = lineups.find((item) => String(item.iddoihinh) === String(lineupSelect.value));
        if (!lineup) {
            document.getElementById("lineupName").value = "";
            document.getElementById("lineupStatus").value = "BAN_NHAP";
            lineupGender.value = "NAM";
            lineupMain.checked = false;
            selected = [];
            renderMembers();
            renderSelected();
            return;
        }

        document.getElementById("lineupName").value = lineup.tendoihinh || "";
        document.getElementById("lineupStatus").value = lineup.trangthai || "BAN_NHAP";
        lineupGender.value = lineup.gioitinh || "NAM";
        lineupMain.checked = Number(lineup.la_doihinh_chinh || 0) === 1;
        selected = lineupDetails
            .filter((detail) => String(detail.iddoihinh) === String(lineup.iddoihinh))
            .map((detail) => ({
                idvandongvien: Number(detail.idvandongvien),
                hoten: detail.hoten,
                vitri: detail.vitri,
                gioitinh: detail.gioitinh,
            }));
        selected = selected.filter((item) => String(item.gioitinh || "").toUpperCase() === selectedGender());
        renderMembers();
        renderSelected();
    }

    playerList.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-id]");
        if (!button) return;
        const member = members.find((item) => String(item.idvandongvien) === String(button.dataset.id));
        if (!member || selected.some((item) => Number(item.idvandongvien) === Number(member.idvandongvien))) return;
        if (String(member.gioitinh || "").toUpperCase() !== selectedGender()) return;
        selected.push({ idvandongvien: Number(member.idvandongvien), hoten: member.hoten, vitri: member.vitri || "CHU_CONG", gioitinh: member.gioitinh });
        renderSelected();
    });

    lineupBody.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-remove]");
        if (!button) return;
        selected = selected.filter((item) => String(item.idvandongvien) !== String(button.dataset.remove));
        renderSelected();
    });

    lineupBody.addEventListener("change", (event) => {
        const select = event.target.closest("select[data-field='position']");
        if (!select) return;
        const item = selected.find((row) => String(row.idvandongvien) === String(select.dataset.id));
        if (item) item.vitri = select.value;
    });

    async function refreshAfterSelect() {
        ui.hideAlert(alertBox);
        selected = [];
        renderSelected();
        await loadTeamData();
        await loadLineups();
    }

    teamSelect.addEventListener("change", () => refreshAfterSelect().catch((error) => ui.show(pageMessage, ui.errorsText(error), true)));
    lineupSelect.addEventListener("change", hydrateExistingLineup);
    lineupGender.addEventListener("change", () => {
        selected = selected.filter((item) => String(item.gioitinh || "").toUpperCase() === selectedGender());
        renderMembers();
        renderSelected();
    });

    document.getElementById("btnSave").addEventListener("click", async () => {
        ui.hideAlert(alertBox);
        if (!teamSelect.value || !document.getElementById("lineupName").value.trim()) {
            ui.showAlert(alertBox, "Vui lòng chọn đội và nhập tên đội hình.");
            return;
        }
        if (selected.length === 0) {
            ui.showAlert(alertBox, "Vui lòng chọn ít nhất 1 VĐV.");
            return;
        }

        const payload = {
            tendoihinh: document.getElementById("lineupName").value.trim(),
            trangthai: document.getElementById("lineupStatus").value,
            gioitinh: selectedGender(),
            la_doihinh_chinh: lineupMain.checked ? 1 : 0,
            details: selected.map((item, index) => ({
                idvandongvien: item.idvandongvien,
                vitri: item.vitri,
                sothutu: index + 1,
            })),
        };

        try {
            const editingId = lineupSelect.value;
            const result = await ui.requestJson(editingId ? `${teamsApi.replace(/\/teams$/, "/lineups")}/${editingId}` : `${teamsApi}/${teamSelect.value}/lineups`, {
                method: editingId ? "PUT" : "POST",
                body: JSON.stringify(payload),
            });
            ui.show(pageMessage, result.message || "Lưu đội hình thành công.");
            await loadLineups();
        } catch (error) {
            ui.showAlert(alertBox, ui.errorsText(error));
        }
    });

    loadBase()
        .then(refreshAfterSelect)
        .catch((error) => ui.show(pageMessage, ui.errorsText(error), true));
})();
