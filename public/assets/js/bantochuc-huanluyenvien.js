(() => {
    const root = document.querySelector(".organizer-coaches");

    if (!root) {
        return;
    }

    const coachesApi = root.dataset.coachesApi || "/api/organizer/coaches";

    let coaches = [];
    let currentCoach = null;
    let searchTimer = null;

    const tbody = document.getElementById("tbody");
    const q = document.getElementById("q");
    const statusFilter = document.getElementById("statusFilter");
    const requestFilter = document.getElementById("requestFilter");
    const fromDate = document.getElementById("fromDate");
    const toDate = document.getElementById("toDate");
    const btnRefresh = document.getElementById("btnRefresh");
    const pageMessage = document.getElementById("pageMessage");

    const sPending = document.getElementById("sPending");
    const sApproved = document.getElementById("sApproved");
    const sRevoked = document.getElementById("sRevoked");

    const detailModal = document.getElementById("detailModal");
    const revokeModal = document.getElementById("revokeModal");

    const detailFields = {
        title: document.getElementById("m_name"),
        sub: document.getElementById("m_sub"),
        id: document.getElementById("m_id"),
        status: document.getElementById("m_status"),
        username: document.getElementById("m_username"),
        email: document.getElementById("m_email"),
        phone: document.getElementById("m_phone"),
        gender: document.getElementById("m_gender"),
        dob: document.getElementById("m_dob"),
        hometown: document.getElementById("m_hometown"),
        address: document.getElementById("m_address"),
        workUnit: document.getElementById("m_workUnit"),
        workRegion: document.getElementById("m_workRegion"),
        degree: document.getElementById("m_degree"),
        exp: document.getElementById("m_exp"),
        requestId: document.getElementById("m_reqId"),
        requestStatus: document.getElementById("m_reqStatus"),
        requestContent: document.getElementById("m_reqContent"),
        alert: document.getElementById("m_alert"),
        approve: document.getElementById("m_approve"),
        revoke: document.getElementById("m_revoke"),
        close: document.getElementById("m_close"),
        closeBtn: document.getElementById("m_closeBtn"),
    };

    const revokeFields = {
        close: document.getElementById("rv_close"),
        cancel: document.getElementById("rv_cancel"),
        confirm: document.getElementById("rv_confirm"),
        info: document.getElementById("rv_info"),
        reason: document.getElementById("rv_reason"),
        alert: document.getElementById("rv_alert"),
    };

    const statusLabels = {
        CHO_DUYET: "Chờ duyệt",
        DA_XAC_NHAN: "Đã xác nhận",
        BI_HUY_TU_CACH: "Bị hủy tư cách",
        NGUNG_HOAT_DONG: "Ngưng hoạt động",
        DA_DUYET: "Đã duyệt",
        TU_CHOI: "Từ chối",
        DA_HUY: "Đã hủy",
        HOAT_DONG: "Hoạt động",
        CHUA_KICH_HOAT: "Chưa kích hoạt",
        TAM_KHOA: "Tạm khóa",
    };

    const genderLabels = {
        NAM: "Nam",
        NU: "Nữ",
        KHAC: "Khác",
    };

    function escapeHtml(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function setMessage(message, success = false) {
        pageMessage.textContent = message || "";
        pageMessage.classList.toggle("success", success);
    }

    function showAlert(element, message) {
        element.textContent = message;
        element.classList.remove("hidden");
    }

    function hideAlert(element) {
        element.textContent = "";
        element.classList.add("hidden");
    }

    function coachId(coach) {
        return Number(coach.idhuanluyenvien || coach.id || 0);
    }

    function statusLabel(status) {
        return statusLabels[status] || status || "-";
    }

    function badgeClass(status) {
        if (["DA_XAC_NHAN", "DA_DUYET", "HOAT_DONG"].includes(status)) {
            return "ok";
        }

        if (["CHO_DUYET", "CHUA_KICH_HOAT"].includes(status)) {
            return "wait";
        }

        if (["BI_HUY_TU_CACH", "TU_CHOI", "NGUNG_HOAT_DONG", "TAM_KHOA"].includes(status)) {
            return "lock";
        }

        return "gray";
    }

    function statusBadge(status) {
        return `<span class="badge ${badgeClass(status)}">${escapeHtml(statusLabel(status))}</span>`;
    }

    function fullName(coach) {
        return coach.hoten || [coach.hodem, coach.ten].filter(Boolean).join(" ") || coach.username || "-";
    }

    function requestLabel(coach) {
        if (!coach.idyeucau) {
            return "-";
        }

        return `#${coach.idyeucau} - ${statusLabel(coach.yeucau_trangthai)}`;
    }

    function buildListUrl() {
        const params = new URLSearchParams();

        if (q.value.trim() !== "") {
            params.set("q", q.value.trim());
        }

        if (statusFilter.value !== "") {
            params.set("status", statusFilter.value);
        }

        if (requestFilter.value !== "") {
            params.set("request_presence", requestFilter.value);
        }

        if (fromDate.value !== "") {
            params.set("from", fromDate.value);
        }

        if (toDate.value !== "") {
            params.set("to", toDate.value);
        }

        const query = params.toString();

        return query === "" ? coachesApi : `${coachesApi}?${query}`;
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
            const details = payload.errors ? Object.values(payload.errors).map((value) => {
                if (typeof value === "object" && value !== null) {
                    return Object.values(value).join(" ");
                }

                return value;
            }).join(" ") : "";

            throw new Error([payload.message, details].filter(Boolean).join(" ") || "Yêu cầu không thành công.");
        }

        return payload;
    }

    function computeStats(data) {
        sPending.textContent = data.filter((coach) => coach.trangthai === "CHO_DUYET").length;
        sApproved.textContent = data.filter((coach) => coach.trangthai === "DA_XAC_NHAN").length;
        sRevoked.textContent = data.filter((coach) => coach.trangthai === "BI_HUY_TU_CACH").length;
    }

    function render() {
        computeStats(coaches);

        if (coaches.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="empty">Không có huấn luyện viên phù hợp.</td></tr>';
            return;
        }

        tbody.innerHTML = coaches.map((coach) => `
            <tr>
                <td>${coachId(coach)}</td>
                <td>
                    <div><strong>${escapeHtml(fullName(coach))}</strong></div>
                    <span class="sub">${escapeHtml(coach.username || "")}</span>
                </td>
                <td>
                    <div>${escapeHtml(coach.email || "")}</div>
                    <span class="sub">${escapeHtml(coach.sodienthoai || "")}</span>
                </td>
                <td>
                    <div>${escapeHtml(coach.donvicongtac || "")}</div>
                    <span class="sub">${escapeHtml(coach.tenkhuvuccongtac || "")}</span>
                </td>
                <td>${escapeHtml(coach.bangcap || "")}</td>
                <td>${Number(coach.kinhnghiem || 0)}</td>
                <td>${statusBadge(coach.trangthai)}</td>
                <td>${escapeHtml(requestLabel(coach))}</td>
                <td><button class="btn" type="button" onclick="openCoachDetail(${coachId(coach)})">Xem</button></td>
            </tr>
        `).join("");
    }

    async function loadCoaches() {
        tbody.innerHTML = '<tr><td colspan="9" class="empty">Đang tải dữ liệu...</td></tr>';
        setMessage("");

        try {
            const payload = await apiRequest(buildListUrl());
            coaches = payload.data || [];
            render();
        } catch (error) {
            coaches = [];
            render();
            setMessage(error.message);
        }
    }

    function fillDetail(coach) {
        const canApprove = coach.trangthai === "CHO_DUYET";
        const canRevoke = ["CHO_DUYET", "DA_XAC_NHAN"].includes(coach.trangthai);

        detailFields.title.textContent = fullName(coach);
        detailFields.sub.textContent = `Username: ${coach.username || "-"} - Trạng thái: ${statusLabel(coach.trangthai)}`;

        detailFields.id.value = coachId(coach) || "";
        detailFields.status.value = statusLabel(coach.trangthai);
        detailFields.username.value = coach.username || "";
        detailFields.email.value = coach.email || "";
        detailFields.phone.value = coach.sodienthoai || "";
        detailFields.gender.value = genderLabels[coach.gioitinh] || coach.gioitinh || "";
        detailFields.dob.value = coach.ngaysinh || "";
        detailFields.hometown.value = coach.quequan || "";
        detailFields.address.value = coach.diachi || "";
        detailFields.workUnit.value = coach.donvicongtac || "";
        detailFields.workRegion.value = [coach.tenkhuvuccongtac, coach.capkhuvuccongtac].filter(Boolean).join(" - ");
        detailFields.degree.value = coach.bangcap || "";
        detailFields.exp.value = Number(coach.kinhnghiem || 0);

        detailFields.requestId.value = coach.idyeucau || "";
        detailFields.requestStatus.value = statusLabel(coach.yeucau_trangthai);
        detailFields.requestContent.value = coach.yeucau_noidung || "";

        detailFields.approve.disabled = !canApprove;
        detailFields.revoke.disabled = !canRevoke;
    }

    async function openDetail(id) {
        hideAlert(detailFields.alert);
        setMessage("");

        try {
            const payload = await apiRequest(`${coachesApi}/${id}`);
            currentCoach = payload.data;
            fillDetail(currentCoach);
            detailModal.classList.remove("hidden");
        } catch (error) {
            setMessage(error.message);
        }
    }

    function closeDetail() {
        detailModal.classList.add("hidden");
        currentCoach = null;
    }

    async function approveCurrentCoach() {
        if (!currentCoach || currentCoach.trangthai !== "CHO_DUYET") {
            return;
        }

        hideAlert(detailFields.alert);
        detailFields.approve.disabled = true;

        try {
            await apiRequest(`${coachesApi}/${coachId(currentCoach)}/approve`, {
                method: "POST",
                body: JSON.stringify({}),
            });
            closeDetail();
            await loadCoaches();
            setMessage("Xác nhận tư cách huấn luyện viên thành công.", true);
        } catch (error) {
            showAlert(detailFields.alert, error.message);
        } finally {
            detailFields.approve.disabled = false;
        }
    }

    function openRevokeModal() {
        if (!currentCoach || !["CHO_DUYET", "DA_XAC_NHAN"].includes(currentCoach.trangthai)) {
            return;
        }

        hideAlert(revokeFields.alert);
        revokeFields.reason.value = "";
        revokeFields.info.textContent = `${fullName(currentCoach)} (${currentCoach.username || "-"})`;
        revokeModal.classList.remove("hidden");
    }

    function closeRevokeModal() {
        revokeModal.classList.add("hidden");
    }

    async function revokeCurrentCoach() {
        const reason = revokeFields.reason.value.trim();

        hideAlert(revokeFields.alert);

        if (!currentCoach) {
            return;
        }

        if (reason === "") {
            showAlert(revokeFields.alert, "Vui lòng nhập lý do hủy.");
            return;
        }

        revokeFields.confirm.disabled = true;

        try {
            await apiRequest(`${coachesApi}/${coachId(currentCoach)}/cancel-qualification`, {
                method: "POST",
                body: JSON.stringify({ lydo: reason }),
            });
            closeRevokeModal();
            closeDetail();
            await loadCoaches();
            setMessage("Hủy tư cách huấn luyện viên thành công.", true);
        } catch (error) {
            showAlert(revokeFields.alert, error.message);
        } finally {
            revokeFields.confirm.disabled = false;
        }
    }

    window.openCoachDetail = openDetail;

    q.addEventListener("input", () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadCoaches, 250);
    });
    statusFilter.addEventListener("change", loadCoaches);
    requestFilter.addEventListener("change", loadCoaches);
    fromDate.addEventListener("change", loadCoaches);
    toDate.addEventListener("change", loadCoaches);
    btnRefresh.addEventListener("click", loadCoaches);

    detailFields.close.addEventListener("click", closeDetail);
    detailFields.closeBtn.addEventListener("click", closeDetail);
    detailFields.approve.addEventListener("click", approveCurrentCoach);
    detailFields.revoke.addEventListener("click", openRevokeModal);

    revokeFields.close.addEventListener("click", closeRevokeModal);
    revokeFields.cancel.addEventListener("click", closeRevokeModal);
    revokeFields.confirm.addEventListener("click", revokeCurrentCoach);

    loadCoaches();
})();
