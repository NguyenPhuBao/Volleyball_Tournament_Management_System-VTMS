(function () {
    const root = document.querySelector(".referee-assignments");

    if (!root) {
        return;
    }

    const assignmentsApi = root.dataset.assignmentsApi || "/api/trongtai/assignments";
    const tournamentsApi = root.dataset.tournamentsApi || "/api/trongtai/tournaments-of-me";
    const venuesApi = root.dataset.venuesApi || "/api/trongtai/venues-of-me";
    const matchDetailApi = root.dataset.matchDetailApi || "/api/trongtai/matches";
    const supervisionUrl = root.dataset.supervisionUrl || "/trong-tai/giam-sat";

    const tbody = document.getElementById("tbody");
    const q = document.getElementById("q");
    const tournamentFilter = document.getElementById("tournamentFilter");
    const venueFilter = document.getElementById("venueFilter");
    const roleFilter = document.getElementById("roleFilter");
    const statusFilter = document.getElementById("statusFilter");
    const fromDate = document.getElementById("fromDate");
    const toDate = document.getElementById("toDate");
    const btnRefresh = document.getElementById("btnRefresh");
    const pageMessage = document.getElementById("pageMessage");

    const sTotal = document.getElementById("sTotal");
    const sUpcoming = document.getElementById("sUpcoming");
    const sNeedConfirm = document.getElementById("sNeedConfirm");

    const detailModal = document.getElementById("detailModal");
    const mClose = document.getElementById("m_close");
    const btnClose = document.getElementById("btnClose");
    const btnConfirm = document.getElementById("btnConfirm");
    const btnDecline = document.getElementById("btnDecline");
    const mAlert = document.getElementById("m_alert");
    const mSub = document.getElementById("m_sub");
    const mAssignId = document.getElementById("m_assignId");
    const mStatus = document.getElementById("m_status");
    const mTournament = document.getElementById("m_tournament");
    const mMatch = document.getElementById("m_match");
    const mStart = document.getElementById("m_start");
    const mEnd = document.getElementById("m_end");
    const mVenue = document.getElementById("m_venue");
    const mRole = document.getElementById("m_role");
    const mAssignedAt = document.getElementById("m_assignedAt");
    const mNote = document.getElementById("m_note");
    const mCancelReasonWrap = document.getElementById("m_cancelReasonWrap");
    const mCancelReason = document.getElementById("m_cancelReason");

    const matchDetailModal = document.getElementById("matchDetailModal");
    const mdClose = document.getElementById("md_close");
    const mdCloseBtn = document.getElementById("md_closeBtn");
    const mdSub = document.getElementById("md_sub");
    const mdMatchId = document.getElementById("md_matchId");
    const mdMatchStatus = document.getElementById("md_matchStatus");
    const mdTournament = document.getElementById("md_tournament");
    const mdRound = document.getElementById("md_round");
    const mdGroup = document.getElementById("md_group");
    const mdTeam1 = document.getElementById("md_team1");
    const mdTeam2 = document.getElementById("md_team2");
    const mdStart = document.getElementById("md_start");
    const mdEnd = document.getElementById("md_end");
    const mdVenue = document.getElementById("md_venue");
    const mdVenueAddr = document.getElementById("md_venueAddr");
    const mdRefs = document.getElementById("md_refs");
    const mdAlert = document.getElementById("md_alert");
    const mdResultSection = document.getElementById("md_resultSection");
    const mdResultScore = document.getElementById("md_resultScore");
    const mdResultSets = document.getElementById("md_resultSets");

    const assignmentStatusMap = {
        CHO_XAC_NHAN: ["ok", "Đã xác nhận"],
        DA_XAC_NHAN: ["ok", "Đã xác nhận"],
        TU_CHOI: ["bad", "Đã hủy xác nhận"],
        DA_HUY: ["gray", "Phân công bị hủy"],
    };

    const attendanceStatusMap = {
        THAM_GIA: ["ok", "Tham gia"],
        VANG: ["bad", "Vắng"],
        CHUA_XAC_NHAN_CO_MAT: ["wait", "Chưa xác nhận có mặt"],
    };

    const matchStatusMap = {
        CHO_DOI_DOI: "Chờ đội",
        CHO_XEP_LICH: "Chờ xếp lịch",
        DA_XEP_LICH: "Đã xếp lịch",
        CHUA_DIEN_RA: "Chưa diễn ra",
        SAP_DIEN_RA: "Sắp diễn ra",
        DANG_DIEN_RA: "Đang diễn ra",
        TAM_DUNG: "Tạm dừng",
        DA_KET_THUC: "Đã kết thúc",
        DA_HUY: "Trận bị hủy",
        TRONG_TAI_TRE_GIAM_SAT: "Trọng tài trễ giám sát",
        DA_HUY_KHONG_CO_GIAM_SAT: "Hủy do thiếu giám sát",
    };

    const tournamentStatusMap = {
        NHAP: "Nháp",
        DA_CONG_BO: "Đã công bố",
        DANG_DIEN_RA: "Đang diễn ra",
        DA_KET_THUC: "Đã kết thúc",
        DA_HUY: "Giải bị hủy",
    };

    const roleMap = {
        TRONG_TAI_CHINH: "Trọng tài chính",
        TRONG_TAI_PHU: "Trọng tài phụ",
        GIAM_SAT: "Giám sát",
    };

    let assignments = [];
    let current = null;

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

    function showPageMessage(message, isError = false) {
        pageMessage.textContent = message || "";
        pageMessage.classList.toggle("is-error", isError);
    }

    function showAlert(message) {
        mAlert.textContent = message;
        mAlert.classList.remove("hidden");
    }

    function hideAlert() {
        mAlert.textContent = "";
        mAlert.classList.add("hidden");
    }

    function showMatchAlert(message) {
        mdAlert.textContent = message;
        mdAlert.classList.remove("hidden");
    }

    function hideMatchAlert() {
        mdAlert.textContent = "";
        mdAlert.classList.add("hidden");
    }

    function formatDateTime(value) {
        if (!value) {
            return "";
        }

        return String(value).replace("T", " ").slice(0, 19);
    }

    function assignmentStatusLabel(status) {
        return assignmentStatusMap[status]?.[1] || status || "-";
    }

    function assignmentBadge(status) {
        return assignmentStatusMap[status] || ["gray", status || "-"];
    }

    function attendanceBadge(item, matchStatus = null) {
        const assignmentStatus = item.phancong_trangthai || item.trangthai;

        if (assignmentStatus && assignmentStatus !== "DA_XAC_NHAN") {
            return assignmentBadge(assignmentStatus);
        }

        const status = item.trangthai_thamgia
            || (item.xacnhanthamgia
                ? "THAM_GIA"
                : ((matchStatus || item.trandau_trangthai) === "DA_KET_THUC" ? "VANG" : "CHUA_XAC_NHAN_CO_MAT"));

        return attendanceStatusMap[status] || assignmentBadge(assignmentStatus);
    }

    function contextualBadge(item) {
        if (item.giaidau_trangthai === "DA_HUY") {
            return ["gray", tournamentStatusMap.DA_HUY];
        }

        if (["DA_HUY", "DA_HUY_KHONG_CO_GIAM_SAT"].includes(item.trandau_trangthai)) {
            return ["gray", matchStatusLabel(item.trandau_trangthai)];
        }

        if (item.trangthai_thamgia || item.trandau_trangthai === "DA_KET_THUC") {
            return attendanceBadge(item);
        }

        return assignmentBadge(item.phancong_trangthai || item.trangthai);
    }

    function isCancelableAssignmentStatus(status) {
        return status === "DA_XAC_NHAN" || status === "CHO_XAC_NHAN";
    }

    function roleLabel(role) {
        return roleMap[role] || role || "-";
    }

    function matchStatusLabel(status) {
        return matchStatusMap[status] || status || "-";
    }

    function tournamentStatusLabel(status) {
        return tournamentStatusMap[status] || status || "-";
    }

    function matchDetailUrl(matchId) {
        return `${matchDetailApi.replace(/\/+$/, "")}/${encodeURIComponent(matchId)}/detail`;
    }

    function supervisionPageUrl(matchId, assignmentId) {
        const url = new URL(supervisionUrl, window.location.origin);
        url.searchParams.set("matchId", matchId);

        if (assignmentId) {
            url.searchParams.set("assignmentId", assignmentId);
        }

        return url.toString();
    }

    function matchName(item) {
        return `${item.doi1 || "-"} vs ${item.doi2 || "-"}`;
    }

    function venueName(item) {
        const address = item.sandau_diachi ? ` - ${item.sandau_diachi}` : "";
        return `${item.tensandau || "-"}${address}`;
    }

    function updateStats(meta) {
        const stats = meta?.stats || {};
        sTotal.textContent = String(Number(stats.total ?? assignments.length));
        sUpcoming.textContent = String(Number(stats.sap_toi ?? 0));
        sNeedConfirm.textContent = String(Number(stats.DA_XAC_NHAN ?? 0) + Number(stats.CHO_XAC_NHAN ?? 0));
    }

    async function loadTournaments() {
        try {
            const payload = await requestJson(tournamentsApi);
            const data = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            tournamentFilter.innerHTML = '<option value="">Tất cả giải đấu</option>' + data.map((item) => (
                `<option value="${escapeHtml(item.idgiaidau)}">${escapeHtml(item.tengiaidau)}</option>`
            )).join("");
        } catch (error) {
            tournamentFilter.innerHTML = '<option value="">Tất cả giải đấu</option>';
        }
    }

    async function loadVenues() {
        try {
            const payload = await requestJson(venuesApi);
            const data = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            venueFilter.innerHTML = '<option value="">Tất cả sân đấu</option>' + data.map((item) => (
                `<option value="${escapeHtml(item.idsandau)}">${escapeHtml(item.tensandau)}</option>`
            )).join("");
        } catch (error) {
            venueFilter.innerHTML = '<option value="">Tất cả sân đấu</option>';
        }
    }

    async function loadAssignments() {
        showPageMessage("Đang tải dữ liệu...");

        try {
            const payload = await requestJson(apiUrl(assignmentsApi, {
                q: q.value.trim(),
                tournament_id: tournamentFilter.value,
                venue_id: venueFilter.value,
                role: roleFilter.value,
                assignment_status: statusFilter.value,
                from: fromDate.value,
                to: toDate.value,
            }));

            assignments = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            updateStats(payload.meta);
            render();
            showPageMessage("");
        } catch (error) {
            assignments = [];
            updateStats(null);
            tbody.innerHTML = '<tr><td colspan="7" class="empty">Không thể tải lịch phân công.</td></tr>';
            showPageMessage(error.message || "Không thể tải lịch phân công.", true);
        }
    }

    function render() {
        if (assignments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty">Không có phân công phù hợp.</td></tr>';
            return;
        }

        tbody.innerHTML = assignments.map((item) => {
            const status = item.phancong_trangthai || item.trangthai;
            const [className, label] = contextualBadge(item);
            const timeText = `${formatDateTime(item.thoigianbatdau)}${item.thoigianketthuc ? ` -> ${formatDateTime(item.thoigianketthuc)}` : ""}`;
            const isSupervisor = item.vaitro === "GIAM_SAT";
            const matchLocked = ["DA_HUY", "DA_HUY_KHONG_CO_GIAM_SAT", "DA_KET_THUC"].includes(item.trandau_trangthai);
            const canSupervise = isSupervisor && isCancelableAssignmentStatus(status) && !matchLocked;
            const canCancelConfirmation = isCancelableAssignmentStatus(status) && !matchLocked;

            return `
                <tr>
                    <td>${escapeHtml(item.tengiaidau)}</td>
                    <td>
                        <div style="font-weight:800">#${escapeHtml(item.idtrandau)} - ${escapeHtml(matchName(item))}</div>
                        <div class="sub">Trạng thái trận: ${escapeHtml(matchStatusLabel(item.trandau_trangthai))}</div>
                        ${item.giaidau_trangthai === "DA_HUY" ? `<div class="sub">Trạng thái giải: ${escapeHtml(tournamentStatusLabel(item.giaidau_trangthai))}</div>` : ""}
                    </td>
                    <td>${escapeHtml(timeText)}</td>
                    <td>${escapeHtml(item.tensandau || "")}</td>
                    <td>${escapeHtml(roleLabel(item.vaitro))}</td>
                    <td><span class="badge ${className}">${escapeHtml(label)}</span></td>
                    <td>
                        <div style="display:flex; gap:8px; flex-wrap:wrap">
                            <button class="btn primary" type="button" data-action="match-detail" data-match-id="${escapeHtml(item.idtrandau)}">Chi tiết trận</button>
                            ${canCancelConfirmation ? `<button class="btn danger" type="button" data-action="cancel-confirmation" data-id="${escapeHtml(item.idphancong)}">Hủy xác nhận</button>` : ""}
                            ${isSupervisor ? `<button class="btn" type="button" data-action="supervise" data-match-id="${escapeHtml(item.idtrandau)}" data-assignment-id="${escapeHtml(item.idphancong)}" ${canSupervise ? "" : "disabled"}>Giám sát</button>` : ""}
                        </div>
                    </td>
                </tr>
            `;
        }).join("");
    }

    async function openDetail(assignmentId, focusCancel = false) {
        hideAlert();

        try {
            const payload = await requestJson(`${assignmentsApi}/${assignmentId}`);
            current = responseData(payload);

            if (!current) {
                throw new Error("Không tìm thấy chi tiết phân công.");
            }
        } catch (error) {
            showPageMessage(error.message || "Không thể tải chi tiết phân công.", true);
            return;
        }

        const status = current.phancong_trangthai || current.trangthai;
        mAssignId.value = current.idphancong || "";
        mStatus.value = assignmentStatusLabel(status);
        mTournament.value = current.tengiaidau || "";
        mMatch.value = `#${current.idtrandau} - ${matchName(current)}`;
        mStart.value = formatDateTime(current.thoigianbatdau);
        mEnd.value = formatDateTime(current.thoigianketthuc);
        mVenue.value = venueName(current);
        mRole.value = roleLabel(current.vaitro);
        mAssignedAt.value = formatDateTime(current.ngayphancong);
        mNote.value = current.ghichu || "";
        mSub.textContent = `Trận #${current.idtrandau} - ${formatDateTime(current.thoigianbatdau)} - ${current.tensandau || ""}`;

        const canCancel = isCancelableAssignmentStatus(status);
        btnConfirm.hidden = true;
        btnConfirm.disabled = true;
        btnDecline.textContent = "Hủy xác nhận";
        btnDecline.disabled = !canCancel;

        if (mCancelReasonWrap) {
            mCancelReasonWrap.classList.toggle("hidden", !canCancel);
        }

        if (mCancelReason) {
            mCancelReason.value = "";
            mCancelReason.disabled = !canCancel;
        }

        detailModal.classList.remove("hidden");

        if (focusCancel && canCancel && mCancelReason) {
            setTimeout(() => mCancelReason.focus(), 0);
        }
    }

    function closeModal() {
        detailModal.classList.add("hidden");
        if (mCancelReasonWrap) {
            mCancelReasonWrap.classList.add("hidden");
        }
        if (mCancelReason) {
            mCancelReason.value = "";
        }
        current = null;
    }

    function closeMatchModal() {
        matchDetailModal.classList.add("hidden");
    }

    function refereeDisplayName(referee) {
        const name = String(referee.hoten || "").trim();
        const username = String(referee.username || "").trim();

        if (name && username) {
            return `${name} (${username})`;
        }

        return name || username || `#${referee.idtrongtai || ""}`;
    }

    function resetMatchResult() {
        mdResultSection?.classList.add("hidden");

        if (mdResultScore) {
            mdResultScore.innerHTML = "";
        }

        if (mdResultSets) {
            mdResultSets.innerHTML = "";
        }
    }

    function renderMatchResult(detail, team1, team2) {
        const result = detail.ketqua || null;

        if (!mdResultSection || !mdResultScore || !mdResultSets || detail.trangthai !== "DA_KET_THUC") {
            return;
        }

        const team1Name = team1.tendoibong || "Đội 1";
        const team2Name = team2.tendoibong || "Đội 2";

        if (!result) {
            mdResultScore.innerHTML = '<p class="match-result-empty">Trận đã kết thúc nhưng chưa có kết quả được ghi nhận.</p>';
            mdResultSection.classList.remove("hidden");
            return;
        }

        mdResultScore.innerHTML = `
            <div>
                <span>${escapeHtml(team1Name)}</span>
                <strong>${escapeHtml(result.sosetdoi1 ?? 0)}</strong>
            </div>
            <b>-</b>
            <div>
                <strong>${escapeHtml(result.sosetdoi2 ?? 0)}</strong>
                <span>${escapeHtml(team2Name)}</span>
            </div>
        `;

        const sets = Array.isArray(result.sets) ? result.sets : [];
        mdResultSets.innerHTML = sets.length === 0
            ? ""
            : sets.map((set) => `
                <span>Set ${escapeHtml(set.setthu)}: ${escapeHtml(set.diemdoi1 ?? 0)} - ${escapeHtml(set.diemdoi2 ?? 0)}</span>
            `).join("");
        mdResultSection.classList.remove("hidden");
    }

    async function openMatchDetail(matchId) {
        hideMatchAlert();
        mdMatchId.value = matchId || "";
        mdMatchStatus.value = "";
        mdTournament.value = "";
        mdRound.value = "";
        mdGroup.value = "";
        mdTeam1.value = "";
        mdTeam2.value = "";
        mdStart.value = "";
        mdEnd.value = "";
        mdVenue.value = "";
        mdVenueAddr.value = "";
        mdSub.textContent = "Đang tải thông tin trận đấu...";
        mdRefs.innerHTML = '<tr><td colspan="4" class="empty">Đang tải dữ liệu...</td></tr>';
        resetMatchResult();
        matchDetailModal.classList.remove("hidden");

        try {
            const payload = await requestJson(matchDetailUrl(matchId));
            const detail = responseData(payload);

            if (!detail) {
                throw new Error("Không tìm thấy thông tin chi tiết trận đấu.");
            }

            const tournament = detail.giaidau || {};
            const group = detail.bangdau || {};
            const venue = detail.sandau || {};
            const team1 = detail.doi1 || {};
            const team2 = detail.doi2 || {};

            mdMatchId.value = detail.idtrandau || "";
            mdMatchStatus.value = matchStatusLabel(detail.trangthai);
            mdTournament.value = tournament.tengiaidau || "";
            mdRound.value = detail.vongdau || "";
            mdGroup.value = group.tenbang || "";
            mdTeam1.value = team1.tendoibong || "";
            mdTeam2.value = team2.tendoibong || "";
            mdStart.value = formatDateTime(detail.thoigianbatdau);
            mdEnd.value = formatDateTime(detail.thoigianketthuc);
            mdVenue.value = venue.tensandau || "";
            mdVenueAddr.value = venue.diachi || "";
            mdSub.textContent = `Trận #${detail.idtrandau} - ${formatDateTime(detail.thoigianbatdau)} - ${venue.tensandau || ""}`;

            const referees = Array.isArray(detail.trongtai_cung_tran) ? detail.trongtai_cung_tran : [];

            mdRefs.innerHTML = referees.length === 0
                ? '<tr><td colspan="4" class="empty">Chưa có trọng tài được phân công.</td></tr>'
                : referees.map((referee) => {
                    const [className, label] = attendanceBadge(referee, detail.trangthai);

                    return `
                        <tr>
                            <td>${escapeHtml(refereeDisplayName(referee))}</td>
                            <td>${escapeHtml(roleLabel(referee.vaitro))}</td>
                            <td><span class="badge ${className}">${escapeHtml(label)}</span></td>
                            <td>${escapeHtml(formatDateTime(referee.ngayphancong))}</td>
                        </tr>
                    `;
                }).join("");
            renderMatchResult(detail, team1, team2);
        } catch (error) {
            mdRefs.innerHTML = '<tr><td colspan="4" class="empty">Không thể tải tổ trọng tài.</td></tr>';
            showMatchAlert(error.message || "Không thể tải thông tin chi tiết trận đấu.");
        }
    }

    async function decideCurrent(action) {
        if (!current || action !== "decline") {
            return;
        }

        if (!isCancelableAssignmentStatus(current.phancong_trangthai || current.trangthai)) {
            return;
        }

        const reason = String(mCancelReason?.value || "").trim();
        if (!reason) {
            showAlert("Vui lòng nhập lý do hủy xác nhận.");
            mCancelReason?.focus();
            return;
        }

        if (!window.confirm("Bạn chắc chắn muốn hủy xác nhận phân công này?")) {
            return;
        }

        btnConfirm.disabled = true;
        btnDecline.disabled = true;
        hideAlert();

        try {
            await requestJson(`${assignmentsApi}/${current.idphancong}/decline`, {
                method: "POST",
                body: JSON.stringify({ reason }),
            });
            closeModal();
            await loadAssignments();
            showPageMessage("Đã hủy xác nhận phân công.");
        } catch (error) {
            showAlert(error.message || "Không thể hủy xác nhận phân công.");
            const canCancel = isCancelableAssignmentStatus(current.phancong_trangthai || current.trangthai);
            btnDecline.disabled = !canCancel;
        }
    }

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-action]");

        if (!button) {
            return;
        }

        if (button.dataset.action === "cancel-confirmation") {
            openDetail(button.dataset.id, true);
        }

        if (button.dataset.action === "match-detail") {
            openMatchDetail(button.dataset.matchId);
        }

        if (button.dataset.action === "supervise") {
            window.location.href = supervisionPageUrl(button.dataset.matchId, button.dataset.assignmentId);
        }
    });

    mClose.addEventListener("click", closeModal);
    btnClose.addEventListener("click", closeModal);
    mdClose.addEventListener("click", closeMatchModal);
    mdCloseBtn.addEventListener("click", closeMatchModal);
    btnConfirm.addEventListener("click", () => decideCurrent("confirm"));
    btnDecline.addEventListener("click", () => decideCurrent("decline"));
    btnRefresh.addEventListener("click", loadAssignments);
    q.addEventListener("input", loadAssignments);
    tournamentFilter.addEventListener("change", loadAssignments);
    venueFilter.addEventListener("change", loadAssignments);
    roleFilter.addEventListener("change", loadAssignments);
    statusFilter.addEventListener("change", loadAssignments);
    fromDate.addEventListener("change", loadAssignments);
    toDate.addEventListener("change", loadAssignments);

    Promise.all([loadTournaments(), loadVenues()]).then(loadAssignments);
})();
