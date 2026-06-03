(function () {
    const page = document.querySelector(".athlete-leave");
    if (!page) return;

    const UI = window.AthleteUI;
    const leavesApi = page.dataset.leavesApi;
    const scheduleApi = page.dataset.scheduleApi;
    const message = document.getElementById("pageMessage");
    const tbody = document.getElementById("tbody");
    const modal = document.getElementById("leaveModal");
    const modalAlert = document.getElementById("modalAlert");
    const matchSelect = document.getElementById("match");
    const btnOpen = document.getElementById("btnOpen");
    const btnClose = document.getElementById("m_close");
    const btnCancel = document.getElementById("m_cancel");
    const btnSubmit = document.getElementById("m_submit");
    let matches = [];

    function leaveId(item) {
        return item.iddonnghi || item.iddonnghivdv || item.id || item.leave_id;
    }

    function matchLabel(match) {
        return `#${match.idtrandau} • ${match.doi1 || "-"} vs ${match.doi2 || "-"} • ${UI.formatDateTime(match.thoigianbatdau)}`;
    }

    function openModal() {
        UI.hideAlert(modalAlert);
        document.getElementById("from").value = "";
        document.getElementById("to").value = "";
        document.getElementById("reason").value = "";
        matchSelect.value = "";
        modal.classList.remove("hidden");
    }

    function closeModal() {
        modal.classList.add("hidden");
    }

    async function loadMatches() {
        try {
            const payload = await UI.requestJson(scheduleApi);
            matches = Array.isArray(payload.data) ? payload.data : [];
            matchSelect.innerHTML = `<option value="">-- Không gắn với trận --</option>` + matches.map((match) => (
                `<option value="${UI.escapeHtml(match.idtrandau)}">${UI.escapeHtml(matchLabel(match))}</option>`
            )).join("");
        } catch (error) {
            matchSelect.innerHTML = `<option value="">Không tải được lịch thi đấu</option>`;
        }
    }

    async function loadLeaves() {
        try {
            const payload = await UI.requestJson(leavesApi);
            const leaves = Array.isArray(payload.data) ? payload.data : [];
            tbody.innerHTML = leaves.length === 0
                ? `<tr><td colspan="8" class="empty">Chưa có yêu cầu xin nghỉ.</td></tr>`
                : leaves.map((item) => {
                    const id = leaveId(item);
                    const canCancel = item.trangthai === "CHO_DUYET";
                    const matchText = item.idtrandau
                        ? `#${item.idtrandau} • ${item.doi1 || ""}${item.doi2 ? ` vs ${item.doi2}` : ""}`
                        : "—";
                    return `
                        <tr>
                            <td>${UI.escapeHtml(id || "-")}</td>
                            <td>${UI.escapeHtml(matchText)}</td>
                            <td>${UI.escapeHtml(item.tungay || "-")}</td>
                            <td>${UI.escapeHtml(item.denngay || "-")}</td>
                            <td>${UI.escapeHtml(item.lydo || "-")}</td>
                            <td>${UI.badgeHtml(item.trangthai)}</td>
                            <td>${UI.escapeHtml(UI.formatDateTime(item.ngaygui))}</td>
                            <td><button class="btn danger" type="button" data-cancel="${UI.escapeHtml(id)}" ${canCancel ? "" : "disabled"}>Hủy</button></td>
                        </tr>
                    `;
                }).join("");
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="8" class="empty">${UI.escapeHtml(UI.errorsText(error))}</td></tr>`;
        }
    }

    function dateOnly(value) {
        return String(value || "").slice(0, 10);
    }

    async function submit() {
        UI.hideAlert(modalAlert);
        UI.showMessage(message, "");

        const matchId = matchSelect.value ? Number(matchSelect.value) : null;
        const selectedMatch = matches.find((match) => Number(match.idtrandau) === matchId);
        const from = document.getElementById("from").value || dateOnly(selectedMatch?.thoigianbatdau);
        const to = document.getElementById("to").value || from;
        const reason = document.getElementById("reason").value.trim();

        if (!from || !to || !reason) {
            UI.showAlert(modalAlert, "Vui lòng chọn trận hoặc khoảng thời gian và nhập lý do.");
            return;
        }

        if (new Date(to) < new Date(from)) {
            UI.showAlert(modalAlert, "Đến ngày phải lớn hơn hoặc bằng Từ ngày.");
            return;
        }

        try {
            await UI.requestJson(leavesApi, {
                method: "POST",
                body: JSON.stringify({
                    idtrandau: matchId,
                    tungay: from,
                    denngay: to,
                    lydo: reason,
                }),
            });
            closeModal();
            UI.showMessage(message, "Đã gửi yêu cầu xin nghỉ. Vui lòng chờ HLV duyệt.");
            await loadLeaves();
        } catch (error) {
            UI.showAlert(modalAlert, UI.errorsText(error));
        }
    }

    async function cancelLeave(id) {
        if (!id || !window.confirm("Hủy yêu cầu xin nghỉ này?")) return;

        try {
            await UI.requestJson(`${leavesApi}/${encodeURIComponent(id)}/cancel`, {
                method: "POST",
                body: JSON.stringify({ reason: "VDV hủy yêu cầu xin nghỉ thi đấu" }),
            });
            UI.showMessage(message, "Hủy yêu cầu xin nghỉ thành công.");
            await loadLeaves();
        } catch (error) {
            UI.showMessage(message, UI.errorsText(error), true);
        }
    }

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("[data-cancel]");
        if (button) cancelLeave(button.dataset.cancel);
    });

    btnOpen.addEventListener("click", openModal);
    btnClose.addEventListener("click", closeModal);
    btnCancel.addEventListener("click", closeModal);
    btnSubmit.addEventListener("click", submit);

    loadMatches();
    loadLeaves();
})();
