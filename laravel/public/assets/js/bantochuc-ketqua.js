(function () {
    const root = document.querySelector(".organizer-results");

    if (!root) {
        return;
    }

    const resultsApi = root.dataset.resultsApi || "/api/organizer/match-results";
    const tournamentsApi = root.dataset.tournamentsApi || "/api/organizer/tournaments";

    const tbody = document.getElementById("tbody");
    const q = document.getElementById("q");
    const tournamentFilter = document.getElementById("tournamentFilter");
    const publishFilter = document.getElementById("publishFilter");
    const fromDate = document.getElementById("fromDate");
    const toDate = document.getElementById("toDate");
    const btnRefresh = document.getElementById("btnRefresh");
    const pageMessage = document.getElementById("pageMessage");

    const sEnded = document.getElementById("sEnded");
    const sUnpub = document.getElementById("sUnpub");
    const sPub = document.getElementById("sPub");

    const editModal = document.getElementById("editModal");
    const mClose = document.getElementById("m_close");
    const mCancel = document.getElementById("m_cancel");
    const mSave = document.getElementById("m_save");
    const mAlert = document.getElementById("m_alert");
    const mSub = document.getElementById("m_sub");
    const mMatchId = document.getElementById("m_matchId");
    const mPublish = document.getElementById("m_publish");
    const mTeam1 = document.getElementById("m_team1");
    const mTeam2 = document.getElementById("m_team2");
    const setsEl = document.getElementById("sets");
    const btnAddSet = document.getElementById("btnAddSet");
    const btnRemoveSet = document.getElementById("btnRemoveSet");
    const mSetScore = document.getElementById("m_setScore");
    const mWinner = document.getElementById("m_winner");
    const mReason = document.getElementById("m_reason");
    const mEvidence = document.getElementById("m_evidence");

    const statusMap = {
        CHO_CONG_BO: ["unpub", "Chờ công bố"],
        DA_DIEU_CHINH: ["adjusted", "Đã điều chỉnh"],
        DA_CONG_BO: ["pub", "Đã công bố"],
        BI_HUY: ["cancel", "Bị hủy"],
    };

    let results = [];
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

    function statusLabel(status) {
        return statusMap[status]?.[1] || status || "-";
    }

    function badge(status) {
        return statusMap[status] || ["cancel", status || "-"];
    }

    function formatDateTime(value) {
        if (!value) {
            return "";
        }

        return String(value).replace("T", " ").slice(0, 19);
    }

    function setScoreText(result) {
        return `${Number(result.sosetdoi1 || 0)} - ${Number(result.sosetdoi2 || 0)}`;
    }

    function totalScoreText(result) {
        return `${Number(result.diemdoi1 || 0)} - ${Number(result.diemdoi2 || 0)}`;
    }

    function isMutable(result) {
        return ["CHO_CONG_BO", "DA_DIEU_CHINH"].includes(String(result.trangthai || ""));
    }

    function updateStats(meta) {
        const stats = meta?.stats || {};
        const unpub = Number(stats.CHO_CONG_BO || 0) + Number(stats.DA_DIEU_CHINH || 0);
        const published = Number(stats.DA_CONG_BO || 0);

        sEnded.textContent = String(results.length);
        sUnpub.textContent = String(unpub);
        sPub.textContent = String(published);
    }

    async function loadTournaments() {
        try {
            const payload = await requestJson(apiUrl(tournamentsApi, { status: "DA_CONG_BO" }));
            const data = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            tournamentFilter.innerHTML = '<option value="">Tất cả giải đấu</option>' + data.map((tournament) => (
                `<option value="${escapeHtml(tournament.idgiaidau)}">${escapeHtml(tournament.tengiaidau)}</option>`
            )).join("");
        } catch (error) {
            tournamentFilter.innerHTML = '<option value="">Tất cả giải đấu</option>';
        }
    }

    async function loadResults() {
        showPageMessage("Đang tải dữ liệu...");

        try {
            const payload = await requestJson(apiUrl(resultsApi, {
                q: q.value.trim(),
                tournament_id: tournamentFilter.value,
                status: publishFilter.value,
                from: fromDate.value,
                to: toDate.value,
            }));

            results = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            updateStats(payload.meta);
            render();
            showPageMessage("");
        } catch (error) {
            results = [];
            updateStats(null);
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Không thể tải danh sách kết quả trận đấu.</td></tr>';
            showPageMessage(error.message || "Không thể tải danh sách kết quả trận đấu.", true);
        }
    }

    function render() {
        if (results.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Không có kết quả trận đấu phù hợp.</td></tr>';
            return;
        }

        tbody.innerHTML = results.map((item) => {
            const [className, label] = badge(item.trangthai);
            const mutable = isMutable(item);

            return `
                <tr>
                    <td>
                        <div>#${escapeHtml(item.idtrandau)}</div>
                        <div class="sub">KQ #${escapeHtml(item.idketqua)}</div>
                    </td>
                    <td>${escapeHtml(item.tengiaidau)}</td>
                    <td>
                        <div style="font-weight:800">${escapeHtml(item.doi1)} vs ${escapeHtml(item.doi2)}</div>
                        <div class="sub">${escapeHtml(item.vongdau || "")}</div>
                    </td>
                    <td>
                        <div>BĐ: ${escapeHtml(formatDateTime(item.thoigianbatdau))}</div>
                        <div class="sub">KT: ${escapeHtml(formatDateTime(item.thoigianketthuc))}</div>
                    </td>
                    <td>${escapeHtml(item.tensandau)}</td>
                    <td>
                        <div style="font-weight:800">${escapeHtml(setScoreText(item))}</div>
                        <div class="sub">Điểm: ${escapeHtml(totalScoreText(item))}</div>
                    </td>
                    <td><span class="badge ${className}">${escapeHtml(label)}</span></td>
                    <td>
                        <div style="display:flex; gap:8px; flex-wrap:wrap">
                            <button class="btn" type="button" data-action="edit" data-id="${escapeHtml(item.idketqua)}"${mutable ? "" : " disabled"}>Chỉnh sửa</button>
                            <button class="btn primary" type="button" data-action="publish" data-id="${escapeHtml(item.idketqua)}"${mutable ? "" : " disabled"}>Công bố</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join("");
    }

    function detailSets(result) {
        const sets = Array.isArray(result.sets) ? result.sets : [];

        if (sets.length > 0) {
            return sets.map((set, index) => ({
                setthu: Number(set.setthu || index + 1),
                a: Number(set.diemdoi1 || 0),
                b: Number(set.diemdoi2 || 0),
            }));
        }

        const totalSets = Math.max(3, Math.min(5, Number(result.sosetdoi1 || 0) + Number(result.sosetdoi2 || 0)));

        return Array.from({ length: totalSets }, (_, index) => ({
            setthu: index + 1,
            a: 0,
            b: 0,
        }));
    }

    function computeSetScore(sets) {
        let teamOneSets = 0;
        let teamTwoSets = 0;

        sets.forEach((set) => {
            if (Number(set.a) > Number(set.b)) {
                teamOneSets++;
            } else if (Number(set.b) > Number(set.a)) {
                teamTwoSets++;
            }
        });

        return { teamOneSets, teamTwoSets };
    }

    function computedWinner() {
        const score = computeSetScore(current.sets);

        if (score.teamOneSets === score.teamTwoSets) {
            return null;
        }

        return score.teamOneSets > score.teamTwoSets ? Number(current.iddoibong1) : Number(current.iddoibong2);
    }

    function winnerName() {
        const winner = computedWinner();

        if (winner === Number(current.iddoibong1)) {
            return current.doi1;
        }

        if (winner === Number(current.iddoibong2)) {
            return current.doi2;
        }

        return "Hòa, cần kiểm tra";
    }

    function renderSets() {
        setsEl.innerHTML = current.sets.map((set, index) => `
            <div class="set-row">
                <div class="tag">Set ${index + 1}</div>
                <input type="number" min="0" data-index="${index}" data-side="a" value="${escapeHtml(set.a)}" placeholder="Điểm đội 1" />
                <input type="number" min="0" data-index="${index}" data-side="b" value="${escapeHtml(set.b)}" placeholder="Điểm đội 2" />
            </div>
        `).join("");
    }

    function recalc() {
        if (!current) {
            return;
        }

        const score = computeSetScore(current.sets);
        mSetScore.value = `${score.teamOneSets} - ${score.teamTwoSets}`;
        mWinner.value = winnerName();
    }

    async function openEdit(resultId) {
        hideAlert();
        mSave.disabled = false;

        try {
            const payload = await requestJson(`${resultsApi}/${resultId}`);
            const data = responseData(payload);

            if (!data || !isMutable(data)) {
                showPageMessage("Chỉ được chỉnh sửa kết quả đang chờ công bố hoặc đã điều chỉnh.", true);
                return;
            }

            current = {
                ...data,
                sets: detailSets(data),
            };
        } catch (error) {
            showPageMessage(error.message || "Không thể tải chi tiết kết quả trận đấu.", true);
            return;
        }

        mSub.textContent = `${current.tengiaidau} - ${current.doi1} vs ${current.doi2}`;
        mMatchId.value = current.idtrandau || "";
        mPublish.value = statusLabel(current.trangthai);
        mTeam1.value = current.doi1 || "";
        mTeam2.value = current.doi2 || "";
        mReason.value = "";
        mEvidence.value = "";

        renderSets();
        recalc();
        editModal.classList.remove("hidden");
    }

    function closeModal() {
        editModal.classList.add("hidden");
        current = null;
    }

    function validateEdit() {
        const reason = mReason.value.trim();

        if (!reason) {
            return "Vui lòng nhập lý do điều chỉnh.";
        }

        if (current.sets.length < 3 || current.sets.length > 5) {
            return "Một trận Bo5 phải có từ 3 đến 5 set.";
        }

        for (const set of current.sets) {
            if (Number(set.a) < 0 || Number(set.b) < 0) {
                return "Điểm từng set phải lớn hơn hoặc bằng 0.";
            }

            if (Number(set.a) === Number(set.b)) {
                return "Điểm hai đội trong một set không được bằng nhau.";
            }
        }

        const score = computeSetScore(current.sets);
        const winnerSets = Math.max(score.teamOneSets, score.teamTwoSets);
        const loserSets = Math.min(score.teamOneSets, score.teamTwoSets);

        if (winnerSets !== 3 || loserSets > 2 || computedWinner() === null) {
            return "Kết quả Bo5 hợp lệ chỉ có thể là 3-0, 3-1 hoặc 3-2.";
        }

        return null;
    }

    function adjustmentPayload() {
        const score = computeSetScore(current.sets);
        const winner = computedWinner();
        const totalTeamOneScore = current.sets.reduce((total, set) => total + Number(set.a || 0), 0);
        const totalTeamTwoScore = current.sets.reduce((total, set) => total + Number(set.b || 0), 0);

        return {
            iddoithang: winner,
            diemdoi1: totalTeamOneScore,
            diemdoi2: totalTeamTwoScore,
            sosetdoi1: score.teamOneSets,
            sosetdoi2: score.teamTwoSets,
            lydo: mReason.value.trim(),
            minhchung: mEvidence.value.trim() || null,
            sets: current.sets.map((set, index) => ({
                setthu: index + 1,
                diemdoi1: Number(set.a || 0),
                diemdoi2: Number(set.b || 0),
                doithangset: Number(set.a) > Number(set.b) ? Number(current.iddoibong1) : Number(current.iddoibong2),
            })),
        };
    }

    async function saveEdit() {
        if (!current) {
            return;
        }

        hideAlert();
        const validationError = validateEdit();

        if (validationError) {
            showAlert(validationError);
            return;
        }

        mSave.disabled = true;

        try {
            await requestJson(`${resultsApi}/${current.idketqua}/adjust`, {
                method: "POST",
                body: JSON.stringify(adjustmentPayload()),
            });
            closeModal();
            await loadResults();
            showPageMessage("Cập nhật kết quả trận đấu thành công.");
        } catch (error) {
            showAlert(error.message || "Không thể cập nhật kết quả trận đấu.");
            mSave.disabled = false;
        }
    }

    async function publishResult(resultId) {
        const item = results.find((result) => Number(result.idketqua) === Number(resultId));

        if (!item || !isMutable(item)) {
            return;
        }

        if (!window.confirm("Công bố kết quả trận đấu này?")) {
            return;
        }

        showPageMessage("Đang công bố kết quả...");

        try {
            await requestJson(`${resultsApi}/${resultId}/publish`, {
                method: "POST",
                body: JSON.stringify({}),
            });
            await loadResults();
            showPageMessage("Công bố kết quả trận đấu thành công.");
        } catch (error) {
            showPageMessage(error.message || "Không thể công bố kết quả trận đấu.", true);
        }
    }

    setsEl.addEventListener("input", (event) => {
        const input = event.target.closest("input[data-index]");

        if (!input || !current) {
            return;
        }

        const index = Number(input.dataset.index);
        const side = input.dataset.side;

        if (!Number.isInteger(index) || !["a", "b"].includes(side)) {
            return;
        }

        current.sets[index][side] = Number(input.value || 0);
        recalc();
    });

    btnAddSet.addEventListener("click", () => {
        if (!current || current.sets.length >= 5) {
            return;
        }

        current.sets.push({
            setthu: current.sets.length + 1,
            a: 0,
            b: 0,
        });
        renderSets();
        recalc();
    });

    btnRemoveSet.addEventListener("click", () => {
        if (!current || current.sets.length <= 3) {
            return;
        }

        current.sets.pop();
        renderSets();
        recalc();
    });

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-action]");

        if (!button) {
            return;
        }

        if (button.dataset.action === "edit") {
            openEdit(button.dataset.id);
            return;
        }

        if (button.dataset.action === "publish") {
            publishResult(button.dataset.id);
        }
    });

    mClose.addEventListener("click", closeModal);
    mCancel.addEventListener("click", closeModal);
    mSave.addEventListener("click", saveEdit);
    btnRefresh.addEventListener("click", loadResults);
    q.addEventListener("input", loadResults);
    tournamentFilter.addEventListener("change", loadResults);
    publishFilter.addEventListener("change", loadResults);
    fromDate.addEventListener("change", loadResults);
    toDate.addEventListener("change", loadResults);

    loadTournaments().then(loadResults);
})();
