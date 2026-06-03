const root = document.querySelector(".admin-approvals");
const approvalsApi = root?.dataset.approvalsApi || "/api/admin/organizer-change-requests";

let approvals = [];
let current = null;
let searchTimer = null;

const tbody = document.getElementById("tbody");
const q = document.getElementById("q");
const statusFilter = document.getElementById("statusFilter");
const fromDate = document.getElementById("fromDate");
const toDate = document.getElementById("toDate");
const btnRefresh = document.getElementById("btnRefresh");
const pageMessage = document.getElementById("pageMessage");

const detailModal = document.getElementById("detailModal");
const rejectModal = document.getElementById("rejectModal");
const detailAlert = document.getElementById("detailAlert");

const sPending = document.getElementById("sPending");
const sApproved = document.getElementById("sApproved");
const sRejected = document.getElementById("sRejected");

const modalFields = {
    id: document.getElementById("m_id"),
    status: document.getElementById("m_status"),
    sender: document.getElementById("m_sender"),
    donvi: document.getElementById("m_donvi"),
    table: document.getElementById("m_table"),
    field: document.getElementById("m_field"),
    oldValue: document.getElementById("m_old"),
    newValue: document.getElementById("m_new"),
    reason: document.getElementById("m_reason"),
    rejectNote: document.getElementById("r_note"),
};

const btnClose = document.getElementById("btnClose");
const btnApprove = document.getElementById("btnApprove");
const btnReject = document.getElementById("btnReject");
const btnRejectClose = document.getElementById("btnRejectClose");
const btnRejectCancel = document.getElementById("btnRejectCancel");
const btnRejectConfirm = document.getElementById("btnRejectConfirm");

const statusLabels = {
    CHO_DUYET: "Chờ duyệt",
    DA_DUYET: "Đã duyệt",
    TU_CHOI: "Từ chối",
};

function requestId(item) {
    return Number(item.idyeucaucapnhat || item.id);
}

function senderName(item) {
    return (item.hoten || `${item.hodem || ""} ${item.ten || ""}` || item.username || "").trim();
}

function badgeClass(status) {
    if (status === "CHO_DUYET") {
        return "pending";
    }

    if (status === "DA_DUYET") {
        return "approved";
    }

    if (status === "TU_CHOI") {
        return "rejected";
    }

    return "";
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function setPageMessage(message, success = false) {
    pageMessage.textContent = message || "";
    pageMessage.classList.toggle("success", success);
}

function showDetailAlert(message) {
    detailAlert.textContent = message;
    detailAlert.classList.remove("hidden");
}

function hideDetailAlert() {
    detailAlert.textContent = "";
    detailAlert.classList.add("hidden");
}

function computeStats(meta = {}) {
    const counts = meta.status_counts || {};
    sPending.textContent = counts.CHO_DUYET || 0;
    sApproved.textContent = counts.DA_DUYET || 0;
    sRejected.textContent = counts.TU_CHOI || 0;
}

function render() {
    if (approvals.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="empty">Không có yêu cầu phù hợp.</td></tr>';
        return;
    }

    tbody.innerHTML = approvals.map((item) => {
        const id = requestId(item);
        const status = item.trangthai || "";
        const oldValue = item.giatricu ?? "";
        const newValue = item.giatrimoi ?? "";

        return `
            <tr>
                <td>${id}</td>
                <td>${escapeHtml(senderName(item) || item.username || "")}</td>
                <td><span class="truncate" title="${escapeHtml(item.current_donvi || "")}">${escapeHtml(item.current_donvi || "")}</span></td>
                <td>${escapeHtml(item.banglienquan)}</td>
                <td>${escapeHtml(item.truongcapnhat)}</td>
                <td><span class="truncate" title="${escapeHtml(oldValue)}">${escapeHtml(oldValue)}</span></td>
                <td><span class="truncate" title="${escapeHtml(newValue)}">${escapeHtml(newValue)}</span></td>
                <td><span class="badge ${badgeClass(status)}">${escapeHtml(statusLabels[status] || status)}</span></td>
                <td>${escapeHtml(item.ngaygui)}</td>
                <td><button class="btn" type="button" data-action="detail" data-id="${id}">Xem</button></td>
            </tr>
        `;
    }).join("");
}

async function apiRequest(url, options = {}) {
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
        const details = payload.errors ? Object.values(payload.errors).join(" ") : "";
        throw new Error([payload.message, details].filter(Boolean).join(" ") || "Yêu cầu không thành công.");
    }

    return payload;
}

function buildUrl() {
    const params = new URLSearchParams();
    const keyword = q.value.trim();

    if (keyword !== "") {
        params.set("q", keyword);
    }

    if (statusFilter.value !== "") {
        params.set("status", statusFilter.value);
    }

    if (fromDate.value !== "") {
        params.set("from", fromDate.value);
    }

    if (toDate.value !== "") {
        params.set("to", toDate.value);
    }

    params.set("per_page", "100");

    return `${approvalsApi}?${params.toString()}`;
}

