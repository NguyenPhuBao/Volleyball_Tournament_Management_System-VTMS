(function () {
    const root = document.querySelector(".coach-requests");
    if (!root) return;

    const ui = window.CoachUI;
    const requestsApi = root.dataset.requestsApi || "/api/coach/athlete-change-requests";
    const leavesApi = root.dataset.leavesApi || "/api/coach/athlete-leaves";
    const tbody = document.getElementById("tbody");
    const empty = document.getElementById("empty");
    const table = document.getElementById("requestTable");
    const pageMessage = document.getElementById("pageMessage");
    const modal = document.getElementById("detailModal");
    const alertBox = document.getElementById("m_alert");
    const leaveTbody = document.getElementById("leaveTbody");
    const leaveEmpty = document.getElementById("leaveEmpty");
    const leaveTable = document.getElementById("leaveTable");
    const leaveMessage = document.getElementById("leaveMessage");
    const leaveModal = document.getElementById("leaveDetailModal");
    const leaveAlertBox = document.getElementById("leave_m_alert");

    let requests = [];
    let current = null;
    let leaves = [];
    let currentLeave = null;

    function render() {
        if (requests.length === 0) {
            table.classList.add("hidden");
            empty.classList.remove("hidden");
            return;
        }

        table.classList.remove("hidden");
        empty.classList.add("hidden");
        tbody.innerHTML = requests.map((request) => {
            const [badgeClass, label] = ui.badge(request.trangthai);
            return `
                <tr>
                    <td>${ui.escapeHtml(request.idyeucaucapnhat)}</td>
                    <td>${ui.escapeHtml(request.hoten || request.username || "")}</td>
                    <td>${ui.escapeHtml(request.banglienquan)}</td>
                    <td>${ui.escapeHtml(request.truongcapnhat)}</td>
                    <td>${ui.escapeHtml(request.giatricu ?? "")}</td>
                    <td>${ui.escapeHtml(request.giatrimoi ?? "")}</td>
                    <td>${ui.escapeHtml(request.ngaygui || "")}</td>
                    <td><span class="badge ${badgeClass}">${ui.escapeHtml(label)}</span></td>
                    <td><button class="btn" type="button" data-id="${ui.escapeHtml(request.idyeucaucapnhat)}">Xem chi tiết</button></td>
                </tr>
            `;
        }).join("");
    }

    function renderLeaves() {
        if (leaves.length === 0) {
            leaveTable.classList.add("hidden");
            leaveEmpty.classList.remove("hidden");
            return;
        }

        leaveTable.classList.remove("hidden");
        leaveEmpty.classList.add("hidden");
        leaveTbody.innerHTML = leaves.map((leave) => {
            const [badgeClass, label] = ui.badge(leave.trangthai);
            const matchLabel = leave.idtrandau
                ? `#${leave.idtrandau} - ${leave.doi1 || ""} vs ${leave.doi2 || ""}`
                : "Không gắn trận";
            return `
                <tr>
                    <td>${ui.escapeHtml(leave.iddonnghi)}</td>
                    <td>${ui.escapeHtml(leave.hoten || leave.username || leave.mavandongvien || "")}</td>
                    <td>${ui.escapeHtml(leave.tendoibong || "")}</td>
                    <td>
                        <strong>${ui.escapeHtml(matchLabel)}</strong><br>
                        <span class="muted">${ui.escapeHtml(leave.tengiaidau || "")}</span>
                    </td>
                    <td>${ui.escapeHtml(leave.tungay || "")}</td>
                    <td>${ui.escapeHtml(leave.denngay || "")}</td>
                    <td>${ui.escapeHtml(leave.lydo || "")}</td>
                    <td><span class="badge ${badgeClass}">${ui.escapeHtml(label)}</span></td>
                    <td><button class="btn" type="button" data-leave-id="${ui.escapeHtml(leave.iddonnghi)}">Xem chi tiết</button></td>
                </tr>
            `;
        }).join("");
    }

    async function load() {
        const params = {
            q: document.getElementById("q").value.trim(),
            status: document.getElementById("statusFilter").value,
            from: document.getElementById("fromDate").value,
            to: document.getElementById("toDate").value,
        };
        const payload = await ui.requestJson(ui.apiUrl(requestsApi, params));
        requests = payload.data || [];
        render();
    }

    async function loadLeaves() {
        const params = {
            q: document.getElementById("leaveQ").value.trim(),
            status: document.getElementById("leaveStatusFilter").value,
            from: document.getElementById("leaveFromDate").value,
            to: document.getElementById("leaveToDate").value,
        };
        const payload = await ui.requestJson(ui.apiUrl(leavesApi, params));
        leaves = payload.data || [];
        renderLeaves();
    }

    function openDetail(id) {
        current = requests.find((request) => String(request.idyeucaucapnhat) === String(id));
        if (!current) return;
        ui.hideAlert(alertBox);
        document.getElementById("oldInfo").textContent = JSON.stringify({
            van_dong_vien: current.hoten,
            bang: current.banglienquan,
            truong: current.truongcapnhat,
            gia_tri_cu: current.giatricu,
            ly_do: current.lydo,
        }, null, 2);
        document.getElementById("newInfo").textContent = JSON.stringify({
            gia_tri_moi: current.giatrimoi,
            trang_thai: current.trangthai,
            ngay_gui: current.ngaygui,
            ngay_xu_ly: current.ngayxuly,
        }, null, 2);
        document.getElementById("m_note").value = "";
        const actionable = current.trangthai === "CHO_DUYET";
        document.getElementById("btnApprove").disabled = !actionable;
        document.getElementById("btnReject").disabled = !actionable;
        modal.classList.remove("hidden");
    }

    function openLeaveDetail(id) {
        currentLeave = leaves.find((leave) => String(leave.iddonnghi) === String(id));
        if (!currentLeave) return;
        ui.hideAlert(leaveAlertBox);
        document.getElementById("leaveAthleteInfo").textContent = JSON.stringify({
            van_dong_vien: currentLeave.hoten || currentLeave.username,
            ma_vdv: currentLeave.mavandongvien,
            doi_bong: currentLeave.tendoibong,
        }, null, 2);
        document.getElementById("leaveInfo").textContent = JSON.stringify({
            tu_ngay: currentLeave.tungay,
            den_ngay: currentLeave.denngay,
            so_ngay: currentLeave.songay,
            ly_do: currentLeave.lydo,
            tran: currentLeave.idtrandau ? `#${currentLeave.idtrandau} - ${currentLeave.doi1 || ""} vs ${currentLeave.doi2 || ""}` : "Không gắn trận",
            giai_dau: currentLeave.tengiaidau,
            trang_thai: currentLeave.trangthai,
            ngay_gui: currentLeave.ngaygui,
            ngay_xu_ly: currentLeave.ngayxuly,
        }, null, 2);
        document.getElementById("leave_m_note").value = "";
        const actionable = currentLeave.trangthai === "CHO_DUYET";
        document.getElementById("btnLeaveApprove").disabled = !actionable;
        document.getElementById("btnLeaveReject").disabled = !actionable;
        leaveModal.classList.remove("hidden");
    }

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-id]");
        if (button) openDetail(button.dataset.id);
    });

    leaveTbody.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-leave-id]");
        if (button) openLeaveDetail(button.dataset.leaveId);
    });

    document.getElementById("m_close").addEventListener("click", () => modal.classList.add("hidden"));
    document.getElementById("leave_m_close").addEventListener("click", () => leaveModal.classList.add("hidden"));
    document.getElementById("btnRefresh").addEventListener("click", () => load().catch((error) => ui.show(pageMessage, ui.errorsText(error), true)));
    document.getElementById("btnLeaveRefresh").addEventListener("click", () => loadLeaves().catch((error) => ui.show(leaveMessage, ui.errorsText(error), true)));

    ["q", "statusFilter", "fromDate", "toDate"].forEach((id) => {
        const element = document.getElementById(id);
        element.addEventListener("change", () => load().catch(() => {}));
        element.addEventListener("input", () => load().catch(() => {}));
    });

    ["leaveQ", "leaveStatusFilter", "leaveFromDate", "leaveToDate"].forEach((id) => {
        const element = document.getElementById(id);
        element.addEventListener("change", () => loadLeaves().catch(() => {}));
        element.addEventListener("input", () => loadLeaves().catch(() => {}));
    });

    document.getElementById("btnApprove").addEventListener("click", async () => {
        if (!current) return;
        try {
            const result = await ui.requestJson(`${requestsApi}/${current.idyeucaucapnhat}/approve`, {
                method: "POST",
                body: JSON.stringify({ note: "HLV duyệt thay đổi thông tin VĐV" }),
            });
            modal.classList.add("hidden");
            ui.show(pageMessage, result.message || "Đã duyệt yêu cầu.");
            await load();
        } catch (error) {
            ui.showAlert(alertBox, ui.errorsText(error));
        }
    });

    document.getElementById("btnReject").addEventListener("click", async () => {
        if (!current) return;
        const note = document.getElementById("m_note").value.trim();
        if (!note) {
            ui.showAlert(alertBox, "Vui lòng nhập ghi chú khi từ chối.");
            return;
        }
        try {
            const result = await ui.requestJson(`${requestsApi}/${current.idyeucaucapnhat}/reject`, {
                method: "POST",
                body: JSON.stringify({ note }),
            });
            modal.classList.add("hidden");
            ui.show(pageMessage, result.message || "Đã từ chối yêu cầu.");
            await load();
        } catch (error) {
            ui.showAlert(alertBox, ui.errorsText(error));
        }
    });

    document.getElementById("btnLeaveApprove").addEventListener("click", async () => {
        if (!currentLeave) return;
        try {
            const result = await ui.requestJson(`${leavesApi}/${currentLeave.iddonnghi}/approve`, {
                method: "POST",
                body: JSON.stringify({ note: "HLV duyệt đơn xin nghỉ phép VĐV" }),
            });
            leaveModal.classList.add("hidden");
            ui.show(leaveMessage, result.message || "Đã duyệt đơn xin nghỉ phép.");
            await loadLeaves();
        } catch (error) {
            ui.showAlert(leaveAlertBox, ui.errorsText(error));
        }
    });

    document.getElementById("btnLeaveReject").addEventListener("click", async () => {
        if (!currentLeave) return;
        const note = document.getElementById("leave_m_note").value.trim();
        if (!note) {
            ui.showAlert(leaveAlertBox, "Vui lòng nhập ghi chú khi từ chối.");
            return;
        }
        try {
            const result = await ui.requestJson(`${leavesApi}/${currentLeave.iddonnghi}/reject`, {
                method: "POST",
                body: JSON.stringify({ note }),
            });
            leaveModal.classList.add("hidden");
            ui.show(leaveMessage, result.message || "Đã từ chối đơn xin nghỉ phép.");
            await loadLeaves();
        } catch (error) {
            ui.showAlert(leaveAlertBox, ui.errorsText(error));
        }
    });

    load().catch((error) => ui.show(pageMessage, ui.errorsText(error), true));
    loadLeaves().catch((error) => ui.show(leaveMessage, ui.errorsText(error), true));
})();
