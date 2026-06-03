(function () {
    const root = document.querySelector(".referee-supervise");

    if (!root) {
        return;
    }

    const supervisionApi = (root.dataset.supervisionApi || "/api/trongtai/matches").replace(/\/+$/, "");
    const assignmentsApi = (root.dataset.assignmentsApi || "/api/trongtai/assignments").replace(/\/+$/, "");

    const matchTitle = document.getElementById("matchTitle");
    const matchSub = document.getElementById("matchSub");
    const matchState = document.getElementById("matchState");
    const mMatchId = document.getElementById("m_matchId");
    const mTeamOne = document.getElementById("m_teamOne");
    const mTeamTwo = document.getElementById("m_teamTwo");
    const mTournament = document.getElementById("m_tournament");
    const mVenue = document.getElementById("m_venue");
    const mRound = document.getElementById("m_round");
    const mStart = document.getElementById("m_start");
    const mEnd = document.getElementById("m_end");
    const pageAlert = document.getElementById("pageAlert");

    const btnJoin = document.getElementById("btnJoin");
    const btnPickRefs = document.getElementById("btnPickRefs");
    const btnStart = document.getElementById("btnStart");
    const btnPause = document.getElementById("btnPause");
    const btnResume = document.getElementById("btnResume");
    const btnEnd = document.getElementById("btnEnd");

    const resultCard = document.getElementById("resultCard");
    const setsEl = document.getElementById("sets");
    const btnAddSet = document.getElementById("btnAddSet");
    const btnRemoveSet = document.getElementById("btnRemoveSet");
    const setScore = document.getElementById("setScore");
    const winner = document.getElementById("winner");
    const resultNote = document.getElementById("resultNote");
    const resultState = document.getElementById("resultState");

    const refsModal = document.getElementById("refsModal");
    const rClose = document.getElementById("r_close");
    const rCancel = document.getElementById("r_cancel");
    const rConfirm = document.getElementById("r_confirm");
    const rTbody = document.getElementById("r_tbody");
    const rAlert = document.getElementById("r_alert");

    const assignmentStatusMap = {
        CHO_XAC_NHAN: ["wait", "Chờ xác nhận"],
        DA_XAC_NHAN: ["ok", "Đã xác nhận"],
        TU_CHOI: ["bad", "Từ chối"],
        DA_HUY: ["gray", "Đã hủy"],
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
        DA_HUY: "Đã hủy",
        TRONG_TAI_TRE_GIAM_SAT: "Trọng tài trễ giám sát",
        DA_HUY_KHONG_CO_GIAM_SAT: "Hủy do thiếu giám sát",
    };

    const roleMap = {
        TRONG_TAI_CHINH: "Trọng tài chính",
        TRONG_TAI_PHU: "Trọng tài phụ",
        GIAM_SAT: "Giám sát",
    };

    const query = new URLSearchParams(window.location.search);
    const matchId = query.get("matchId") ? Number(query.get("matchId")) : null;
    const initialAssignmentId = query.get("assignmentId") ? Number(query.get("assignmentId")) : null;

    let match = null;
    let assignedRefs = [];
    let confirmedParticipants = new Set();
    let sets = [emptySet(), emptySet(), emptySet()];
    let hasLoadedSupervision = false;

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
            const detail = payload.errors
                ? Object.values(payload.errors).filter(Boolean).join(" ")
                : "";
            const message = [payload.message, detail].filter(Boolean).join(" ");
            const error = new Error(message || "Yêu cầu không thành công.");
            error.status = response.status;
            error.payload = payload;
            throw error;
        }

        return payload;
    }

    function formatDateTime(value) {
        if (!value) {
            return "";
        }

        return String(value).replace("T", " ").slice(0, 19);
    }

    function currentDateTime() {
        const now = new Date();
        const pad = (value) => String(value).padStart(2, "0");

        return [
            now.getFullYear(),
            pad(now.getMonth() + 1),
            pad(now.getDate()),
        ].join("-") + " " + [
            pad(now.getHours()),
            pad(now.getMinutes()),
            pad(now.getSeconds()),
        ].join(":");
    }

    function emptySet() {
        return { a: 0, b: 0, startAt: "", endAt: "" };
    }

    function showPage(message, ok = false) {
        pageAlert.textContent = message || "";
        pageAlert.classList.toggle("hidden", !message);
        pageAlert.classList.toggle("is-ok", ok);
    }

    function showRefs(message) {
        rAlert.textContent = message || "";
        rAlert.classList.toggle("hidden", !message);
    }

    function assignmentBadge(status) {
        return assignmentStatusMap[status] || ["gray", status || "-"];
    }

    function attendanceBadge(referee) {
        if (referee.trangthai && referee.trangthai !== "DA_XAC_NHAN") {
            return assignmentBadge(referee.trangthai);
        }

        const status = referee.trangthai_thamgia
            || (referee.xacnhanthamgia
                ? "THAM_GIA"
                : (match?.trangthai === "DA_KET_THUC" ? "VANG" : "CHUA_XAC_NHAN_CO_MAT"));

        return attendanceStatusMap[status] || assignmentBadge(referee.trangthai);
    }

    function assignmentStatusLabel(status) {
        return assignmentStatusMap[status]?.[1] || status || "-";
    }

    function roleLabel(role) {
        return roleMap[role] || role || "-";
    }

    function matchStatusLabel(status) {
        return matchStatusMap[status] || status || "-";
    }

    function refereeDisplayName(referee) {
        const name = String(referee.hoten || "").trim();
        const username = String(referee.username || "").trim();

        if (name && username) {
            return `${name} (${username})`;
        }

        return name || username || `#${referee.idtrongtai || ""}`;
    }

    function matchEndpoint(suffix = "supervision") {
        return `${supervisionApi}/${encodeURIComponent(matchId)}/${suffix}`;
    }

    function currentAssignment() {
        return match?.phancong_cua_toi || null;
    }

    function currentAssignmentId() {
        return currentAssignment()?.idphancong || initialAssignmentId || null;
    }

    function teamOneName() {
        return match?.doi1?.tendoibong || "-";
    }

    function teamTwoName() {
        return match?.doi2?.tendoibong || "-";
    }

    function setButtonsLoading(loading) {
        [btnJoin, btnPickRefs, btnStart, btnPause, btnResume, btnEnd, rConfirm].forEach((button) => {
            button.dataset.loading = loading ? "1" : "";
            button.disabled = loading;
        });
    }

    function refreshHeader() {
        if (!match) {
            return;
        }

        matchTitle.textContent = `#${match.idtrandau} • ${teamOneName()} vs ${teamTwoName()}`;
        matchSub.textContent = `${match.giaidau?.tengiaidau || ""} • ${match.sandau?.tensandau || ""} • ${formatDateTime(match.thoigianbatdau)}`;
        matchState.textContent = `Trạng thái: ${matchStatusLabel(match.trangthai)}`;
        mMatchId.value = match.idtrandau || "";
        mTeamOne.value = teamOneName();
        mTeamTwo.value = teamTwoName();
        mTournament.value = match.giaidau?.tengiaidau || "";
        mVenue.value = match.sandau?.tensandau || "";
        mRound.value = match.vongdau || "";
        mStart.value = formatDateTime(match.thoigianbatdau);
        mEnd.value = formatDateTime(match.thoigianketthuc);
    }

    function resultVisible() {
        return ["DANG_DIEN_RA", "TAM_DUNG", "DA_KET_THUC"].includes(String(match?.trangthai || ""));
    }

    function resultEditable() {
        return String(match?.trangthai || "") === "DANG_DIEN_RA";
    }

    function participantReadiness(ids = confirmedParticipants) {
        const selected = assignedRefs.filter((referee) => ids.has(Number(referee.idtrongtai)) && referee.trangthai === "DA_XAC_NHAN");

        return {
            hasSupervisor: selected.some((referee) => referee.vaitro === "GIAM_SAT"),
            hasMainReferee: selected.some((referee) => referee.vaitro === "TRONG_TAI_CHINH"),
        };
    }

    function requiredParticipantMessage(ids = confirmedParticipants) {
        const readiness = participantReadiness(ids);
        const missing = [];

        if (!readiness.hasSupervisor) {
            missing.push("1 trọng tài giám sát");
        }

        if (!readiness.hasMainReferee) {
            missing.push("1 trọng tài chính");
        }

        return missing.length > 0 ? `Tổ trọng tài tham gia cần tối thiểu ${missing.join(" và ")}.` : "";
    }

    function refreshButtons() {
        const assignment = currentAssignment();
        const assignmentStatus = assignment?.trangthai || "";
        const actions = match?.actions || {};
        const loadingButtons = root.querySelectorAll("[data-loading='1']");

        btnJoin.disabled = !(assignmentStatus === "CHO_XAC_NHAN");
        btnJoin.textContent = assignmentStatus === "DA_XAC_NHAN" ? "Đã nhận phân công" : "Xác nhận nhận phân công";
        btnPickRefs.disabled = !actions.confirm_participants;
        btnStart.disabled = !actions.start;
        btnPause.disabled = !actions.pause;
        btnResume.disabled = !actions.resume;
        btnEnd.disabled = !actions.finish || !resultEditable();
        resultCard?.classList.toggle("hidden", !resultVisible());
        refreshResultControls();

        loadingButtons.forEach((button) => {
            button.disabled = true;
        });
    }

    function setsFromResult(result) {
        const resultSets = Array.isArray(result?.sets) ? result.sets : [];

        if (resultSets.length === 0) {
            return [emptySet(), emptySet(), emptySet()];
        }

        return resultSets.map((item) => ({
            a: Number(item.diemdoi1 || 0),
            b: Number(item.diemdoi2 || 0),
            startAt: item.thoigianbatdau || item.thoigianbatdau_set || item.start_at || "",
            endAt: item.thoigianketthuc || item.thoigianketthuc_set || item.end_at || "",
        }));
    }

    function hasBackendResult(result) {
        return Array.isArray(result?.sets) && result.sets.length > 0;
    }

    function hasResultDraft() {
        return sets.length !== 3
            || sets.some((item) => Number(item.a || 0) > 0 || Number(item.b || 0) > 0 || item.startAt || item.endAt)
            || String(resultNote.value || "").trim() !== "";
    }

    function computeSetWins() {
        let a = 0;
        let b = 0;

        for (const item of sets) {
            const one = Number(item.a || 0);
            const two = Number(item.b || 0);

            if (one > two) {
                a += 1;
            } else if (two > one) {
                b += 1;
            }
        }

        return { a, b };
    }

    function recalcResult() {
        const score = computeSetWins();
        setScore.value = `${score.a} - ${score.b}`;
        winner.value = score.a === score.b ? "Chưa xác định" : (score.a > score.b ? teamOneName() : teamTwoName());
        resultState.textContent = `Kết quả: ${setScore.value}`;
    }

    function canStartSet(index) {
        if (!resultEditable() || !sets[index] || sets[index].startAt) {
            return false;
        }

        return index === 0 || !!sets[index - 1]?.endAt;
    }

    function canEndSet(index) {
        return resultEditable() && !!sets[index]?.startAt && !sets[index]?.endAt;
    }

    function canScoreSet(index) {
        return resultEditable() && !!sets[index]?.startAt && !sets[index]?.endAt;
    }

    function setScoreGapMessage(index) {
        const item = sets[index];

        if (!item) {
            return "";
        }

        const one = Number(item.a || 0);
        const two = Number(item.b || 0);

        return Math.abs(one - two) < 2 ? `Set ${index + 1} phải chênh lệch tối thiểu 2 điểm mới được kết thúc.` : "";
    }

    function refreshResultControls() {
        const editable = resultEditable();

        if (resultNote) {
            resultNote.disabled = !editable;
        }

        if (btnAddSet) {
            btnAddSet.disabled = !editable || sets.length >= 5;
        }

        if (btnRemoveSet) {
            btnRemoveSet.disabled = !editable || sets.length <= 3;
        }
    }

    function scoreControl(index, side, value, placeholder, ariaLabel) {
        const disabled = canScoreSet(index) ? "" : "disabled";

        return `
            <div class="score-control">
                <input class="score-entry" type="number" min="1" step="1" inputmode="numeric" data-score-entry="1" data-idx="${index}" data-side="${side}" placeholder="${escapeHtml(placeholder)}" aria-label="${escapeHtml(placeholder)}" ${disabled} />
                <div class="score-actions" aria-label="${escapeHtml(ariaLabel)}">
                    <button class="score-step" type="button" data-score-action="add" data-idx="${index}" data-side="${side}" aria-label="Cộng ${escapeHtml(ariaLabel)}" ${disabled}>+</button>
                    <button class="score-step" type="button" data-score-action="subtract" data-idx="${index}" data-side="${side}" aria-label="Trừ ${escapeHtml(ariaLabel)}" ${disabled}>-</button>
                </div>
                <input class="score-display" type="number" value="${escapeHtml(value)}" data-idx="${index}" data-side="${side}" readonly tabindex="-1" aria-label="Điểm hiện tại ${escapeHtml(ariaLabel)}" />
            </div>
        `;
    }

    function setTimeControls(item, index) {
        const startDisabled = canStartSet(index) ? "" : "disabled";
        const endDisabled = canEndSet(index) ? "" : "disabled";

        return `
            <div class="set-time-row">
                <button class="btn set-time-btn set-time-start" type="button" data-set-time-action="start" data-idx="${index}" ${startDisabled}>Bắt đầu</button>
                <span class="set-time-value" data-set-time-field="startAt" data-idx="${index}">${escapeHtml(formatDateTime(item.startAt) || "--:--:--")}</span>
                <span class="set-time-separator">-</span>
                <button class="btn set-time-btn set-time-end" type="button" data-set-time-action="end" data-idx="${index}" ${endDisabled}>Kết thúc</button>
                <span class="set-time-value" data-set-time-field="endAt" data-idx="${index}">${escapeHtml(formatDateTime(item.endAt) || "--:--:--")}</span>
            </div>
        `;
    }

    function renderSets() {
        setsEl.innerHTML = sets.map((item, index) => `
            <div class="set-block">
                ${setTimeControls(item, index)}
                <div class="set-row">
                    <div class="tag">Set ${index + 1}</div>
                    ${scoreControl(index, "a", item.a, "Ghi điểm đội 1", `điểm đội 1 set ${index + 1}`)}
                    ${scoreControl(index, "b", item.b, "Ghi điểm đội 2", `điểm đội 2 set ${index + 1}`)}
                </div>
            </div>
        `).join("");
        recalcResult();
        refreshResultControls();
    }

    function syncScoreDisplay(index, side) {
        const display = setsEl.querySelector(`.score-display[data-idx="${index}"][data-side="${side}"]`);

        if (display) {
            display.value = String(sets[index][side]);
        }
    }

    function syncSetTimeDisplay(index, field) {
        const display = setsEl.querySelector(`.set-time-value[data-idx="${index}"][data-set-time-field="${field}"]`);

        if (display) {
            display.textContent = formatDateTime(sets[index][field]) || "--:--:--";
        }
    }

    function applySetTime(button) {
        const index = Number(button.dataset.idx);
        const action = button.dataset.setTimeAction;

        if (!Number.isInteger(index) || !sets[index] || !["start", "end"].includes(action)) {
            return;
        }

        if (!resultEditable()) {
            showPage("Trận đang tạm dừng, không thể thay đổi thông tin ghi nhận kết quả.");
            return;
        }

        if (action === "start" && !canStartSet(index)) {
            showPage(index === 0 ? "Set này đã bắt đầu." : `Set ${index + 1} chỉ được bắt đầu sau khi set ${index} đã kết thúc.`);
            return;
        }

        if (action === "end" && !canEndSet(index)) {
            showPage(sets[index].endAt ? "Set này đã kết thúc." : "Phải bắt đầu set trước khi kết thúc.");
            return;
        }

        if (action === "end") {
            const gapMessage = setScoreGapMessage(index);

            if (gapMessage) {
                showPage(gapMessage);
                return;
            }
        }

        const field = action === "start" ? "startAt" : "endAt";
        sets[index][field] = currentDateTime();
        renderSets();
        showPage("");
    }

    function applyScoreDelta(button) {
        const index = Number(button.dataset.idx);
        const side = button.dataset.side;
        const action = button.dataset.scoreAction;

        if (!Number.isInteger(index) || !sets[index] || !["a", "b"].includes(side) || !["add", "subtract"].includes(action)) {
            return;
        }

        if (!resultEditable()) {
            showPage("Trận đang tạm dừng, không thể thay đổi thông tin ghi nhận kết quả.");
            return;
        }

        if (!canScoreSet(index)) {
            showPage(sets[index]?.endAt ? "Set đã kết thúc, không thể ghi điểm thêm." : "Phải bắt đầu set trước khi ghi điểm.");
            return;
        }

        const control = button.closest(".score-control");
        const input = control?.querySelector("input[data-score-entry]");
        const raw = String(input?.value || "").trim();
        const delta = Number(raw);

        if (!Number.isInteger(delta) || delta <= 0) {
            showPage("Điểm ghi thêm/bớt phải là số nguyên dương.");
            input?.focus();
            return;
        }

        const current = Number(sets[index][side] || 0);
        sets[index][side] = action === "add" ? current + delta : Math.max(0, current - delta);

        if (input) {
            input.value = "";
        }

        syncScoreDisplay(index, side);
        recalcResult();
        showPage("");
    }

    function validateResult() {
        if (sets.length < 3 || sets.length > 5) {
            return "Một trận Bo5 phải có từ 3 đến 5 set.";
        }

        for (const [index, item] of sets.entries()) {
            const one = Number(item.a);
            const two = Number(item.b);

            if (!item.startAt) {
                return `Set ${index + 1} phải bấm bắt đầu trước khi kết thúc trận.`;
            }

            if (!item.endAt) {
                return `Set ${index + 1} phải bấm kết thúc trước khi kết thúc trận.`;
            }

            if (!Number.isInteger(one) || !Number.isInteger(two) || one < 0 || two < 0) {
                return `Điểm set ${index + 1} phải là số nguyên không âm.`;
            }

            if (one === two) {
                return `Set ${index + 1} không được hòa.`;
            }

            if (Math.abs(one - two) < 2) {
                return `Set ${index + 1} phải chênh lệch tối thiểu 2 điểm.`;
            }
        }

        const score = computeSetWins();

        const winnerSets = Math.max(score.a, score.b);
        const loserSets = Math.min(score.a, score.b);

        if (winnerSets !== 3 || loserSets > 2 || score.a === score.b) {
            return "Kết quả Bo5 hợp lệ chỉ có thể là 3-0, 3-1 hoặc 3-2.";
        }

        return null;
    }

    function resultPayload() {
        return {
            sets: sets.map((item, index) => ({
                setthu: index + 1,
                diemdoi1: Number(item.a),
                diemdoi2: Number(item.b),
                thoigianbatdau: item.startAt || null,
                thoigianketthuc: item.endAt || null,
            })),
            note: resultNote.value.trim() || null,
        };
    }

    function renderRefsList() {
        if (assignedRefs.length === 0) {
            rTbody.innerHTML = '<tr><td colspan="5" class="empty">Chưa có trọng tài được phân công.</td></tr>';
            return;
        }

        rTbody.innerHTML = assignedRefs.map((referee) => {
            const id = Number(referee.idtrongtai);
            const [className, label] = attendanceBadge(referee);
            const checked = confirmedParticipants.has(id) ? "checked" : "";
            const disabled = referee.trangthai === "DA_XAC_NHAN" ? "" : "disabled";

            return `
                <tr>
                    <td><input type="checkbox" data-id="${escapeHtml(id)}" ${checked} ${disabled} /></td>
                    <td>${escapeHtml(refereeDisplayName(referee))}</td>
                    <td>${escapeHtml(roleLabel(referee.vaitro))}</td>
                    <td><span class="badge ${className}">${escapeHtml(label)}</span></td>
                    <td>${escapeHtml(formatDateTime(referee.ngayphancong))}</td>
                </tr>
            `;
        }).join("");
    }

    function openRefsModal() {
        showRefs("");
        renderRefsList();
        refsModal.classList.remove("hidden");
    }

    function closeRefsModal() {
        refsModal.classList.add("hidden");
    }

    function applySupervision(data) {
        const shouldKeepDraft = hasLoadedSupervision && hasResultDraft() && !hasBackendResult(data?.ketqua);

        match = data;
        assignedRefs = Array.isArray(data?.trongtai_thamgia) ? data.trongtai_thamgia : [];
        confirmedParticipants = new Set(
            assignedRefs
                .filter((referee) => referee.xacnhanthamgia)
                .map((referee) => Number(referee.idtrongtai))
        );

        if (!shouldKeepDraft) {
            sets = setsFromResult(data?.ketqua);
            resultNote.value = data?.ketqua?.ghichu || data?.ketqua?.note || "";
        }

        refreshHeader();
        renderSets();
        refreshButtons();
        hasLoadedSupervision = true;
    }

    async function loadSupervision() {
        if (!matchId) {
            showPage("Thiếu mã trận đấu. Vui lòng mở chức năng giám sát từ lịch phân công.");
            refreshButtons();
            return;
        }

        showPage("Đang tải dữ liệu giám sát...", true);

        try {
            const payload = await requestJson(matchEndpoint("supervision"));
            const data = responseData(payload);

            if (!data) {
                throw new Error("Không tìm thấy dữ liệu giám sát trận đấu.");
            }

            applySupervision(data);
            showPage("");
        } catch (error) {
            match = null;
            assignedRefs = [];
            refreshButtons();
            showPage(error.message || "Không thể tải dữ liệu giám sát.");
        }
    }

    async function runAction(endpoint, body = null, successMessage = "") {
        setButtonsLoading(true);
        showPage("");

        try {
            const payload = await requestJson(endpoint, {
                method: "POST",
                body: JSON.stringify(body || {}),
            });
            const data = responseData(payload);

            if (data) {
                applySupervision(data);
            } else {
                await loadSupervision();
            }

            showPage(successMessage || payload.message || "Cập nhật thành công.", true);
            return true;
        } catch (error) {
            showPage(error.message || "Không thể thực hiện thao tác.");
            refreshButtons();
            return false;
        } finally {
            setButtonsLoading(false);
            refreshButtons();
        }
    }

    btnJoin.addEventListener("click", async () => {
        const assignmentId = currentAssignmentId();

        if (!assignmentId) {
            showPage("Không tìm thấy mã phân công của trọng tài.");
            return;
        }

        await runAction(`${assignmentsApi}/${encodeURIComponent(assignmentId)}/confirm`, {}, "Đã nhận phân công trận đấu.");
    });

    btnPickRefs.addEventListener("click", openRefsModal);
    rClose.addEventListener("click", closeRefsModal);
    rCancel.addEventListener("click", closeRefsModal);

    rTbody.addEventListener("change", (event) => {
        const input = event.target.closest("input[type='checkbox'][data-id]");

        if (!input) {
            return;
        }

        const id = Number(input.dataset.id);

        if (!id) {
            return;
        }

        if (input.checked) {
            confirmedParticipants.add(id);
        } else {
            confirmedParticipants.delete(id);
        }
    });

    rConfirm.addEventListener("click", async () => {
        showRefs("");

        if (confirmedParticipants.size === 0) {
            showRefs("Vui lòng chọn ít nhất 1 trọng tài tham gia.");
            return;
        }

        const requiredMessage = requiredParticipantMessage();

        if (requiredMessage) {
            showRefs(requiredMessage);
            return;
        }

        const ok = await runAction(matchEndpoint("participants/confirm"), {
            referee_ids: Array.from(confirmedParticipants),
        }, "Đã xác nhận tổ trọng tài tham gia.");

        if (ok) {
            closeRefsModal();
        }
    });

    btnStart.addEventListener("click", () => runAction(matchEndpoint("start"), {}, "Đã bắt đầu trận đấu."));
    btnPause.addEventListener("click", () => runAction(matchEndpoint("pause"), {}, "Đã tạm dừng trận đấu."));
    btnResume.addEventListener("click", () => runAction(matchEndpoint("resume"), {}, "Đã tiếp tục trận đấu."));

    btnEnd.addEventListener("click", async () => {
        const error = validateResult();

        if (error) {
            showPage(`Trước khi kết thúc, vui lòng nhập kết quả hợp lệ. ${error}`);
            return;
        }

        if (!window.confirm("Kết thúc trận đấu và ghi nhận kết quả hiện tại?")) {
            return;
        }

        await runAction(matchEndpoint("finish"), resultPayload(), "Đã kết thúc trận đấu.");
    });

    setsEl.addEventListener("click", (event) => {
        const timeButton = event.target.closest("button[data-set-time-action]");

        if (timeButton) {
            applySetTime(timeButton);
            return;
        }

        const button = event.target.closest("button[data-score-action]");

        if (!button) {
            return;
        }

        applyScoreDelta(button);
    });

    setsEl.addEventListener("keydown", (event) => {
        const input = event.target.closest("input[data-score-entry]");

        if (!input || event.key !== "Enter") {
            return;
        }

        event.preventDefault();
        input.closest(".score-control")?.querySelector('button[data-score-action="add"]')?.click();
    });

    btnAddSet.addEventListener("click", () => {
        if (!resultEditable()) {
            showPage("Trận đang tạm dừng, không thể thay đổi thông tin ghi nhận kết quả.");
            return;
        }

        if (sets.length >= 5) {
            return;
        }

        sets.push(emptySet());
        renderSets();
    });

    btnRemoveSet.addEventListener("click", () => {
        if (!resultEditable()) {
            showPage("Trận đang tạm dừng, không thể thay đổi thông tin ghi nhận kết quả.");
            return;
        }

        if (sets.length <= 3) {
            return;
        }

        sets.pop();
        renderSets();
    });

    loadSupervision();
})();
