(() => {
    const root = document.querySelector(".organizer-athletes");

    if (!root) {
        return;
    }

    const athletesApi = root.dataset.athletesApi || "/api/organizer/athletes";

    let athletes = [];
    let currentAthlete = null;
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
    const sOk = document.getElementById("sOk");
    const sRevoked = document.getElementById("sRevoked");

    const detailModal = document.getElementById("detailModal");
    const revokeModal = document.getElementById("revokeModal");

    const detailFields = {
        title: document.getElementById("m_name"),
        sub: document.getElementById("m_sub"),
        id: document.getElementById("m_id"),
        status: document.getElementById("m_status"),
        code: document.getElementById("m_code"),
        position: document.getElementById("m_pos"),
        username: document.getElementById("m_username"),
        email: document.getElementById("m_email"),
        phone: document.getElementById("m_phone"),
        gender: document.getElementById("m_gender"),
        dob: document.getElementById("m_dob"),
        hometown: document.getElementById("m_hometown"),
        height: document.getElementById("m_height"),
        weight: document.getElementById("m_weight"),
        address: document.getElementById("m_address"),
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
        CHO_XAC_NHAN: "Chờ xác nhận",
        DU_DIEU_KIEN: "Đủ điều kiện",
        BI_HUY_TU_CACH: "Bị hủy tư cách",
        DANG_NGHI_PHEP: "Đang nghỉ phép",
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

    const positionLabels = {
        CHU_CONG: "Chủ công",
        PHU_CONG: "Phụ công",
        CHUYEN_HAI: "Chuyền hai",
        DOI_CHUYEN: "Đối chuyền",
        LIBERO: "Libero",
        DOI_TRU: "Dự bị",
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

    function athleteId(athlete) {
        return Number(athlete.idvandongvien || athlete.id || 0);
    }

    function statusLabel(status) {
        return statusLabels[status] || status || "-";
    }

    function positionLabel(position) {
        return positionLabels[position] || position || "-";
    }

    function badgeClass(status) {
        if (["DU_DIEU_KIEN", "DA_DUYET", "HOAT_DONG"].includes(status)) {
            return "ok";
        }

        if (["CHO_XAC_NHAN", "CHUA_KICH_HOAT"].includes(status)) {
            return "wait";
        }

        if (["BI_HUY_TU_CACH", "TU_CHOI", "TAM_KHOA"].includes(status)) {
            return "lock";
        }

        return "gray";
    }

    function statusBadge(status) {
        return `<span class="badge ${badgeClass(status)}">${escapeHtml(statusLabel(status))}</span>`;
    }

    function fullName(athlete) {
        return athlete.hoten || [athlete.hodem, athlete.ten].filter(Boolean).join(" ") || athlete.username || "-";
    }

    function requestLabel(athlete) {
        if (!athlete.idyeucau) {
            return "-";
        }

        return `#${athlete.idyeucau} - ${statusLabel(athlete.yeucau_trangthai)}`;
    }

    function activeTeamNames(athlete) {
        return athlete.active_team_names || "";
    }

    function activeCoachNames(athlete) {
        return athlete.active_coach_names || "";
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

        return query === "" ? athletesApi : `${athletesApi}?${query}`;
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
        sPending.textContent = data.filter((athlete) => athlete.trangthaidaugiai === "CHO_XAC_NHAN").length;
        sOk.textContent = data.filter((athlete) => athlete.trangthaidaugiai === "DU_DIEU_KIEN").length;
        sRevoked.textContent = data.filter((athlete) => athlete.trangthaidaugiai === "BI_HUY_TU_CACH").length;
    }

    function render() {
        computeStats(athletes);

        if (athletes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="empty">Không có vận động viên phù hợp.</td></tr>';
            return;
        }

        tbody.innerHTML = athletes.map((athlete) => `
            <tr>
                <td>${athleteId(athlete)}</td>
                <td>${escapeHtml(athlete.mavandongvien || "")}</td>
                <td>
                    <div><strong>${escapeHtml(fullName(athlete))}</strong></div>
                    <span class="sub">${escapeHtml(athlete.username || "")}</span>
                </td>
                <td>${escapeHtml(activeTeamNames(athlete) || "-")}</td>
                <td>${escapeHtml(activeCoachNames(athlete) || "-")}</td>
                <td>
                    <div>${escapeHtml(athlete.email || "")}</div>
                    <span class="sub">${escapeHtml(athlete.sodienthoai || "")}</span>
                </td>
                <td>${escapeHtml(positionLabel(athlete.vitri))}</td>
                <td>${statusBadge(athlete.trangthaidaugiai)}</td>
                <td>${escapeHtml(requestLabel(athlete))}</td>
                <td><button class="btn" type="button" onclick="openAthleteDetail(${athleteId(athlete)})">Xem</button></td>
            </tr>
        `).join("");
    }

    async function loadAthletes() {
        tbody.innerHTML = '<tr><td colspan="10" class="empty">Đang tải dữ liệu...</td></tr>';
        setMessage("");

        try {
            const payload = await apiRequest(buildListUrl());
            athletes = payload.data || [];
            render();
        } catch (error) {
            athletes = [];
            render();
            setMessage(error.message);
        }
    }

    function fillDetail(athlete) {
        const canApprove = athlete.trangthaidaugiai === "CHO_XAC_NHAN";
        const canRevoke = ["CHO_XAC_NHAN", "DU_DIEU_KIEN"].includes(athlete.trangthaidaugiai);

        detailFields.title.textContent = fullName(athlete);
        detailFields.sub.textContent = `Mã VĐV: ${athlete.mavandongvien || "-"} - Trạng thái: ${statusLabel(athlete.trangthaidaugiai)}`;

        detailFields.id.value = athleteId(athlete) || "";
        detailFields.status.value = statusLabel(athlete.trangthaidaugiai);
        detailFields.code.value = athlete.mavandongvien || "";
        detailFields.position.value = positionLabel(athlete.vitri);
        detailFields.username.value = athlete.username || "";
        detailFields.email.value = athlete.email || "";
        detailFields.phone.value = athlete.sodienthoai || "";
        detailFields.gender.value = genderLabels[athlete.gioitinh] || athlete.gioitinh || "";
        detailFields.dob.value = athlete.ngaysinh || "";
        detailFields.hometown.value = athlete.quequan || "";
        detailFields.height.value = athlete.chieucao ?? "";
        detailFields.weight.value = athlete.cannang ?? "";
        detailFields.address.value = athlete.diachi || "";

        detailFields.requestId.value = athlete.idyeucau || "";
        detailFields.requestStatus.value = statusLabel(athlete.yeucau_trangthai);
        detailFields.requestContent.value = athlete.yeucau_noidung || "";

        detailFields.approve.disabled = !canApprove;
        detailFields.revoke.disabled = !canRevoke;
    }

    async function openDetail(id) {
        hideAlert(detailFields.alert);
        setMessage("");

        try {
            const payload = await apiRequest(`${athletesApi}/${id}`);
            currentAthlete = payload.data;
            fillDetail(currentAthlete);
            detailModal.classList.remove("hidden");
        } catch (error) {
            setMessage(error.message);
        }
    }

    function closeDetail() {
        detailModal.classList.add("hidden");
        currentAthlete = null;
    }

    async function approveCurrentAthlete() {
        if (!currentAthlete || currentAthlete.trangthaidaugiai !== "CHO_XAC_NHAN") {
            return;
        }

        hideAlert(detailFields.alert);
        detailFields.approve.disabled = true;

        try {
            await apiRequest(`${athletesApi}/${athleteId(currentAthlete)}/approve-qualification`, {
                method: "POST",
                body: JSON.stringify({}),
            });
            closeDetail();
            await loadAthletes();
            setMessage("Xác nhận tư cách thi đấu vận động viên thành công.", true);
        } catch (error) {
            showAlert(detailFields.alert, error.message);
        } finally {
            detailFields.approve.disabled = false;
        }
    }

    function openRevokeModal() {
        if (!currentAthlete || !["CHO_XAC_NHAN", "DU_DIEU_KIEN"].includes(currentAthlete.trangthaidaugiai)) {
            return;
        }

        hideAlert(revokeFields.alert);
        revokeFields.reason.value = "";
        revokeFields.info.textContent = `${fullName(currentAthlete)} (${currentAthlete.mavandongvien || "-"})`;
        revokeModal.classList.remove("hidden");
    }

    function closeRevokeModal() {
        revokeModal.classList.add("hidden");
    }

    async function revokeCurrentAthlete() {
        const reason = revokeFields.reason.value.trim();

        hideAlert(revokeFields.alert);

        if (!currentAthlete) {
            return;
        }

        if (reason === "") {
            showAlert(revokeFields.alert, "Vui lòng nhập lý do hủy.");
            return;
        }

        revokeFields.confirm.disabled = true;

        try {
            await apiRequest(`${athletesApi}/${athleteId(currentAthlete)}/cancel-qualification`, {
                method: "POST",
                body: JSON.stringify({ lydo: reason }),
            });
            closeRevokeModal();
            closeDetail();
            await loadAthletes();
            setMessage("Hủy tư cách thi đấu vận động viên thành công.", true);
        } catch (error) {
            showAlert(revokeFields.alert, error.message);
        } finally {
            revokeFields.confirm.disabled = false;
        }
    }

    window.openAthleteDetail = openDetail;

    q.addEventListener("input", () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadAthletes, 250);
    });
    statusFilter.addEventListener("change", loadAthletes);
    requestFilter.addEventListener("change", loadAthletes);
    fromDate.addEventListener("change", loadAthletes);
    toDate.addEventListener("change", loadAthletes);
    btnRefresh.addEventListener("click", loadAthletes);

    detailFields.close.addEventListener("click", closeDetail);
    detailFields.closeBtn.addEventListener("click", closeDetail);
    detailFields.approve.addEventListener("click", approveCurrentAthlete);
    detailFields.revoke.addEventListener("click", openRevokeModal);

    revokeFields.close.addEventListener("click", closeRevokeModal);
    revokeFields.cancel.addEventListener("click", closeRevokeModal);
    revokeFields.confirm.addEventListener("click", revokeCurrentAthlete);

    loadAthletes();
})();
