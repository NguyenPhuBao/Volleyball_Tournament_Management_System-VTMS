(function () {
    const UI = window.AthleteUI;
    const listPage = document.querySelector(".athlete-invitations");
    const detailPage = document.querySelector(".athlete-invitation-detail");

    function invitationId(item) {
        return item.idloimoi || item.id || item.invitation_id;
    }

    function teamName(item) {
        return item.tendoibong || item.team || item.team_name || "-";
    }

    function coachName(item) {
        return item.huanluyenvien_hoten || item.coach || item.coach_name || "-";
    }

    function statusOf(item) {
        return item.trangthai_hienthi || item.trangthai || item.status;
    }

    async function loadList() {
        if (!listPage) return;
        const tbody = document.getElementById("tbody");
        const empty = document.getElementById("empty");
        const message = document.getElementById("pageMessage");
        const api = listPage.dataset.invitationsApi;
        const detailUrl = listPage.dataset.detailUrl;

        try {
            const payload = await UI.requestJson(api);
            const items = Array.isArray(payload.data) ? payload.data : [];
            empty.classList.toggle("hidden", items.length > 0);

            if (items.length === 0) {
                tbody.innerHTML = "";
                return;
            }

            tbody.innerHTML = items.map((item) => {
                const id = invitationId(item);
                return `
                    <tr>
                        <td>${UI.escapeHtml(teamName(item))}</td>
                        <td>${UI.escapeHtml(item.vaitro || item.role || "VAN_DONG_VIEN")}</td>
                        <td>${UI.escapeHtml(coachName(item))}</td>
                        <td>${UI.escapeHtml(UI.formatDateTime(item.ngaygui || item.created_at || item.date))}</td>
                        <td>${UI.badgeHtml(statusOf(item))}</td>
                        <td><a class="btn" href="${UI.escapeHtml(detailUrl)}?id=${encodeURIComponent(id)}">Xem chi tiết</a></td>
                    </tr>
                `;
            }).join("");
        } catch (error) {
            UI.showMessage(message, UI.errorsText(error), true);
            tbody.innerHTML = `<tr><td colspan="6" class="empty">Không thể tải lời mời.</td></tr>`;
        }
    }

    async function loadDetail() {
        if (!detailPage) return;
        const id = new URLSearchParams(window.location.search).get("id");
        const api = detailPage.dataset.invitationsApi;
        const message = document.getElementById("pageMessage");
        const btnAccept = document.getElementById("btnAccept");
        const btnReject = document.getElementById("btnReject");

        if (!id) {
            UI.showMessage(message, "Thiếu mã lời mời.", true);
            btnAccept.disabled = true;
            btnReject.disabled = true;
            return;
        }

        let current = null;

        function render(item) {
            current = item;
            document.getElementById("teamName").textContent = teamName(item);
            document.getElementById("coachName").textContent = coachName(item);
            document.getElementById("role").textContent = item.vaitro || item.role || "VAN_DONG_VIEN";
            document.getElementById("status").innerHTML = UI.badgeHtml(statusOf(item));
            document.getElementById("desc").textContent = item.mota || item.ghichu || item.description || "—";
            document.getElementById("inviteSub").textContent = `${teamName(item)} • ${UI.formatDateTime(item.ngaygui || "")}`;

            const actionable = (item.trangthai || item.status) === "CHO_PHAN_HOI" && statusOf(item) !== "HET_HAN";
            btnAccept.disabled = !actionable;
            btnReject.disabled = !actionable;
        }

        async function submit(action) {
            if (!current) return;
            const endpoint = `${api}/${encodeURIComponent(id)}/${action}`;
            try {
                const payload = await UI.requestJson(endpoint, { method: "POST", body: "{}" });
                if (payload.data) render(payload.data);
                UI.showMessage(message, action === "accept" ? "Bạn đã đồng ý tham gia đội bóng." : "Bạn đã từ chối lời mời.");
            } catch (error) {
                UI.showMessage(message, UI.errorsText(error), true);
            }
        }

        btnAccept.addEventListener("click", () => submit("accept"));
        btnReject.addEventListener("click", () => submit("reject"));

        try {
            const payload = await UI.requestJson(`${api}/${encodeURIComponent(id)}`);
            render(payload.data || {});
        } catch (error) {
            UI.showMessage(message, UI.errorsText(error), true);
            btnAccept.disabled = true;
            btnReject.disabled = true;
        }
    }

    loadList();
    loadDetail();
})();
