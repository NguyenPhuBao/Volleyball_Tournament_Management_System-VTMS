(function () {
    const root = document.querySelector(".coach-players");
    if (!root) return;

    const ui = window.CoachUI;
    const athletesApi = root.dataset.athletesApi || "/api/coach/athletes";
    const teamsApi = root.dataset.teamsApi || "/api/coach/teams";

    const tbody = document.getElementById("tbody");
    const pageMessage = document.getElementById("pageMessage");
    const modal = document.getElementById("createModal");
    const alertBox = document.getElementById("m_alert");
    const teamSelect = document.getElementById("m_team");

    let teams = [];

    function openModal() {
        ui.hideAlert(alertBox);
        modal.classList.remove("hidden");
    }

    function closeModal() {
        modal.classList.add("hidden");
    }

    async function loadTeams() {
        const payload = await ui.requestJson(teamsApi);
        teams = payload.data || [];
        ui.fillSelect(teamSelect, teams, "iddoibong", "tendoibong", "Không gắn đội");

        if (!teamSelect.value && teams.length > 0) {
            teamSelect.value = teams[0].iddoibong;
        }
    }

    async function loadMembers() {
        if (teams.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty">Chưa có đội bóng để hiển thị VĐV.</td></tr>';
            return;
        }

        const team = teams[0];
        const payload = await ui.requestJson(`${teamsApi}/${team.iddoibong}/members`);
        const members = payload.data || [];

        if (members.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty">Đội hiện chưa có VĐV.</td></tr>';
            return;
        }

        tbody.innerHTML = members.map((member) => {
            const [badgeClass, label] = ui.badge(member.trangthaidaugiai || member.trangthaithanhvien);
            return `
                <tr>
                    <td>${ui.escapeHtml(member.idvandongvien)}</td>
                    <td>${ui.escapeHtml(member.hoten)}</td>
                    <td>${ui.escapeHtml(member.ngaysinh || "")}</td>
                    <td>${ui.escapeHtml(member.vitri || "")}</td>
                    <td>${ui.escapeHtml(member.email || "")}</td>
                    <td>${ui.escapeHtml(member.sodienthoai || "")}</td>
                    <td><span class="badge ${badgeClass}">${ui.escapeHtml(label)}</span></td>
                </tr>
            `;
        }).join("");
    }

    async function init() {
        try {
            await loadTeams();
            await loadMembers();
        } catch (error) {
            ui.show(pageMessage, ui.errorsText(error), true);
            tbody.innerHTML = '<tr><td colspan="7" class="empty">Không tải được dữ liệu.</td></tr>';
        }
    }

    document.getElementById("btnCreate").addEventListener("click", openModal);
    document.getElementById("m_close").addEventListener("click", closeModal);
    document.getElementById("m_cancel").addEventListener("click", closeModal);

    document.getElementById("m_submit").addEventListener("click", async () => {
        ui.hideAlert(alertBox);
        ui.show(pageMessage, "");

        const name = ui.splitName(document.getElementById("m_name").value);
        const payload = {
            username: document.getElementById("m_username").value.trim(),
            password: document.getElementById("m_password").value,
            hodem: name.hodem,
            ten: name.ten,
            gioitinh: document.getElementById("m_gender").value,
            ngaysinh: document.getElementById("m_dob").value,
            vitri: document.getElementById("m_position").value,
            email: document.getElementById("m_email").value.trim(),
            phone: document.getElementById("m_phone").value.trim(),
            team_id: teamSelect.value || null,
            team_role: document.getElementById("m_teamRole").value,
            membership_status: "DANG_THAM_GIA",
        };

        for (const key of ["username", "password", "ten", "gioitinh", "ngaysinh", "vitri", "email", "phone"]) {
            if (!payload[key]) {
                ui.showAlert(alertBox, "Vui lòng nhập đầy đủ thông tin.");
                return;
            }
        }

        if (!payload.hodem) {
            ui.showAlert(alertBox, "Vui lòng nhập đầy đủ họ và tên.");
            return;
        }

        try {
            const result = await ui.requestJson(athletesApi, {
                method: "POST",
                body: JSON.stringify(payload),
            });
            closeModal();
            ui.show(pageMessage, result.message || "Tạo tài khoản VĐV thành công.");
            await init();
        } catch (error) {
            ui.showAlert(alertBox, ui.errorsText(error));
        }
    });

    init();
})();
