(function () {
    const page = document.querySelector(".athlete-team-detail");
    if (!page) return;

    const UI = window.AthleteUI;
    const api = page.dataset.teamsApi;
    const message = document.getElementById("pageMessage");
    const teamInfo = document.getElementById("teamInfo");
    const coach = document.getElementById("coach");
    const members = document.getElementById("members");

    async function load() {
        try {
            const listPayload = await UI.requestJson(api);
            const teams = Array.isArray(listPayload.data) ? listPayload.data : [];
            const active = teams.find((team) => team.trangthaithanhvien === "DANG_THAM_GIA") || teams[0];

            if (!active) {
                teamInfo.innerHTML = `<p class="empty">Bạn chưa thuộc đội bóng nào.</p>`;
                members.innerHTML = `<tr><td colspan="4" class="empty">—</td></tr>`;
                return;
            }

            const detailPayload = await UI.requestJson(`${api}/${encodeURIComponent(active.iddoibong)}`);
            const team = detailPayload.data || active;
            const detailMembers = Array.isArray(detailPayload.members) ? detailPayload.members : [];

            teamInfo.innerHTML = `
                <div class="athlete-grid">
                    <div>
                        <img class="logo" src="${UI.escapeHtml(team.logo || "https://placehold.co/100x100?text=VTMS")}" alt="">
                    </div>
                    <div>
                        <h2>${UI.escapeHtml(team.tendoibong || "-")}</h2>
                        <p><b>Địa phương:</b> ${UI.escapeHtml(team.diaphuong || "-")}</p>
                        <p><b>Trạng thái đội:</b> ${UI.badgeHtml(team.trangthaidoibong)}</p>
                        <p><b>Vai trò của bạn:</b> ${UI.escapeHtml(team.vaitrotrongdoi || "-")}</p>
                        <p><b>Giải đấu:</b> ${UI.escapeHtml(team.tournament_names || "-")}</p>
                    </div>
                </div>
            `;
            coach.textContent = `${team.huanluyenvien_hoten || "-"}${team.huanluyenvien_email ? ` (${team.huanluyenvien_email})` : ""}`;

            members.innerHTML = detailMembers.length === 0
                ? `<tr><td colspan="4" class="empty">Chưa có thành viên.</td></tr>`
                : detailMembers.map((member) => `
                    <tr>
                        <td>${UI.escapeHtml(member.hoten || member.username || "-")}</td>
                        <td>${UI.escapeHtml(member.vaitro || "-")}</td>
                        <td>${UI.escapeHtml(member.vitri || "-")}</td>
                        <td>${UI.escapeHtml([member.email, member.sodienthoai].filter(Boolean).join(" / ") || "-")}</td>
                    </tr>
                `).join("");
        } catch (error) {
            UI.showMessage(message, UI.errorsText(error), true);
        }
    }

    load();
})();
