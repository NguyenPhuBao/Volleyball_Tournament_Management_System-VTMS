(function () {
    const root = document.querySelector(".referee-leaves");

    if (!root) {
        return;
    }

    const leavesApi = root.dataset.leavesApi || "/api/trongtai/leaves";

    const tbody = document.getElementById("tbody");
    const q = document.getElementById("q");
    const statusFilter = document.getElementById("statusFilter");
    const fromDate = document.getElementById("fromDate");
    const toDate = document.getElementById("toDate");
    const btnRefresh = document.getElementById("btnRefresh");
    const pageAlert = document.getElementById("pageAlert");

    const sTotal = document.getElementById("sTotal");
    const sPending = document.getElementById("sPending");
    const sApproved = document.getElementById("sApproved");
    const sDays = document.getElementById("sDays");

    const btnCreate = document.getElementById("btnCreate");
    const createModal = document.getElementById("createModal");
    const mClose = document.getElementById("m_close");
    const mCancel = document.getElementById("m_cancel");
    const mSubmit = document.getElementById("m_submit");
    const mAlert = document.getElementById("m_alert");
    const mFrom = document.getElementById("m_from");
    const mTo = document.getElementById("m_to");
    const mReason = document.getElementById("m_reason");

    const detailModal = document.getElementById("detailModal");
    const dClose = document.getElementById("d_close");
    const dCloseBtn = document.getElementById("d_closeBtn");
    const dAlert = document.getElementById("d_alert");
    const dId = document.getElementById("d_id");
    const dStatus = document.getElementById("d_status");
    const dFrom = document.getElementById("d_from");
    const dTo = document.getElementById("d_to");
    const dDays = document.getElementById("d_days");
    const dReason = document.getElementById("d_reason");
    const dSent = document.getElementById("d_sent");
    const dDone = document.getElementById("d_done");

    const statusMap = {
        CHO_DUYET: ["wait", "Chờ duyệt"],
        DA_DUYET: ["ok", "Đã duyệt"],
        TU_CHOI: ["bad", "Từ chối"],
        DA_HUY: ["gray", "Đã hủy"],
    };

    let leaves = [];

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function responseData(payload) {
        return payload && Object.prototype.hasOwnProperty.call(payload, "data") ? payload.data : null;
    }

    function apiUrl(base, params = null) {
        const url = new URL(base, window.location.origin);

        if (params) {
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && String(value).trim() !== "") {
                    url.searchParams.set(key, value);
                }
            });
        }

        return url.toString();
    }

    async function requestJson(url, options = {}) {
        const response = await fetch(url, {
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                ...(options.headers || {}),
            },
            ...options,
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok || payload.success === false) {
            const error = new Error(payload.message || "Yêu cầu không thành công.");
            error.status = response.status;
            error.payload = payload;
            throw error;
        }

        return payload;
    }

    function showPage(message, isSuccess = false) {
        pageAlert.textContent = message || "";
        pageAlert.classList.toggle("hidden", !message);
        pageAlert.classList.toggle("success", Boolean(isSuccess));
    }

    function showModalAlert(element, message) {
        element.textContent = message || "";
        element.classList.toggle("hidden", !message);
    }

    function formatDateTime(value) {
        if (!value) {
            return "";
        }

        return String(value).replace("T", " ").slice(0, 19);
    }

    function statusBadge(status) {
        return statusMap[status] || ["gray", status || "-"];
    }

    function statusLabel(status) {
        return statusBadge(status)[1];
    }

    function todayDate() {
        return new Date().toISOString().slice(0, 10);
    }

    function dayCountInclusive(from, to) {
        const start = new Date(`${from}T00:00:00`);
        const end = new Date(`${to}T00:00:00`);

        return Math.round((end - start) / 86400000) + 1;
    }

    function updateStats(meta) {
        const stats = meta?.stats || {};
        sTotal.textContent = String(Number(stats.total ?? leaves.length));
        sPending.textContent = String(Number(stats.CHO_DUYET ?? leaves.filter((item) => item.trangthai === "CHO_DUYET").length));
        sApproved.textContent = String(Number(stats.DA_DUYET ?? leaves.filter((item) => item.trangthai === "DA_DUYET").length));
        sDays.textContent = String(Number(stats.approved_days ?? leaves
            .filter((item) => item.trangthai === "DA_DUYET")
            .reduce((sum, item) => sum + Number(item.songay || 0), 0)));
    }

    async function loadLeaves() {
        showPage("");

        try {
            const payload = await requestJson(apiUrl(leavesApi, {
                q: q.value.trim(),
                status: statusFilter.value,
                from: fromDate.value,
                to: toDate.value,
            }));
            leaves = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            updateStats(payload.meta);
            render();
        } catch (error) {
            leaves = [];
            updateStats(null);
            tbody.innerHTML = '<tr><td colspan="9" class="empty">Không thể tải đơn xin nghỉ.</td></tr>';
            showPage(error.message || "Không thể tải đơn xin nghỉ.");
        }
    }

    function render() {
        if (leaves.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="empty">Không có đơn xin nghỉ phù hợp.</td></tr>';
            return;
        }

        tbody.innerHTML = leaves.map((item) => {
            const [className, label] = statusBadge(item.trangthai);
            const canCancel = item.trangthai === "CHO_DUYET";

            return `
                <tr>
                    <td>${escapeHtml(item.iddonnghi)}</td>
                    <td>${escapeHtml(item.tungay)}</td>
                    <td>${escapeHtml(item.denngay)}</td>
                    <td>${escapeHtml(item.songay)}</td>
                    <td>${escapeHtml(item.lydo)}</td>
                    <td><span class="badge ${className}">${escapeHtml(label)}</span></td>
                    <td>${escapeHtml(formatDateTime(item.ngaygui))}</td>
                    <td>${escapeHtml(formatDateTime(item.ngayxuly))}</td>
                    <td>
                        <div style="display:flex; gap:8px; flex-wrap:wrap">
                            <button class="btn" type="button" data-action="view" data-id="${escapeHtml(item.iddonnghi)}">Xem</button>
                            <button class="btn danger" type="button" data-action="cancel" data-id="${escapeHtml(item.iddonnghi)}" ${canCancel ? "" : "disabled"}>Hủy xin nghỉ</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join("");
    }

    function openCreateModal() {
        showModalAlert(mAlert, "");
        const today = todayDate();
        mFrom.min = today;
        mTo.min = today;
        mFrom.value = "";
        mTo.value = "";
        mReason.value = "";
        createModal.classList.remove("hidden");
    }

    function closeCreateModal() {
        createModal.classList.add("hidden");
    }

    function closeDetailModal() {
        detailModal.classList.add("hidden");
    }

    function validateCreate() {
        const from = mFrom.value;
        const to = mTo.value;
        const reason = mReason.value.trim();

        if (!from || !to || !reason) {
            return "Vui lòng nhập đầy đủ: Từ ngày, Đến ngày, Lý do.";
        }

        if (from < todayDate()) {
            return "Từ ngày không được nhỏ hơn ngày hiện tại.";
        }

        if (to < from) {
            return "Đến ngày phải lớn hơn hoặc bằng Từ ngày.";
        }

        if (reason.length > 1000) {
            return "Lý do xin nghỉ phép không được vượt quá 1000 ký tự.";
        }

        return null;
    }

    async function submitLeave() {
        showModalAlert(mAlert, "");

        const error = validateCreate();

        if (error) {
            showModalAlert(mAlert, error);
            return;
        }

        if (!window.confirm(`Xác nhận gửi đơn xin nghỉ ${dayCountInclusive(mFrom.value, mTo.value)} ngày?`)) {
            return;
        }

        mSubmit.disabled = true;

        try {
            await requestJson(leavesApi, {
                method: "POST",
                body: JSON.stringify({
                    tungay: mFrom.value,
                    denngay: mTo.value,
                    lydo: mReason.value.trim(),
                }),
            });

            closeCreateModal();
            await loadLeaves();
            showPage("Xin nghỉ phép thành công", true);
        } catch (submitError) {
            const errors = submitError.payload?.errors || {};
            const firstError = Object.values(errors)[0];
            showModalAlert(mAlert, String(firstError || submitError.message || "Không thể gửi đơn xin nghỉ."));
        } finally {
            mSubmit.disabled = false;
        }
    }

    async function openDetail(leaveId) {
        showModalAlert(dAlert, "");
        dId.value = leaveId || "";
        dStatus.value = "";
        dFrom.value = "";
        dTo.value = "";
        dDays.value = "";
        dReason.value = "";
        dSent.value = "";
        dDone.value = "";
        detailModal.classList.remove("hidden");

        try {
            const payload = await requestJson(`${leavesApi.replace(/\/+$/, "")}/${encodeURIComponent(leaveId)}`);
            const detail = responseData(payload);

            if (!detail) {
                throw new Error("Không tìm thấy đơn xin nghỉ.");
            }

            dId.value = detail.iddonnghi || "";
            dStatus.value = statusLabel(detail.trangthai);
            dFrom.value = detail.tungay || "";
            dTo.value = detail.denngay || "";
            dDays.value = detail.songay || "";
            dReason.value = detail.lydo || "";
            dSent.value = formatDateTime(detail.ngaygui);
            dDone.value = formatDateTime(detail.ngayxuly);
        } catch (error) {
            showModalAlert(dAlert, error.message || "Không thể tải chi tiết đơn xin nghỉ.");
        }
    }

    async function cancelLeave(leaveId) {
        showPage("");

        const item = leaves.find((leave) => Number(leave.iddonnghi) === Number(leaveId));

        if (!item || item.trangthai !== "CHO_DUYET") {
            return;
        }

        if (!window.confirm("Hủy đơn xin nghỉ này?")) {
            return;
        }

        try {
            await requestJson(`${leavesApi.replace(/\/+$/, "")}/${encodeURIComponent(leaveId)}/cancel`, {
                method: "POST",
                body: JSON.stringify({
                    lydo: "Trọng tài hủy đơn nghỉ phép",
                }),
            });
            await loadLeaves();
            showPage("Hủy nghỉ phép thành công", true);
        } catch (error) {
            showPage(error.message || "Không thể hủy đơn xin nghỉ.");
        }
    }

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-action]");

        if (!button) {
            return;
        }

        if (button.dataset.action === "view") {
            openDetail(button.dataset.id);
        }

        if (button.dataset.action === "cancel") {
            cancelLeave(button.dataset.id);
        }
    });

    btnCreate.addEventListener("click", openCreateModal);
    mClose.addEventListener("click", closeCreateModal);
    mCancel.addEventListener("click", closeCreateModal);
    mSubmit.addEventListener("click", submitLeave);
    dClose.addEventListener("click", closeDetailModal);
    dCloseBtn.addEventListener("click", closeDetailModal);
    btnRefresh.addEventListener("click", loadLeaves);
    q.addEventListener("input", loadLeaves);
    statusFilter.addEventListener("change", loadLeaves);
    fromDate.addEventListener("change", loadLeaves);
    toDate.addEventListener("change", loadLeaves);

    loadLeaves();
})();
