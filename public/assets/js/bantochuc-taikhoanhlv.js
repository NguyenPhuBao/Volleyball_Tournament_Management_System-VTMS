(() => {
    const root = document.querySelector(".organizer-coach-accounts");

    if (!root) {
        return;
    }

    const accountsApi = root.dataset.accountsApi || "/api/organizer/coach-accounts";

    const tbody = document.getElementById("tbody");
    const q = document.getElementById("q");
    const statusFilter = document.getElementById("statusFilter");
    const btnRefresh = document.getElementById("btnRefresh");
    const pageMessage = document.getElementById("pageMessage");

    const sPending = document.getElementById("sPending");
    const sActive = document.getElementById("sActive");
    const sCanceled = document.getElementById("sCanceled");

    const modal = document.getElementById("detailModal");
    const fields = {
        title: document.getElementById("m_title"),
        sub: document.getElementById("m_sub"),
        id: document.getElementById("m_id"),
        status: document.getElementById("m_status"),
        username: document.getElementById("m_username"),
        email: document.getElementById("m_email"),
        phone: document.getElementById("m_phone"),
        name: document.getElementById("m_name"),
        created: document.getElementById("m_created"),
        updated: document.getElementById("m_updated"),
        alert: document.getElementById("m_alert"),
        approve: document.getElementById("m_approve"),
        reject: document.getElementById("m_reject"),
        close: document.getElementById("m_close"),
        closeBtn: document.getElementById("m_closeBtn"),
    };

    const statusLabels = {
        CHO_DUYET: "Chờ duyệt",
        HOAT_DONG: "Hoạt động",
        CHUA_KICH_HOAT: "Chưa kích hoạt",
        TAM_KHOA: "Tạm khóa",
        DA_HUY: "Đã hủy",
    };

    let accounts = [];
    let current = null;
    let searchTimer = null;

    function escapeHtml(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function responseData(payload) {
        return payload && Object.prototype.hasOwnProperty.call(payload, "data") ? payload.data : null;
    }

    function apiUrl(base, params = {}) {
        const url = new URL(base, window.location.origin);

        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && String(value).trim() !== "") {
                url.searchParams.set(key, value);
            }
        });

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
            const details = payload.errors ? Object.values(payload.errors).flat().join(" ") : "";
            throw new Error([payload.message, details].filter(Boolean).join(" ") || "Yêu cầu không thành công.");
        }

        return payload;
    }

    function showMessage(message, success = false) {
        pageMessage.textContent = message || "";
        pageMessage.classList.toggle("success", success);
    }

    function showModalAlert(message) {
        fields.alert.textContent = message || "";
        fields.alert.classList.toggle("hidden", !message);
    }

    function fullName(account) {
        return account.hoten || [account.hodem, account.ten].filter(Boolean).join(" ").trim() || "-";
    }

    function statusLabel(status) {
        return statusLabels[status] || status || "-";
    }

    function badgeClass(status) {
        if (status === "HOAT_DONG") {
            return "ok";
        }

        if (status === "CHO_DUYET" || status === "CHUA_KICH_HOAT") {
            return "wait";
        }

        if (status === "DA_HUY" || status === "TAM_KHOA") {
            return "bad";
        }

        return "gray";
    }

    function badge(status) {
        return `<span class="badge ${badgeClass(status)}">${escapeHtml(statusLabel(status))}</span>`;
    }

    function formatDateTime(value) {
        if (!value) {
            return "";
        }

        return String(value).replace("T", " ").slice(0, 19);
    }

    function updateStats() {
        sPending.textContent = String(accounts.filter((item) => item.trangthai === "CHO_DUYET").length);
        sActive.textContent = String(accounts.filter((item) => item.trangthai === "HOAT_DONG").length);
        sCanceled.textContent = String(accounts.filter((item) => item.trangthai === "DA_HUY").length);
    }

    function renderRows() {
        updateStats();

        if (accounts.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Không có tài khoản HLV phù hợp.</td></tr>';
            return;
        }

        tbody.innerHTML = accounts.map((account) => `
            <tr>
                <td>${escapeHtml(account.idtaikhoan)}</td>
                <td>${escapeHtml(account.username)}</td>
                <td>
                    <strong>${escapeHtml(fullName(account))}</strong>
                    <div class="sub">${escapeHtml(account.role_mota || account.role || "")}</div>
                </td>
                <td>${escapeHtml(account.email || "")}</td>
                <td>${escapeHtml(account.sodienthoai || "")}</td>
                <td>${badge(account.trangthai)}</td>
                <td>${escapeHtml(formatDateTime(account.ngaytao))}</td>
                <td><button class="btn" type="button" data-action="view" data-id="${escapeHtml(account.idtaikhoan)}">Xem</button></td>
            </tr>
        `).join("");
    }

    async function loadAccounts(showLoading = true) {
        if (showLoading) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Đang tải dữ liệu...</td></tr>';
        }

        showMessage("");

        try {
            const payload = await requestJson(apiUrl(accountsApi, {
                q: q.value.trim(),
                status: statusFilter.value,
            }));
            accounts = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            renderRows();
        } catch (error) {
            accounts = [];
            renderRows();
            showMessage(error.message || "Không thể tải danh sách tài khoản HLV.");
        }
    }

    function setBusy(isBusy) {
        const actionable = current?.trangthai === "CHO_DUYET";
        fields.approve.disabled = isBusy || !actionable;
        fields.reject.disabled = isBusy || !actionable;
    }

    function fillDetail(account) {
        current = account;
        fields.title.textContent = fullName(account);
        fields.sub.textContent = `${account.username || "-"} · ${statusLabel(account.trangthai)}`;
        fields.id.value = account.idtaikhoan || "";
        fields.status.value = statusLabel(account.trangthai);
        fields.username.value = account.username || "";
        fields.email.value = account.email || "";
        fields.phone.value = account.sodienthoai || "";
        fields.name.value = fullName(account);
        fields.created.value = formatDateTime(account.ngaytao);
        fields.updated.value = formatDateTime(account.ngaycapnhat);
        showModalAlert("");
        setBusy(false);
    }

    async function openDetail(accountId) {
        showMessage("");

        try {
            const payload = await requestJson(`${accountsApi}/${encodeURIComponent(accountId)}`);
            fillDetail(responseData(payload));
            modal.classList.remove("hidden");
            modal.setAttribute("aria-hidden", "false");
        } catch (error) {
            showMessage(error.message || "Không thể tải chi tiết tài khoản HLV.");
        }
    }

    function closeModal() {
        modal.classList.add("hidden");
        modal.setAttribute("aria-hidden", "true");
        current = null;
    }

    async function processCurrent(action) {
        if (!current || current.trangthai !== "CHO_DUYET") {
            return;
        }

        const verb = action === "approve" ? "duyệt" : "từ chối";

        if (!window.confirm(`Xác nhận ${verb} tài khoản HLV này?`)) {
            return;
        }

        setBusy(true);
        showModalAlert("");

        try {
            const payload = await requestJson(`${accountsApi}/${current.idtaikhoan}/${action}`, {
                method: "POST",
                body: JSON.stringify({}),
            });
            closeModal();
            showMessage(payload.message || "Cập nhật tài khoản HLV thành công.", true);
            await loadAccounts(false);
        } catch (error) {
            showModalAlert(error.message || "Không thể cập nhật tài khoản HLV.");
            setBusy(false);
        }
    }

    function scheduleLoad() {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => loadAccounts(false), 250);
    }

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-action='view']");

        if (button) {
            openDetail(button.dataset.id);
        }
    });

    fields.close.addEventListener("click", closeModal);
    fields.closeBtn.addEventListener("click", closeModal);
    fields.approve.addEventListener("click", () => processCurrent("approve"));
    fields.reject.addEventListener("click", () => processCurrent("reject"));
    btnRefresh.addEventListener("click", () => loadAccounts(true));
    q.addEventListener("input", scheduleLoad);
    statusFilter.addEventListener("change", () => loadAccounts(true));

    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    loadAccounts(true);
})();