async function loadApprovals() {
    tbody.innerHTML = '<tr><td colspan="10" class="empty">Đang tải dữ liệu...</td></tr>';
    setPageMessage("");

    try {
        const payload = await apiRequest(buildUrl());
        approvals = payload.data || [];
        computeStats(payload.meta || {});
        render();
    } catch (error) {
        approvals = [];
        computeStats();
        render();
        setPageMessage(error.message);
    }
}

async function openDetail(id) {
    hideDetailAlert();
    setPageMessage("");

    try {
        const payload = await apiRequest(`${approvalsApi}/${id}`);
        current = payload.data;
        fillDetail(current);
        detailModal.classList.remove("hidden");
        detailModal.setAttribute("aria-hidden", "false");
    } catch (error) {
        setPageMessage(error.message);
    }
}

function fillDetail(item) {
    const status = item.trangthai || "";
    const actionable = status === "CHO_DUYET";

    modalFields.id.value = requestId(item);
    modalFields.status.value = statusLabels[status] || status;
    modalFields.sender.value = senderName(item) || item.username || "";
    modalFields.donvi.value = item.current_donvi || "";
    modalFields.table.value = item.banglienquan || "";
    modalFields.field.value = item.truongcapnhat || "";
    modalFields.oldValue.value = item.giatricu ?? "";
    modalFields.newValue.value = item.giatrimoi ?? "";
    modalFields.reason.value = item.lydo ?? "";

    btnApprove.disabled = !actionable;
    btnReject.disabled = !actionable;
}

function closeDetail() {
    detailModal.classList.add("hidden");
    detailModal.setAttribute("aria-hidden", "true");
    current = null;
    hideDetailAlert();
}

function closeReject() {
    rejectModal.classList.add("hidden");
    rejectModal.setAttribute("aria-hidden", "true");
    modalFields.rejectNote.value = "";
}

async function approveCurrent() {
    if (!current) {
        return;
    }

    btnApprove.disabled = true;
    btnReject.disabled = true;
    hideDetailAlert();

    try {
        await apiRequest(`${approvalsApi}/${requestId(current)}/approve`, {
            method: "POST",
            body: JSON.stringify({}),
        });

        closeDetail();
        setPageMessage("Xác nhận yêu cầu thành công.", true);
        await loadApprovals();
    } catch (error) {
        showDetailAlert(error.message);
        fillDetail(current);
    }
}

async function rejectCurrent() {
    if (!current) {
        return;
    }

    const note = modalFields.rejectNote.value.trim();

    if (note === "") {
        closeReject();
        showDetailAlert("Vui lòng nhập lý do từ chối.");
        return;
    }

    btnRejectConfirm.disabled = true;

    try {
        await apiRequest(`${approvalsApi}/${requestId(current)}/reject`, {
            method: "POST",
            body: JSON.stringify({ lydo: note }),
        });

        closeReject();
        closeDetail();
        setPageMessage("Từ chối yêu cầu thành công.", true);
        await loadApprovals();
    } catch (error) {
        closeReject();
        showDetailAlert(error.message);
    } finally {
        btnRejectConfirm.disabled = false;
    }
}

tbody.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-action='detail']");

    if (!button) {
        return;
    }

    openDetail(Number(button.dataset.id));
});

btnClose.addEventListener("click", closeDetail);
btnRefresh.addEventListener("click", loadApprovals);
btnApprove.addEventListener("click", approveCurrent);
btnReject.addEventListener("click", () => {
    modalFields.rejectNote.value = "";
    rejectModal.classList.remove("hidden");
    rejectModal.setAttribute("aria-hidden", "false");
    modalFields.rejectNote.focus();
});
btnRejectClose.addEventListener("click", closeReject);
btnRejectCancel.addEventListener("click", closeReject);
btnRejectConfirm.addEventListener("click", rejectCurrent);

detailModal.addEventListener("click", (event) => {
    if (event.target === detailModal) {
        closeDetail();
    }
});

rejectModal.addEventListener("click", (event) => {
    if (event.target === rejectModal) {
        closeReject();
    }
});

q.addEventListener("input", () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadApprovals, 250);
});

statusFilter.addEventListener("change", loadApprovals);
fromDate.addEventListener("change", loadApprovals);
toDate.addEventListener("change", loadApprovals);

document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") {
        return;
    }

    if (!rejectModal.classList.contains("hidden")) {
        closeReject();
        return;
    }

    if (!detailModal.classList.contains("hidden")) {
        closeDetail();
    }
});

loadApprovals();
