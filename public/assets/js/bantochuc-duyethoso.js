(function () {
    const root = document.querySelector(".organizer-profile-approvals");

    if (!root) {
        return;
    }

    const approvalsApi = root.dataset.approvalsApi || "/api/organizer/profile-change-requests";

    const tbody = document.getElementById("tbody");
    const q = document.getElementById("q");
    const roleFilter = document.getElementById("roleFilter");
    const statusFilter = document.getElementById("statusFilter");
    const fromDate = document.getElementById("fromDate");
    const toDate = document.getElementById("toDate");
    const btnRefresh = document.getElementById("btnRefresh");
    const pageAlert = document.getElementById("pageAlert");

    const sPending = document.getElementById("sPending");
    const sApproved = document.getElementById("sApproved");
    const sRejected = document.getElementById("sRejected");

    const detailModal = document.getElementById("detailModal");
    const mClose = document.getElementById("m_close");
    const btnClose = document.getElementById("btnClose");
    const btnApprove = document.getElementById("btnApprove");
    const btnReject = document.getElementById("btnReject");
    const mAlert = document.getElementById("m_alert");

    const fields = {
        sub: document.getElementById("m_sub"),
        id: document.getElementById("m_id"),
        status: document.getElementById("m_status"),
        sender: document.getElementById("m_sender"),
        role: document.getElementById("m_role"),
        table: document.getElementById("m_table"),
        field: document.getElementById("m_field"),
        oldValue: document.getElementById("m_old"),
        newValue: document.getElementById("m_new"),
        reason: document.getElementById("m_reason"),
        sentAt: document.getElementById("m_sentAt"),
        doneAt: document.getElementById("m_doneAt"),
        note: document.getElementById("m_note"),
    };

    const statusMap = {
        CHO_DUYET: ["wait", "Chờ duyệt"],
        DA_DUYET: ["ok", "Đã duyệt"],
        TU_CHOI: ["bad", "Từ chối"],
    };

    const roleMap = {
        ADMIN: "ADMIN",
        TRONG_TAI: "Trọng tài",
        HUAN_LUYEN_VIEN: "Huấn luyện viên",
    };

    let requests = [];
    let current = null;
    let loadTimer = null;

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
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                ...(options.headers || {}),
            },
            credentials: "same-origin",
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

    function showPageAlert(message, isError = false) {
        pageAlert.textContent = message || "";
        pageAlert.classList.toggle("is-error", isError);
        pageAlert.classList.toggle("hidden", !message);
    }

    function showModalAlert(message) {
        mAlert.textContent = message || "";
        mAlert.classList.toggle("hidden", !message);
    }

    function statusInfo(status) {
        return statusMap[status] || ["wait", status || "-"];
    }

    function roleLabel(role) {
        return roleMap[role] || role || "-";
    }

    function formatDateTime(value) {
        if (!value) {
            return "";
        }

        return String(value).replace("T", " ").slice(0, 19);
    }

    function senderLabel(item) {
        const name = item.hoten || [item.hodem, item.ten].filter(Boolean).join(" ").trim();
        const username = item.username || "";

        if (name && username) {
            return `${name} (${username})`;
        }

        return name || username || `Người dùng #${item.idnguoidung || "-"}`;
    }

    function updateStats(counts = null) {
        const source = counts || requests.reduce((acc, item) => {
            acc[item.trangthai] = (acc[item.trangthai] || 0) + 1;
            return acc;
        }, {});

        sPending.textContent = source.CHO_DUYET || 0;
        sApproved.textContent = source.DA_DUYET || 0;
        sRejected.textContent = source.TU_CHOI || 0;
    }

    function renderRows() {
        if (!requests.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="empty">Không có yêu cầu xác nhận phù hợp.</td></tr>';
            return;
        }

        tbody.innerHTML = requests.map((item) => {
            const [badgeClass, badgeText] = statusInfo(item.trangthai);

            return `
                <tr>
                    <td>${escapeHtml(item.idyeucaucapnhat)}</td>
                    <td>
                        <div style="font-weight:800">${escapeHtml(senderLabel(item))}</div>
                        <div class="sub">Người dùng #${escapeHtml(item.idnguoidung)}</div>
                    </td>
                    <td>${escapeHtml(roleLabel(item.role))}</td>
                    <td>${escapeHtml(item.banglienquan)}</td>
                    <td>${escapeHtml(item.truongcapnhat)}</td>
                    <td class="value-cell" title="${escapeHtml(item.giatricu)}">${escapeHtml(item.giatricu)}</td>
                    <td class="value-cell" title="${escapeHtml(item.giatrimoi)}">${escapeHtml(item.giatrimoi)}</td>
                    <td><span class="badge ${badgeClass}">${escapeHtml(badgeText)}</span></td>
                    <td>${escapeHtml(formatDateTime(item.ngaygui))}</td>
                    <td><button class="btn" type="button" data-action="view" data-id="${escapeHtml(item.idyeucaucapnhat)}">Xem</button></td>
                </tr>
            `;
        }).join("");
    }

    function filters() {
        return {
            q: q.value.trim(),
            role: roleFilter.value,
            trangthai: statusFilter.value,
            from: fromDate.value,
            to: toDate.value,
            per_page: 100,
        };
    }

    async function loadRequests(showLoading = true) {
        if (showLoading) {
            tbody.innerHTML = '<tr><td colspan="10" class="empty">Đang tải danh sách yêu cầu...</td></tr>';
            showPageAlert("Đang tải danh sách yêu cầu...");
        }

        try {
            const payload = await requestJson(apiUrl(approvalsApi, filters()));
            requests = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            updateStats(payload.meta?.status_counts || null);
            renderRows();
            showPageAlert("");
        } catch (error) {
            requests = [];
            updateStats();
            renderRows();
            showPageAlert(error.message || "Không thể tải danh sách yêu cầu.", true);
        }
    }

    function scheduleLoad() {
        window.clearTimeout(loadTimer);
        loadTimer = window.setTimeout(() => loadRequests(false), 250);
    }

    function fillDetail(item) {
        current = item;
        const [, statusText] = statusInfo(item.trangthai);
        const sender = senderLabel(item);

        fields.sub.textContent = `${sender} - ${roleLabel(item.role)}`;
        fields.id.value = item.idyeucaucapnhat || "";
        fields.status.value = statusText;
        fields.sender.value = sender;
        fields.role.value = roleLabel(item.role);
        fields.table.value = item.banglienquan || "";
        fields.field.value = item.truongcapnhat || "";
        fields.oldValue.value = item.giatricu ?? "";
        fields.newValue.value = item.giatrimoi ?? "";
        fields.reason.value = item.lydo ?? "";
        fields.sentAt.value = formatDateTime(item.ngaygui);
        fields.doneAt.value = formatDateTime(item.ngayxuly);
        fields.note.value = "";

        const actionable = item.trangthai === "CHO_DUYET";
        btnApprove.disabled = !actionable;
        btnReject.disabled = !actionable;
        showModalAlert("");
    }

    async function openDetail(requestId) {
        showPageAlert("");

        try {
            const payload = await requestJson(`${approvalsApi}/${requestId}`);
            fillDetail(responseData(payload));
            detailModal.classList.remove("hidden");
            detailModal.setAttribute("aria-hidden", "false");
        } catch (error) {
            showPageAlert(error.message || "Không thể tải chi tiết yêu cầu.", true);
        }
    }

    function closeModal() {
        detailModal.classList.add("hidden");
        detailModal.setAttribute("aria-hidden", "true");
        current = null;
    }

    function setActionBusy(isBusy) {
        btnApprove.disabled = isBusy || !current || current.trangthai !== "CHO_DUYET";
        btnReject.disabled = isBusy || !current || current.trangthai !== "CHO_DUYET";
    }

    async function approveCurrent() {
        if (!current || current.trangthai !== "CHO_DUYET") {
            return;
        }

        showModalAlert("");
        setActionBusy(true);

        try {
            await requestJson(`${approvalsApi}/${current.idyeucaucapnhat}/approve`, {
                method: "POST",
                body: JSON.stringify({
                    ghichu: fields.note.value.trim() || null,
                }),
            });
            closeModal();
            showPageAlert("Xác nhận thành công.");
            await loadRequests(false);
        } catch (error) {
            showModalAlert(error.message || "Không thể xác nhận yêu cầu.");
            setActionBusy(false);
        }
    }

    async function rejectCurrent() {
        if (!current || current.trangthai !== "CHO_DUYET") {
            return;
        }

        const note = fields.note.value.trim();

        if (!note) {
            showModalAlert("Vui lòng nhập ghi chú (bắt buộc) khi hủy/từ chối.");
            return;
        }

        showModalAlert("");
        setActionBusy(true);

        try {
            await requestJson(`${approvalsApi}/${current.idyeucaucapnhat}/reject`, {
                method: "POST",
                body: JSON.stringify({
                    ghichu: note,
                }),
            });
            closeModal();
            showPageAlert("Hủy thành công.");
            await loadRequests(false);
        } catch (error) {
            showModalAlert(error.message || "Không thể hủy yêu cầu.");
            setActionBusy(false);
        }
    }

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-action='view']");

        if (button) {
            openDetail(button.dataset.id);
        }
    });

    mClose.addEventListener("click", closeModal);
    btnClose.addEventListener("click", closeModal);
    btnApprove.addEventListener("click", approveCurrent);
    btnReject.addEventListener("click", rejectCurrent);
    btnRefresh.addEventListener("click", () => loadRequests(true));
    q.addEventListener("input", scheduleLoad);
    roleFilter.addEventListener("change", () => loadRequests(true));
    statusFilter.addEventListener("change", () => loadRequests(true));
    fromDate.addEventListener("change", () => loadRequests(true));
    toDate.addEventListener("change", () => loadRequests(true));

    detailModal.addEventListener("click", (event) => {
        if (event.target === detailModal) {
            closeModal();
        }
    });

    loadRequests(true);
})();
