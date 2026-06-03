(function () {
    const root = document.querySelector(".organizer-standings");

    if (!root) {
        return;
    }

    const standingsApi = root.dataset.standingsApi || "/api/organizer/standings";
    const tournamentsApi = root.dataset.tournamentsApi || "/api/organizer/standings/tournaments";

    const tournamentSelect = document.getElementById("tournamentSelect");
    const rankName = document.getElementById("rankName");
    const btnGenerate = document.getElementById("btnGenerate");
    const btnPublish = document.getElementById("btnPublish");
    const btnRefresh = document.getElementById("btnRefresh");
    const bxhId = document.getElementById("bxhId");
    const bxhStatus = document.getElementById("bxhStatus");
    const bxhCreated = document.getElementById("bxhCreated");
    const bxhPublished = document.getElementById("bxhPublished");
    const pageAlert = document.getElementById("pageAlert");
    const tbody = document.getElementById("tbody");
    const knockoutPlan = document.getElementById("knockoutPlan");

    const statusMap = {
        BAN_NHAP: "Bản nháp",
        DA_CAP_NHAT: "Đã cập nhật",
        DA_CONG_BO: "Đã công bố",
    };

    let tournaments = [];
    let currentTournament = null;
    let currentBxh = null;

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

    function showAlert(message, isError = false) {
        pageAlert.textContent = message || "";
        pageAlert.classList.toggle("is-error", isError);
        pageAlert.classList.toggle("hidden", !message);
    }

    function clearAlert() {
        showAlert("");
    }

    function formatDateTime(value) {
        if (!value) {
            return "-";
        }

        return String(value).replace("T", " ").slice(0, 19);
    }

    function statusLabel(status) {
        return statusMap[status] || status || "-";
    }

    function tournamentIsEligible(tournament) {
        return Number(tournament?.published_results || 0) > 0
            && Number(tournament?.unresolved_results || 0) === 0;
    }

    function tournamentCanBeOpened(tournament) {
        return tournamentIsEligible(tournament) || Boolean(tournament?.latest_ranking_id);
    }

    function tournamentOptionSuffix(tournament) {
        const published = Number(tournament.published_results || 0);
        const unresolved = Number(tournament.unresolved_results || 0);

        if (tournament.latest_ranking_id) {
            return ` - BXH hiện tại: ${statusLabel(tournament.latest_ranking_status)}`;
        }

        if (tournamentIsEligible(tournament)) {
            return ` - ${published} KQ đã công bố`;
        }

        if (published <= 0) {
            return " - chưa có KQ đã công bố";
        }

        if (unresolved > 0) {
            return ` - còn ${unresolved} KQ chưa công bố`;
        }

        return " - chưa đủ điều kiện";
    }

    function defaultRankingName(tournament) {
        return tournament?.latest_ranking_name || `Bảng xếp hạng ${tournament?.tengiaidau || ""}`.trim();
    }

    function setCurrentRanking(ranking) {
        currentBxh = ranking;
        bxhId.textContent = ranking?.idbangxephang ?? "-";
        bxhStatus.textContent = statusLabel(ranking?.trangthai);
        bxhCreated.textContent = formatDateTime(ranking?.ngaytao);
        bxhPublished.textContent = formatDateTime(ranking?.ngaycongbo);

        if (ranking?.tenbangxephang) {
            rankName.value = ranking.tenbangxephang;
        }

        btnPublish.disabled = !(ranking && ranking.trangthai !== "DA_CONG_BO");
        renderTable(ranking?.details || []);
        renderKnockoutPlan(ranking?.knockout_plan || null);
        updateGenerateState();
    }

    function updateGenerateState() {
        btnGenerate.disabled = !(
            currentTournament
            && tournamentIsEligible(currentTournament)
            && rankName.value.trim()
        );
    }

    function renderTable(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Chưa có dữ liệu xếp hạng để hiển thị.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(row.hang)}</td>
                <td>
                    <div style="font-weight:800">${escapeHtml(row.tendoibong)}</div>
                    <div class="sub">Đội #${escapeHtml(row.iddoibong)}</div>
                </td>
                <td>${escapeHtml(row.sotran)}</td>
                <td>${escapeHtml(row.thang)}</td>
                <td>${escapeHtml(row.thua)}</td>
                <td>${escapeHtml(row.sosetthang)}</td>
                <td>${escapeHtml(row.sosetthua)}</td>
                <td>${escapeHtml(row.diem)}</td>
            </tr>
        `).join("");
    }

    function teamLabel(team) {
        if (!team) {
            return "Chưa xác định";
        }

        return `#${escapeHtml(team.rank)} ${escapeHtml(team.team_name || ("Đội " + team.team_id))}`;
    }

    function renderKnockoutPlan(plan) {
        if (!knockoutPlan) {
            return;
        }

        if (!plan || !Array.isArray(plan.quarterfinals)) {
            knockoutPlan.innerHTML = '<div class="empty">Chưa có dữ liệu nhánh đấu.</div>';
            return;
        }

        const eliminated = Array.isArray(plan.eliminated) && plan.eliminated.length > 0
            ? plan.eliminated.map(teamLabel).join(", ")
            : "Chưa xác định";

        const quarterfinals = plan.quarterfinals.map((match) => `
            <div class="bracket-card">
                <span>${escapeHtml(match.label)}</span>
                <strong>${teamLabel(match.teams?.[0])} vs ${teamLabel(match.teams?.[1])}</strong>
            </div>
        `).join("");

        knockoutPlan.innerHTML = `
            <div class="bracket-note">Bị loại sau vòng sơ bộ: ${eliminated}</div>
            <div class="bracket-grid">${quarterfinals}</div>
            <div class="bracket-note">Bán kết: Tứ kết 1 vs Tứ kết 4; Tứ kết 2 vs Tứ kết 3. Chung kết: thắng hai bán kết. Tranh hạng 3: thua hai bán kết.</div>
        `;
    }

    function renderTournamentOptions() {
        tournamentSelect.innerHTML = '<option value="">Chọn giải đấu...</option>' + tournaments.map((tournament) => {
            const suffix = tournamentOptionSuffix(tournament);
            const disabled = tournamentCanBeOpened(tournament) ? "" : " disabled";

            return `<option value="${escapeHtml(tournament.idgiaidau)}"${disabled}>${escapeHtml(tournament.tengiaidau + suffix)}</option>`;
        }).join("");
    }

    async function loadTournaments(keepSelection = true) {
        const selectedId = keepSelection ? tournamentSelect.value : "";
        tournamentSelect.disabled = true;
        showAlert("Đang tải danh sách giải đấu...");

        try {
            const payload = await requestJson(tournamentsApi);
            const data = responseData(payload);
            tournaments = Array.isArray(data) ? data : [];
            renderTournamentOptions();

            let hasSelectedTournament = false;

            if (selectedId && tournaments.some((item) => String(item.idgiaidau) === String(selectedId) && tournamentCanBeOpened(item))) {
                tournamentSelect.value = selectedId;
                hasSelectedTournament = true;
            }

            if (!hasSelectedTournament) {
                const firstOpenable = tournaments.find(tournamentCanBeOpened);

                if (firstOpenable) {
                    tournamentSelect.value = firstOpenable.idgiaidau;
                }
            }

            clearAlert();
            await selectTournament(tournamentSelect.value);
        } catch (error) {
            tournaments = [];
            renderTournamentOptions();
            setCurrentRanking(null);
            showAlert(error.message || "Không thể tải danh sách giải đấu.", true);
        } finally {
            tournamentSelect.disabled = false;
        }
    }

    async function loadRanking(rankingId) {
        const payload = await requestJson(`${standingsApi}/${rankingId}`);
        return responseData(payload);
    }

    async function selectTournament(tournamentId) {
        clearAlert();
        currentTournament = tournaments.find((item) => String(item.idgiaidau) === String(tournamentId)) || null;
        setCurrentRanking(null);

        if (!currentTournament) {
            rankName.value = "";
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Chọn giải đấu để xem hoặc tạo bảng xếp hạng.</td></tr>';
            updateGenerateState();
            return;
        }

        rankName.value = defaultRankingName(currentTournament);

        if (currentTournament.latest_ranking_id) {
            try {
                setCurrentRanking(await loadRanking(currentTournament.latest_ranking_id));
            } catch (error) {
                showAlert(error.message || "Không thể tải bảng xếp hạng hiện tại.", true);
            }
            return;
        }

        if (!tournamentIsEligible(currentTournament)) {
            showAlert("Giải đấu chưa đủ điều kiện tạo BXH: cần có kết quả đã công bố và không còn trận đã kết thúc chưa công bố kết quả.", true);
            updateGenerateState();
            return;
        }

        updateGenerateState();
    }

    async function generateStandings() {
        if (!currentTournament) {
            return;
        }

        const name = rankName.value.trim();

        if (!name) {
            showAlert("Vui lòng nhập tên bảng xếp hạng.", true);
            return;
        }

        btnGenerate.disabled = true;
        showAlert("Đang tạo bảng xếp hạng...");

        try {
            const payload = await requestJson(`${standingsApi}/generate`, {
                method: "POST",
                body: JSON.stringify({
                    idgiaidau: Number(currentTournament.idgiaidau),
                    tenbangxephang: name,
                }),
            });
            const ranking = responseData(payload);
            setCurrentRanking(ranking);
            showAlert("Tạo bảng xếp hạng thành công.");

            currentTournament.latest_ranking_id = ranking?.idbangxephang || currentTournament.latest_ranking_id;
            currentTournament.latest_ranking_name = ranking?.tenbangxephang || currentTournament.latest_ranking_name;
            currentTournament.latest_ranking_status = ranking?.trangthai || currentTournament.latest_ranking_status;
        } catch (error) {
            showAlert(error.message || "Không thể tạo bảng xếp hạng.", true);
        } finally {
            updateGenerateState();
        }
    }

    async function publishStandings() {
        if (!currentBxh || currentBxh.trangthai === "DA_CONG_BO") {
            return;
        }

        if (!window.confirm("Công bố bảng xếp hạng này?")) {
            return;
        }

        btnPublish.disabled = true;
        showAlert("Đang công bố bảng xếp hạng...");

        try {
            const payload = await requestJson(`${standingsApi}/${currentBxh.idbangxephang}/publish`, {
                method: "POST",
                body: JSON.stringify({}),
            });
            const ranking = responseData(payload);
            setCurrentRanking(ranking);
            showAlert("Công bố bảng xếp hạng thành công.");
        } catch (error) {
            showAlert(error.message || "Không thể công bố bảng xếp hạng.", true);
            btnPublish.disabled = false;
        }
    }

    tournamentSelect.addEventListener("change", () => {
        selectTournament(tournamentSelect.value);
    });
    rankName.addEventListener("input", updateGenerateState);
    btnGenerate.addEventListener("click", generateStandings);
    btnPublish.addEventListener("click", publishStandings);
    btnRefresh.addEventListener("click", () => loadTournaments(true));

    loadTournaments(false);
})();
