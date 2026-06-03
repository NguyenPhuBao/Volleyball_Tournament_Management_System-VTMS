(function () {
    const root = document.querySelector(".organizer-schedule");

    if (!root) {
        return;
    }

    const scheduleTournamentsApi = root.dataset.scheduleTournamentsApi || "/api/organizer/schedules/tournaments";
    const tournamentApiBase = root.dataset.tournamentApiBase || "/api/organizer/tournaments";

    const tQ = document.getElementById("t_q");
    const tRefresh = document.getElementById("t_refresh");
    const tList = document.getElementById("t_list");

    const tourName = document.getElementById("tour_name");
    const tourSub = document.getElementById("tour_sub");
    const btnGenerateStandard = document.getElementById("btnGenerateStandard");
    const btnAddGroup = document.getElementById("btnAddGroup");
    const btnAddMatch = document.getElementById("btnAddMatch");

    const vRound = document.getElementById("v_round");
    const vRounds = document.getElementById("v_rounds");
    const gQ = document.getElementById("g_q");
    const gTbody = document.getElementById("g_tbody");
    const mGroup = document.getElementById("m_group");
    const mTbody = document.getElementById("m_tbody");
    const pageAlert = document.getElementById("page_alert");

    const groupModal = document.getElementById("groupModal");
    const gmTitle = document.getElementById("gm_title");
    const gmClose = document.getElementById("gm_close");
    const gmCancel = document.getElementById("gm_cancel");
    const gmSave = document.getElementById("gm_save");
    const gmDelete = document.getElementById("gm_delete");
    const gmAlert = document.getElementById("gm_alert");
    const gmRound = document.getElementById("gm_round");
    const gmName = document.getElementById("gm_name");
    const gmStatus = document.getElementById("gm_status");
    const gmStart = document.getElementById("gm_start");
    const gmEnd = document.getElementById("gm_end");
    const gmTeamPicker = document.getElementById("gm_team_picker");
    const gmSelectedTeams = document.getElementById("gm_selected_teams");
    const gmDesc = document.getElementById("gm_desc");

    const matchModal = document.getElementById("matchModal");
    const mmTitle = document.getElementById("mm_title");
    const mmClose = document.getElementById("mm_close");
    const mmCancel = document.getElementById("mm_cancel");
    const mmSave = document.getElementById("mm_save");
    const mmDelete = document.getElementById("mm_delete");
    const mmAlert = document.getElementById("mm_alert");
    const mmGroup = document.getElementById("mm_group");
    const mmRoundSelect = document.getElementById("mm_round_select");
    const mmRound = document.getElementById("mm_round");
    const mmTeam1 = document.getElementById("mm_team1");
    const mmTeam2 = document.getElementById("mm_team2");
    const mmVenue = document.getElementById("mm_venue");
    const mmStatus = document.getElementById("mm_status");
    const mmStart = document.getElementById("mm_start");
    const mmEnd = document.getElementById("mm_end");
    const mmReferees = document.getElementById("mm_referees");
    const mmAddReferee = document.getElementById("mm_add_referee");
    const mmSlot1Source = document.getElementById("mm_slot1_source");
    const mmSlot2Source = document.getElementById("mm_slot2_source");
    const mmSlot1Match = document.getElementById("mm_slot1_match");
    const mmSlot2Match = document.getElementById("mm_slot2_match");
    const mmSlot1Seed = document.getElementById("mm_slot1_seed");
    const mmSlot2Seed = document.getElementById("mm_slot2_seed");

    const GROUP_STATUSES = {
        HOAT_DONG: ["wait", "Chờ phân công"],
        DA_KHOA: ["gray", "Đã khóa"],
        DA_XOA: ["lock", "Đã xóa"],
    };

    const MATCH_STATUSES = {
        CHO_DOI_DOI: ["gray", "Chờ đội"],
        CHO_XEP_LICH: ["gray", "Chờ xếp lịch"],
        DA_XEP_LICH: ["wait", "Đã xếp lịch"],
        CHUA_DIEN_RA: ["gray", "Chưa diễn ra"],
        SAP_DIEN_RA: ["wait", "Sắp diễn ra"],
        TRONG_TAI_TRE_GIAM_SAT: ["bad", "Trọng tài trễ giám sát"],
        DANG_DIEN_RA: ["wait", "Đang diễn ra"],
        TAM_DUNG: ["wait", "Tạm dừng"],
        DA_KET_THUC: ["ok", "Đã kết thúc"],
        DA_HUY: ["lock", "Đã hủy"],
        DA_HUY_KHONG_CO_GIAM_SAT: ["lock", "Hủy do thiếu giám sát"],
    };

    const REFEREE_ASSIGNMENT_STATUSES = {
        CHO_XAC_NHAN: ["wait", "Chờ xác nhận"],
        DA_XAC_NHAN: ["ok", "Đã xác nhận"],
        TU_CHOI: ["lock", "Đã hủy xác nhận"],
        DA_HUY: ["lock", "Đã hủy"],
    };

    const REFEREE_ASSIGNMENT_NOTES = {
        CHO_XAC_NHAN: "Trọng tài chưa xác nhận, chưa tính là đủ điều kiện bắt đầu trận.",
        TU_CHOI: "Trọng tài đã hủy xác nhận, cần phân công lại hoặc chọn trọng tài khác.",
        DA_HUY: "Phân công đã bị hủy, cần phân công lại nếu vai trò này là bắt buộc.",
    };

    const REFEREE_ROLE_LABELS = {
        TRONG_TAI_CHINH: "trọng tài chính",
        TRONG_TAI_PHU: "trọng tài phụ",
        GIAM_SAT: "giám sát",
    };

    let tournaments = [];
    let selectedTournament = null;
    let groups = [];
    let matches = [];
    let teams = [];
    let venues = [];
    let referees = [];
    let rounds = [];
    let selectedRoundKey = "";
    let editingGroupId = null;
    let editingMatchId = null;
    let editingMatchRefereeKey = "";
    let selectedGroupTeamIds = [];

    const mmRefereeNotice = document.createElement("div");
    mmRefereeNotice.className = "referee-assignment-notice hidden";
    mmRefereeNotice.setAttribute("role", "status");
    mmReferees.before(mmRefereeNotice);

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function showMessage(message, isError = false) {
        pageAlert.textContent = message || "";
        pageAlert.classList.toggle("is-error", isError);
    }

    function showModalError(element, message) {
        element.textContent = message || "Yêu cầu không thành công.";
        element.classList.remove("hidden");
    }

    function hideModalError(element) {
        element.textContent = "";
        element.classList.add("hidden");
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

    function urlFrom(path, params = null) {
        const url = new URL(path, window.location.origin);

        if (params) {
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && String(value).trim() !== "") {
                    url.searchParams.set(key, value);
                }
            });
        }

        return url.toString();
    }

    function tournamentUrl(tournamentId, path = "") {
        return urlFrom(`${tournamentApiBase}/${tournamentId}${path}`);
    }

    function badge(status, map) {
        return map[status] || ["gray", status || "-"];
    }

    function matchNeedsRefereeAssignment(match) {
        const isScheduled = Number(match.iddoibong1 || 0) > 0
            && Number(match.iddoibong2 || 0) > 0
            && Number(match.idsandau || 0) > 0
            && !!match.thoigianbatdau;

        if (!isScheduled) {
            return false;
        }

        return Number(match.confirmed_main_referees || 0) < 1 || Number(match.confirmed_supervisors || 0) < 1;
    }

    function matchStatusBadge(match) {
        const status = String(match.trangthai || "");

        if (["CHO_XEP_LICH", "DA_XEP_LICH", "CHUA_DIEN_RA", "SAP_DIEN_RA"].includes(status) && matchNeedsRefereeAssignment(match)) {
            return ["wait", "Chờ phân công trọng tài"];
        }

        return badge(status, MATCH_STATUSES);
    }

    function teamName(teamId) {
        return teams.find((team) => Number(team.iddoibong) === Number(teamId))?.tendoibong || `#${teamId}`;
    }

    function venueName(venueId) {
        return venues.find((venue) => Number(venue.idsandau) === Number(venueId))?.tensandau || `#${venueId}`;
    }

    function formatDisplayDateTime(value) {
        if (!value) {
            return "";
        }

        const normalized = String(value).replace("T", " ");
        return normalized.length >= 16 ? normalized.slice(0, 16) : normalized;
    }

    function toInputDateTime(value) {
        if (!value) {
            return "";
        }

        return String(value).replace(" ", "T").slice(0, 16);
    }

    function toApiDateTime(value) {
        if (!value) {
            return null;
        }

        const normalized = String(value).replace("T", " ");
        return normalized.length === 16 ? `${normalized}:00` : normalized;
    }

    function tournamentDateInputLimit(field, time) {
        const raw = String(selectedTournament?.[field] || "");
        const exact = toInputDateTime(raw);
        if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(exact)) return exact;

        const date = raw.slice(0, 10);
        return date ? `${date}T${time}` : "";
    }

    function applyMatchDateLimits() {
        const min = tournamentDateInputLimit("thoigianbatdau", "00:00");
        const max = tournamentDateInputLimit("thoigianketthuc", "23:59");

        [mmStart, mmEnd].forEach((input) => {
            input.min = min;
            input.max = max;
        });
    }

    function dateTimeMs(value) {
        if (!value) {
            return null;
        }

        const timestamp = new Date(String(value).replace(" ", "T")).getTime();
        return Number.isNaN(timestamp) ? null : timestamp;
    }

    function tournamentBoundaryMs(field, time) {
        const raw = String(selectedTournament?.[field] || "");
        const exact = toInputDateTime(raw);
        if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(exact)) return dateTimeMs(exact);

        const date = raw.slice(0, 10);
        return date ? dateTimeMs(`${date}T${time}`) : null;
    }

    function selectedValues(selectElement) {
        return Array.from(selectElement.selectedOptions)
            .map((option) => Number(option.value))
            .filter((value) => Number.isInteger(value) && value > 0);
    }

    function sameNumberArray(left, right) {
        const a = [...left].map(Number).sort((x, y) => x - y);
        const b = [...right].map(Number).sort((x, y) => x - y);

        return a.length === b.length && a.every((value, index) => value === b[index]);
    }

    function fillSelect(selectElement, items, valueKey, labelKey, placeholder = null, selectedValue = "") {
        let html = "";

        if (placeholder !== null) {
            html += `<option value="">${escapeHtml(placeholder)}</option>`;
        }

        html += items.map((item) => {
            const value = item[valueKey];
            const label = typeof labelKey === "function" ? labelKey(item) : item[labelKey];
            const selected = String(value) === String(selectedValue) ? " selected" : "";
            return `<option value="${escapeHtml(value)}"${selected}>${escapeHtml(label)}</option>`;
        }).join("");

        selectElement.innerHTML = html;
    }

    function roundName(round) {
        return String(round.tenvongdau || round.tenvong || round.name || round.label || "");
    }

    function roundId(round) {
        const value = round.idvongdau || round.id || null;
        return value === null || value === "" ? null : Number(value);
    }

    function roundKey(round) {
        const id = roundId(round);
        return id ? `id:${id}` : `name:${roundName(round)}`;
    }

    function normalizeRound(raw, index) {
        const name = typeof raw === "string" ? raw : roundName(raw);
        const oldType = String(raw?.type || raw?.loai || "").toUpperCase();
        const dbType = String(raw?.loaivongdau || raw?.loaivong || "").toUpperCase();
        const type = dbType || (["KNOCKOUT", "VONG_LOAI"].includes(oldType) ? "VONG_LOAI" : "VONG_DIEM");
        const hasGroups = Number(raw?.total_groups || 0) > 0;

        return {
            ...((typeof raw === "object" && raw !== null) ? raw : {}),
            idvongdau: roundId(raw),
            tenvong: name || `Vòng ${index + 1}`,
            thutu: Number(raw?.thutu || raw?.order || index + 1),
            loaivong: type === "VONG_LOAI" ? "VONG_LOAI" : "VONG_DIEM",
            has_groups: hasGroups,
            trangthai: raw?.trangthai || raw?.status || "",
            so_doi_tham_gia: Number(raw?.so_doi_tham_gia || raw?.total_teams || 0),
            so_doi_vao_vong_sau: raw?.so_doi_vao_vong_sau ?? null,
            so_doi_vao_moi_bang: raw?.so_doi_vao_moi_bang ?? null,
            total_groups: Number(raw?.total_groups || 0),
            total_matches: Number(raw?.total_matches || 0),
        };
    }

    function truthyFlag(value) {
        return value === true || value === 1 || value === "1" || String(value).toUpperCase() === "TRUE";
    }

    function roundsFromCompetitionFormat(format) {
        const source = Array.isArray(format?.rounds)
            ? format.rounds
            : (Array.isArray(format?.vong_dau) ? format.vong_dau : []);

        const configuredRounds = source.map(normalizeRound).filter((round) => round.tenvong);

        if (configuredRounds.length > 0) {
            return configuredRounds;
        }

        const roundsFromFlags = [];

        if (truthyFlag(format?.co_vong_diem)) {
            roundsFromFlags.push(normalizeRound({
                tenvongdau: "Vòng điểm",
                loaivongdau: "VONG_DIEM",
                trangthai: "THEO_THE_THUC",
                is_virtual: true,
            }, roundsFromFlags.length));
        }

        if (truthyFlag(format?.co_vong_loai)) {
            roundsFromFlags.push(normalizeRound({
                tenvongdau: "Vòng loại trực tiếp",
                loaivongdau: "VONG_LOAI",
                trangthai: "THEO_THE_THUC",
                is_virtual: true,
            }, roundsFromFlags.length));
        }

        if (roundsFromFlags.length > 0) {
            return roundsFromFlags;
        }

        const formatName = String(format?.tenthethuc || format?.name || "").toLowerCase();

        if (formatName.includes("loại")) {
            return [normalizeRound({
                tenvongdau: "Vòng loại trực tiếp",
                loaivongdau: "VONG_LOAI",
                trangthai: "THEO_THE_THUC",
                is_virtual: true,
            }, 0)];
        }

        if (formatName.includes("điểm") || formatName.includes("diem")) {
            return [normalizeRound({
                tenvongdau: "Vòng điểm",
                loaivongdau: "VONG_DIEM",
                trangthai: "THEO_THE_THUC",
                is_virtual: true,
            }, 0)];
        }

        return [];
    }

    function roundFromKey(key) {
        if (!key) {
            return null;
        }

        return rounds.find((round) => roundKey(round) === key) || null;
    }

    function roundKeyFromEntity(entity) {
        if (!entity) {
            return "";
        }

        if (entity.idvongdau) {
            const byId = rounds.find((round) => Number(roundId(round)) === Number(entity.idvongdau));

            if (byId) {
                return roundKey(byId);
            }
        }

        const name = String(entity.tenvong || entity.vongdau || "");
        const byName = rounds.find((round) => round.tenvong === name);

        return byName ? roundKey(byName) : "";
    }

    function defaultRoundKey(preferredRoundType = "") {
        if (rounds.length === 0) {
            return "";
        }

        const preferred = preferredRoundType
            ? rounds.find((round) => round.loaivong === preferredRoundType)
            : rounds[0];

        return roundKey(preferred || rounds[0]);
    }

    function syncMatchRoundInput() {
        const round = roundFromKey(mmRoundSelect.value);
        mmRound.value = round?.tenvong || "";
    }

    function canMutateSchedule() {
        return selectedTournament?.trangthaidangky === "DA_DONG";
    }

    function canUsePersistedRound(round) {
        return !!round && !!roundId(round);
    }

    function canAddGroupForSelectedRound() {
        const round = roundFromKey(selectedRoundKey);
        return canMutateSchedule() && !!round && round.loaivong === "VONG_DIEM";
    }

    function canGenerateForSelectedRound() {
        const round = roundFromKey(selectedRoundKey);
        return canMutateSchedule() && !!round && ["VONG_DIEM", "VONG_LOAI"].includes(round.loaivong);
    }

    async function loadTournamentDetails(tournamentId) {
        try {
            const payload = await requestJson(tournamentUrl(tournamentId));
            return payload.data || null;
        } catch (_) {
            return null;
        }
    }

    function refreshGroupTeamPicker() {
        const availableTeams = teams.filter((team) => !selectedGroupTeamIds.includes(Number(team.iddoibong)));

        fillSelect(gmTeamPicker, availableTeams, "iddoibong", "tendoibong", "Chọn đội tham gia bảng", "");
    }

    function renderSelectedGroupTeams() {
        if (selectedGroupTeamIds.length === 0) {
            gmSelectedTeams.innerHTML = '<span class="field-hint">Chưa chọn đội nào.</span>';
            refreshGroupTeamPicker();
            return;
        }

        gmSelectedTeams.innerHTML = selectedGroupTeamIds.map((teamId) => `
            <span class="selected-team-chip">
                ${escapeHtml(teamName(teamId))}
                <button type="button" data-action="remove-group-team" data-id="${escapeHtml(teamId)}" aria-label="Loại đội">×</button>
            </span>
        `).join("");
        refreshGroupTeamPicker();
    }

    function setSelectedGroupTeams(teamIds = []) {
        selectedGroupTeamIds = [...new Set(teamIds.map(Number).filter((value) => value > 0))];
        renderSelectedGroupTeams();
    }

    function refreshGroupSelects() {
        const activeGroups = groups.filter((group) => group.trangthai !== "DA_XOA");

        fillSelect(
            mGroup,
            activeGroups,
            "idbangdau",
            "tenbang",
            "Tất cả, gồm trận ngoài bảng",
            mGroup.value
        );
        fillSelect(mmGroup, activeGroups, "idbangdau", "tenbang", "Không thuộc bảng", mmGroup.value);
    }

    function refreshMatchSelects() {
        fillSelect(mmTeam1, teams, "iddoibong", "tendoibong", "Chọn đội", mmTeam1.value);
        fillSelect(mmTeam2, teams, "iddoibong", "tendoibong", "Chọn đội", mmTeam2.value);
        fillSelect(mmVenue, venues, "idsandau", "tensandau", "Chưa xếp sân", mmVenue.value);
        fillMatchSourceSelects();
        fillRoundSelects();
    }

    function fillMatchSourceSelects() {
        const sourceMatches = matches
            .filter((match) => Number(match.idtrandau) !== Number(editingMatchId || 0))
            .map((match) => ({
                id: match.idtrandau,
                label: `${match.ma_tran || `#${match.idtrandau}`} - ${match.tenvong || ""}`.trim(),
            }));

        fillSelect(mmSlot1Match, sourceMatches, "id", "label", "Chọn trận nguồn", mmSlot1Match.value);
        fillSelect(mmSlot2Match, sourceMatches, "id", "label", "Chọn trận nguồn", mmSlot2Match.value);
    }

    function fillRoundSelects() {
        const previousFilter = vRound.value;
        const previousGroup = gmRound.value;
        const previousMatch = mmRoundSelect.value;
        const options = rounds.map((round) => ({
            key: roundKey(round),
            label: `${round.thutu}. ${round.tenvong}`,
        }));

        fillSelect(vRound, options, "key", "label", "Tất cả vòng đấu", previousFilter);
        fillSelect(gmRound, options, "key", "label", "Chọn vòng đấu", previousGroup);
        fillSelect(mmRoundSelect, options, "key", "label", "Chọn vòng đấu", previousMatch);
    }

    function setSelectedTournamentFromList(id) {
        const found = tournaments.find((item) => Number(item.idgiaidau) === Number(id));

        if (found) {
            selectedTournament = found;
        }
    }

    function clearSelectedTournament() {
        selectedTournament = null;
        groups = [];
        matches = [];
        teams = [];
        venues = [];
        referees = [];
        rounds = [];
        selectedRoundKey = "";
        tourName.textContent = "Chưa chọn giải đấu";
        tourSub.textContent = "Chọn một giải đấu ở cột bên trái.";
        btnGenerateStandard.disabled = true;
        btnAddGroup.disabled = true;
        btnAddMatch.disabled = true;
        refreshGroupSelects();
        refreshMatchSelects();
        renderRounds();
        renderGroups();
        renderMatches();
    }

    async function loadTournaments() {
        const previousTournamentId = selectedTournament?.idgiaidau || null;
        showMessage("Đang tải danh sách giải đấu...");

        try {
            const payload = await requestJson(urlFrom(scheduleTournamentsApi, { q: tQ.value.trim() }));
            tournaments = Array.isArray(payload.data) ? payload.data : [];
            renderTournaments();

            const nextTournament = tournaments.find((item) => Number(item.idgiaidau) === Number(previousTournamentId))
                || tournaments[0]
                || null;

            if (nextTournament) {
                await selectTournament(nextTournament.idgiaidau);
                return;
            }

            clearSelectedTournament();
            showMessage("");
        } catch (error) {
            tournaments = [];
            clearSelectedTournament();
            tList.innerHTML = '<li class="empty">Không thể tải danh sách giải đấu.</li>';
            showMessage(error.message || "Không thể tải danh sách giải đấu.", true);
        }
    }

    function renderTournaments() {
        if (tournaments.length === 0) {
            tList.innerHTML = '<li class="empty">Không có giải đấu hợp lệ.</li>';
            return;
        }

        tList.innerHTML = tournaments.map((tournament) => {
            const active = selectedTournament && Number(tournament.idgiaidau) === Number(selectedTournament.idgiaidau);
            return `
                <li class="item${active ? " active" : ""}" data-action="select-tournament" data-id="${escapeHtml(tournament.idgiaidau)}">
                    <div class="title">${escapeHtml(tournament.tengiaidau)}</div>
                    <div class="meta">${escapeHtml(tournament.diadiem || "")}</div>
                    <div class="meta">${escapeHtml(tournament.total_teams || 0)} đội - ${escapeHtml(tournament.total_groups || 0)} bảng - ${escapeHtml(tournament.total_matches || 0)} trận</div>
                </li>
            `;
        }).join("");
    }

    async function selectTournament(id) {
        setSelectedTournamentFromList(id);
        btnGenerateStandard.disabled = true;
        btnAddGroup.disabled = true;
        btnAddMatch.disabled = true;
        showMessage("Đang tải lịch thi đấu...");

        try {
            const payload = await requestJson(tournamentUrl(id, "/schedule"));
            const data = payload.data || {};
            const tournamentDetails = await loadTournamentDetails(id);

            selectedTournament = {
                ...(selectedTournament || {}),
                ...(data.tournament || {}),
                ...(tournamentDetails || {}),
            };
            teams = Array.isArray(data.teams) ? data.teams : [];
            venues = Array.isArray(data.venues) ? data.venues : [];
            referees = Array.isArray(data.referees) ? data.referees : [];
            groups = Array.isArray(data.groups) ? data.groups : [];
            matches = Array.isArray(data.matches) ? data.matches : [];
            rounds = Array.isArray(data.rounds) && data.rounds.length > 0
                ? data.rounds.map(normalizeRound)
                : roundsFromCompetitionFormat(selectedTournament?.competition_format || selectedTournament?.thethuc);
            selectedRoundKey = selectedRoundKey && rounds.some((round) => roundKey(round) === selectedRoundKey)
                ? selectedRoundKey
                : defaultRoundKey("");

            tourName.textContent = selectedTournament?.tengiaidau || "Chưa chọn giải đấu";
            tourSub.textContent = [
                selectedTournament?.diadiem || "",
                `Trạng thái: ${selectedTournament?.trangthai || ""}`,
                `Đăng ký: ${selectedTournament?.trangthaidangky || ""}`,
                `${teams.length} đội`,
            ].filter(Boolean).join(" - ");

            btnAddGroup.disabled = !canAddGroupForSelectedRound();
            btnAddMatch.disabled = !canMutateSchedule();
            btnGenerateStandard.disabled = !canGenerateForSelectedRound();
            refreshGroupSelects();
            refreshMatchSelects();
            renderTournaments();
            renderRounds();
            renderGroups();
            renderMatches();
            showMessage("");
        } catch (error) {
            showMessage(error.message || "Không thể tải lịch thi đấu.", true);
            gTbody.innerHTML = '<tr><td colspan="4" class="empty">Không thể tải bảng đấu.</td></tr>';
            mTbody.innerHTML = '<tr><td colspan="10" class="empty">Không thể tải trận đấu.</td></tr>';
        }
    }

    function groupRows() {
        const keyword = gQ.value.trim().toLowerCase();

        return groups.filter((group) => {
            const haystack = `${group.tenbang || ""} ${group.mota || ""}`.toLowerCase();
            const matchesKeyword = !keyword || haystack.includes(keyword);
            const matchesRound = !selectedRoundKey || entityMatchesRound(group);
            return matchesKeyword && matchesRound;
        });
    }

    function entityMatchesRound(entity) {
        const round = rounds.find((item) => roundKey(item) === selectedRoundKey);

        if (!round) {
            return true;
        }

        const id = roundId(round);

        if (id && Number(entity.idvongdau || 0) === id) {
            return true;
        }

        return String(entity.tenvong || entity.vongdau || "") === round.tenvong;
    }

    function renderRounds() {
        if (!selectedTournament) {
            vRounds.innerHTML = '<div class="empty">Chưa chọn giải đấu.</div>';
            return;
        }

        if (rounds.length === 0) {
            vRounds.innerHTML = '<div class="empty">Giải đấu chưa có cấu trúc vòng đấu.</div>';
            return;
        }

        vRounds.innerHTML = rounds.map((round) => {
            const active = selectedRoundKey === roundKey(round);
            const type = round.loaivong === "VONG_LOAI" ? "Loại trực tiếp" : "Tính điểm";
            const typeText = round.total_groups > 0 ? `${type} - ${round.total_groups} bảng thủ công` : type;
            const metrics = round.is_virtual
                ? "Theo thể thức đã lưu"
                : [
                    `${round.so_doi_tham_gia || 0} đội`,
                    `${round.total_matches || 0} trận`,
                ].join(" - ");
            return `
                <button class="round-card${active ? " is-active" : ""}" type="button" data-action="select-round" data-key="${escapeHtml(roundKey(round))}">
                    <div class="round-title">${escapeHtml(round.thutu)}. ${escapeHtml(round.tenvong)}</div>
                    <div class="round-meta">${escapeHtml(typeText)}</div>
                    <div class="round-meta">${escapeHtml(metrics)}</div>
                </button>
            `;
        }).join("");
    }

    function renderGroups() {
        const data = groupRows();

        if (!selectedTournament) {
            gTbody.innerHTML = '<tr><td colspan="4" class="empty">Chưa chọn giải đấu.</td></tr>';
            return;
        }

        if (data.length === 0) {
            gTbody.innerHTML = '<tr><td colspan="4" class="empty">Chưa có bảng đấu phù hợp.</td></tr>';
            return;
        }

        gTbody.innerHTML = data.map((group) => {
            const [className, label] = badge(group.trangthai, GROUP_STATUSES);

            return `
                <tr>
                    <td>${escapeHtml(group.idbangdau)}</td>
                    <td>${escapeHtml(group.tenbang)}</td>
                    <td><span class="badge ${className}">${escapeHtml(label)}</span></td>
                    <td>
                        <button class="btn" type="button" data-action="edit-group" data-id="${escapeHtml(group.idbangdau)}">Sửa</button>
                    </td>
                </tr>
            `;
        }).join("");
    }

    function filteredMatches() {
        const groupId = mGroup.value ? Number(mGroup.value) : null;
        let data = matches;

        if (!groupId) {
            data = matches;
        } else {
            data = matches.filter((match) => Number(match.idbangdau) === groupId);
        }

        return selectedRoundKey ? data.filter(entityMatchesRound) : data;
    }

    function renderMatches() {
        const data = filteredMatches();

        if (!selectedTournament) {
            mTbody.innerHTML = '<tr><td colspan="10" class="empty">Chưa chọn giải đấu.</td></tr>';
            return;
        }

        if (data.length === 0) {
            mTbody.innerHTML = '<tr><td colspan="10" class="empty">Chưa có trận đấu phù hợp.</td></tr>';
            return;
        }

        mTbody.innerHTML = data.map((match) => {
            const [className, label] = matchStatusBadge(match);

            return `
                <tr>
                    <td>${escapeHtml(match.idtrandau)}</td>
                    <td>${escapeHtml(match.doi1 || (match.iddoibong1 ? teamName(match.iddoibong1) : "Chờ xác định"))}</td>
                    <td>${escapeHtml(match.doi2 || (match.iddoibong2 ? teamName(match.iddoibong2) : "Chờ xác định"))}</td>
                    <td>${escapeHtml(match.tensandau || (match.idsandau ? venueName(match.idsandau) : "Chưa xếp sân"))}</td>
                    <td>${escapeHtml(formatDisplayDateTime(match.thoigianbatdau) || "Chưa xếp lịch")}</td>
                    <td>${escapeHtml(formatDisplayDateTime(match.thoigianketthuc) || "")}</td>
                    <td>${escapeHtml(match.tenvong || "")}</td>
                    <td>${escapeHtml(match.tenbang || "Ngoài bảng")}</td>
                    <td><span class="badge ${className}">${escapeHtml(label)}</span></td>
                    <td>
                        <button class="btn" type="button" data-action="edit-match" data-id="${escapeHtml(match.idtrandau)}">Sửa</button>
                    </td>
                </tr>
            `;
        }).join("");
    }

    function currentGroup() {
        return groups.find((group) => Number(group.idbangdau) === Number(editingGroupId)) || null;
    }

    function currentMatch() {
        return matches.find((match) => Number(match.idtrandau) === Number(editingMatchId)) || null;
    }

    function openCreateGroup() {
        if (!selectedTournament) {
            return;
        }

        editingGroupId = null;
        hideModalError(gmAlert);
        gmTitle.textContent = "Thêm bảng đấu";
        gmName.value = "";
        gmStatus.value = "HOAT_DONG";
        gmStart.value = String(selectedTournament?.thoigianbatdau || "").slice(0, 10);
        gmEnd.value = "";
        gmDesc.value = "";
        gmRound.value = selectedRoundKey || defaultRoundKey("VONG_DIEM");
        setSelectedGroupTeams([]);
        gmDelete.disabled = true;
        groupModal.classList.remove("hidden");
    }

    function openEditGroup(groupId) {
        const group = groups.find((item) => Number(item.idbangdau) === Number(groupId));

        if (!group) {
            return;
        }

        editingGroupId = Number(groupId);
        hideModalError(gmAlert);
        gmTitle.textContent = "Sửa bảng đấu";
        gmName.value = group.tenbang || "";
        gmStatus.value = group.trangthai || "HOAT_DONG";
        gmStart.value = String(group.thoigianbatdau || selectedTournament?.thoigianbatdau || "").slice(0, 10);
        gmEnd.value = group.thoigianketthuc || "";
        gmDesc.value = group.mota || "";
        gmRound.value = roundKeyFromEntity(group) || defaultRoundKey("VONG_DIEM");
        setSelectedGroupTeams((group.teams || []).map((team) => Number(team.iddoibong)));
        gmDelete.disabled = Number(group.total_matches || 0) > 0 || group.trangthai === "DA_XOA";
        groupModal.classList.remove("hidden");
    }

    function closeGroupModal() {
        groupModal.classList.add("hidden");
        editingGroupId = null;
    }

    function groupPayload() {
        const round = roundFromKey(gmRound.value);
        return {
            idvongdau: roundId(round),
            vongdau: round?.tenvong || null,
            tenbang: gmName.value.trim(),
            trangthai: gmStatus.value,
            thoigianketthuc: gmEnd.value || null,
            mota: gmDesc.value.trim() || null,
            team_ids: selectedGroupTeamIds,
        };
    }

    function validateGroup(payload) {
        if (!payload.tenbang) {
            return "Vui lòng nhập tên bảng đấu.";
        }

        if (rounds.length > 0 && !roundFromKey(gmRound.value)) {
            return "Vui lòng chọn vòng đấu cho bảng.";
        }

        if (payload.thoigianketthuc) {
            const groupStartDate = String(gmStart.value || selectedTournament?.thoigianbatdau || "").slice(0, 10);
            const tournamentStartDate = String(selectedTournament?.thoigianbatdau || "").slice(0, 10);
            const tournamentEndDate = String(selectedTournament?.thoigianketthuc || "").slice(0, 10);

            if (groupStartDate && payload.thoigianketthuc <= groupStartDate) {
                return "Thời gian kết thúc bảng đấu phải sau thời gian bắt đầu bảng đấu.";
            }

            if (tournamentStartDate && payload.thoigianketthuc < tournamentStartDate) {
                return "Thời gian kết thúc bảng đấu không được trước ngày bắt đầu giải đấu.";
            }

            if (tournamentEndDate && payload.thoigianketthuc > tournamentEndDate) {
                return "Thời gian kết thúc bảng đấu không được sau ngày kết thúc giải đấu.";
            }
        }

        return null;
    }

    function changedGroupPayload(payload) {
        const group = currentGroup();

        if (!group) {
            return payload;
        }

        const changes = {};

        if (payload.tenbang !== String(group.tenbang || "")) {
            changes.tenbang = payload.tenbang;
        }

        if (payload.idvongdau && Number(payload.idvongdau) !== Number(group.idvongdau || 0)) {
            changes.idvongdau = payload.idvongdau;
        }

        if (payload.trangthai !== String(group.trangthai || "")) {
            changes.trangthai = payload.trangthai;
        }

        if ((payload.mota || null) !== (group.mota || null)) {
            changes.mota = payload.mota;
        }

        if ((payload.thoigianketthuc || null) !== (group.thoigianketthuc || null)) {
            changes.thoigianketthuc = payload.thoigianketthuc;
        }

        const currentTeamIds = (group.teams || []).map((team) => Number(team.iddoibong));

        if (!sameNumberArray(payload.team_ids, currentTeamIds)) {
            changes.team_ids = payload.team_ids;
        }

        return changes;
    }

    async function saveGroup() {
        hideModalError(gmAlert);
        const payload = groupPayload();
        const validationError = validateGroup(payload);

        if (validationError) {
            showModalError(gmAlert, validationError);
            return;
        }

        gmSave.disabled = true;

        try {
            if (editingGroupId) {
                const changes = changedGroupPayload(payload);

                if (Object.keys(changes).length === 0) {
                    showModalError(gmAlert, "Không có dữ liệu thay đổi.");
                    return;
                }

                await requestJson(tournamentUrl(selectedTournament.idgiaidau, `/groups/${editingGroupId}`), {
                    method: "PATCH",
                    body: JSON.stringify(changes),
                });
                closeGroupModal();
                await selectTournament(selectedTournament.idgiaidau);
                showMessage("Cập nhật bảng đấu thành công.");
                return;
            }

            await requestJson(tournamentUrl(selectedTournament.idgiaidau, "/groups"), {
                method: "POST",
                body: JSON.stringify(payload),
            });
            closeGroupModal();
            await selectTournament(selectedTournament.idgiaidau);
            showMessage("Thêm bảng đấu thành công.");
        } catch (error) {
            showModalError(gmAlert, error.message || "Không thể lưu bảng đấu.");
        } finally {
            gmSave.disabled = false;
        }
    }

    async function deleteGroup() {
        if (!editingGroupId || !window.confirm("Xóa bảng đấu? Hệ thống sẽ chuyển trạng thái bảng sang DA_XOA.")) {
            return;
        }

        gmDelete.disabled = true;

        try {
            await requestJson(tournamentUrl(selectedTournament.idgiaidau, `/groups/${editingGroupId}/delete`), {
                method: "POST",
                body: JSON.stringify({ lydo: "Xoa bang dau tu giao dien quan ly lich thi dau" }),
            });
            closeGroupModal();
            await selectTournament(selectedTournament.idgiaidau);
            showMessage("Xóa bảng đấu thành công.");
        } catch (error) {
            showModalError(gmAlert, error.message || "Không thể xóa bảng đấu.");
            gmDelete.disabled = false;
        }
    }

    function openCreateMatch() {
        if (!selectedTournament) {
            return;
        }

        editingMatchId = null;
        editingMatchRefereeKey = "";
        hideModalError(mmAlert);
        mmTitle.textContent = "Thêm trận đấu";
        applyMatchDateLimits();
        refreshGroupSelects();
        refreshMatchSelects();
        mmGroup.value = mGroup.value || "";
        mmRoundSelect.value = selectedRoundKey || defaultRoundKey("");
        syncMatchRoundInput();
        mmStatus.value = "DA_XEP_LICH";
        mmStart.value = "";
        mmEnd.value = "";
        mmSlot1Source.value = "TEAM";
        mmSlot2Source.value = "TEAM";
        mmSlot1Match.value = "";
        mmSlot2Match.value = "";
        mmSlot1Seed.value = "";
        mmSlot2Seed.value = "";

        if (teams.length > 0) {
            mmTeam1.value = String(teams[0].iddoibong);
        }

        if (teams.length > 1) {
            mmTeam2.value = String(teams[1].iddoibong);
        }

        if (venues.length > 0) {
            mmVenue.value = String(venues[0].idsandau);
        }

        renderRefereeRows([]);
        syncSlotInputs();
        mmDelete.disabled = true;
        matchModal.classList.remove("hidden");
    }

    async function openEditMatch(matchId) {
        const summaryMatch = matches.find((item) => Number(item.idtrandau) === Number(matchId));

        if (!summaryMatch) {
            return;
        }

        editingMatchId = Number(matchId);
        hideModalError(mmAlert);
        mmTitle.textContent = "Sửa trận đấu";
        applyMatchDateLimits();
        refreshGroupSelects();
        refreshMatchSelects();
        matchModal.classList.remove("hidden");

        try {
            const payload = await requestJson(tournamentUrl(selectedTournament.idgiaidau, `/matches/${matchId}`));
            const match = payload.data || summaryMatch;
            const slots = Array.isArray(match.slots) ? match.slots : [];
            const slot1 = slots.find((slot) => Number(slot.slot_so) === 1) || {};
            const slot2 = slots.find((slot) => Number(slot.slot_so) === 2) || {};

            mmGroup.value = match.idbangdau || "";
            mmRoundSelect.value = roundKeyFromEntity(match) || defaultRoundKey("");
            syncMatchRoundInput();
            mmTeam1.value = match.iddoibong1 === null ? "" : String(match.iddoibong1);
            mmTeam2.value = match.iddoibong2 === null ? "" : String(match.iddoibong2);
            mmVenue.value = match.idsandau === null ? "" : String(match.idsandau);
            mmStatus.value = match.trangthai || "CHO_DOI_DOI";
            mmStart.value = toInputDateTime(match.thoigianbatdau);
            mmEnd.value = toInputDateTime(match.thoigianketthuc);
            fillSlotControls(1, slot1);
            fillSlotControls(2, slot2);
            const refereeAssignments = Array.isArray(match.referee_assignments) ? match.referee_assignments : [];
            editingMatchRefereeKey = refereeAssignmentsKey(refereeAssignments);
            renderRefereeRows(refereeAssignments);
            syncSlotInputs();
            mmDelete.disabled = !["CHO_DOI_DOI", "CHO_XEP_LICH", "DA_XEP_LICH", "CHUA_DIEN_RA", "SAP_DIEN_RA"].includes(String(match.trangthai));
        } catch (error) {
            showModalError(mmAlert, error.message || "Không thể tải chi tiết trận đấu.");
        }
    }

    function closeMatchModal() {
        matchModal.classList.add("hidden");
        editingMatchId = null;
        editingMatchRefereeKey = "";
    }

    function matchPayload() {
        syncMatchRoundInput();
        const round = roundFromKey(mmRoundSelect.value);
        return {
            idvongdau: roundId(round),
            idbangdau: mmGroup.value ? Number(mmGroup.value) : null,
            iddoibong1: mmSlot1Source.value === "TEAM" && mmTeam1.value ? Number(mmTeam1.value) : null,
            iddoibong2: mmSlot2Source.value === "TEAM" && mmTeam2.value ? Number(mmTeam2.value) : null,
            idsandau: mmVenue.value ? Number(mmVenue.value) : null,
            trangthai: mmStatus.value,
            thoigianbatdau: toApiDateTime(mmStart.value),
            thoigianketthuc: toApiDateTime(mmEnd.value),
            slots: [slotPayload(1), slotPayload(2)],
            referee_assignments: refereeAssignmentsPayload(),
        };
    }

    function validateMatch(payload) {
        if (!payload.idvongdau) {
            return "Vui lòng chọn vòng đấu.";
        }

        if (payload.slots.some((slot) => slot.source_type === "TEAM" && !slot.iddoibong)) {
            return "Nguồn đội cụ thể phải chọn đội bóng.";
        }

        if (payload.slots.some((slot) => ["WINNER", "LOSER"].includes(slot.source_type) && !slot.source_match_id)) {
            return "Nguồn đội thắng/thua phải chọn trận nguồn.";
        }

        if (payload.slots.some((slot) => slot.source_type === "SEED" && !slot.source_seed_no)) {
            return "Nguồn hạt giống phải nhập số hạt giống.";
        }

        if (payload.iddoibong1 && payload.iddoibong2 && payload.iddoibong1 === payload.iddoibong2) {
            return "Đội 1 phải khác đội 2.";
        }

        if (payload.trangthai !== "CHO_DOI_DOI" && (!payload.idsandau || !payload.thoigianbatdau)) {
            return "Trận đã sẵn sàng cần có sân đấu và thời gian bắt đầu.";
        }

        if (payload.thoigianketthuc && !payload.thoigianbatdau) {
            return "Vui lòng nhập thời gian bắt đầu trước khi nhập thời gian kết thúc.";
        }

        if (payload.thoigianketthuc && new Date(payload.thoigianketthuc) <= new Date(payload.thoigianbatdau)) {
            return "Thời gian kết thúc phải lớn hơn thời gian bắt đầu.";
        }

        const tournamentStart = tournamentBoundaryMs("thoigianbatdau", "00:00:00");
        const tournamentEnd = tournamentBoundaryMs("thoigianketthuc", "23:59:59");
        const matchStart = dateTimeMs(payload.thoigianbatdau);
        const matchEnd = dateTimeMs(payload.thoigianketthuc);

        if (matchStart !== null && tournamentStart !== null && matchStart < tournamentStart) {
            return "Thời gian bắt đầu trận đấu không được trước ngày bắt đầu giải đấu.";
        }

        if (matchStart !== null && tournamentEnd !== null && matchStart > tournamentEnd) {
            return "Thời gian bắt đầu trận đấu không được sau ngày kết thúc giải đấu.";
        }

        if (matchEnd !== null && tournamentStart !== null && matchEnd < tournamentStart) {
            return "Thời gian kết thúc trận đấu không được trước ngày bắt đầu giải đấu.";
        }

        if (matchEnd !== null && tournamentEnd !== null && matchEnd > tournamentEnd) {
            return "Thời gian kết thúc trận đấu không được sau ngày kết thúc giải đấu.";
        }

        if (payload.trangthai === "DANG_DIEN_RA") {
            const readiness = refereeReadiness();

            if (readiness.missing.length > 0) {
                return `Trận muốn bắt đầu cần ${readiness.missing.map((role) => `${role} đã xác nhận`).join(" và ")}.`;
            }
        }

        return null;
    }

    function changedMatchPayload(payload) {
        const match = currentMatch();

        if (!match) {
            return payload;
        }

        const changes = {};
        const current = {
            idbangdau: match.idbangdau === null ? null : Number(match.idbangdau),
            idvongdau: match.idvongdau === null || match.idvongdau === undefined ? null : Number(match.idvongdau),
            iddoibong1: Number(match.iddoibong1),
            iddoibong2: Number(match.iddoibong2),
            idsandau: Number(match.idsandau),
            trangthai: String(match.trangthai || ""),
            thoigianbatdau: toApiDateTime(toInputDateTime(match.thoigianbatdau)),
            thoigianketthuc: toApiDateTime(toInputDateTime(match.thoigianketthuc)),
        };

        Object.entries(payload).forEach(([key, value]) => {
            if (key === "referee_assignments" && refereeAssignmentsKey(value) === editingMatchRefereeKey) {
                return;
            }

            if (value !== current[key]) {
                changes[key] = value;
            }
        });

        return changes;
    }

    async function saveMatch() {
        hideModalError(mmAlert);
        const payload = matchPayload();
        const validationError = validateMatch(payload);

        if (validationError) {
            showModalError(mmAlert, validationError);
            return;
        }

        mmSave.disabled = true;

        try {
            if (editingMatchId) {
                const changes = changedMatchPayload(payload);

                if (Object.keys(changes).length === 0) {
                    showModalError(mmAlert, "Không có dữ liệu thay đổi.");
                    return;
                }

                await requestJson(tournamentUrl(selectedTournament.idgiaidau, `/matches/${editingMatchId}`), {
                    method: "PATCH",
                    body: JSON.stringify(changes),
                });
                closeMatchModal();
                await selectTournament(selectedTournament.idgiaidau);
                showMessage("Cập nhật trận đấu thành công.");
                return;
            }

            await requestJson(tournamentUrl(selectedTournament.idgiaidau, "/matches"), {
                method: "POST",
                body: JSON.stringify(payload),
            });
            closeMatchModal();
            await selectTournament(selectedTournament.idgiaidau);
            showMessage("Thêm trận đấu thành công.");
        } catch (error) {
            showModalError(mmAlert, error.message || "Không thể lưu trận đấu.");
        } finally {
            mmSave.disabled = false;
        }
    }

    async function generateStandardSchedule() {
        if (!selectedTournament) {
            return;
        }

        const selectedRound = roundFromKey(selectedRoundKey);

        if (!selectedRound) {
            showMessage("Hãy chọn một vòng đấu để tạo trận tự động.", true);
            return;
        }

        if (!window.confirm(`Tạo các cặp trận tự động cho ${selectedRound.tenvong}?`)) {
            return;
        }

        btnGenerateStandard.disabled = true;

        try {
            const payload = {
                idvongdau: roundId(selectedRound),
                loaivongdau: selectedRound.loaivong,
            };

            await requestJson(tournamentUrl(selectedTournament.idgiaidau, "/schedule/generate-standard"), {
                method: "POST",
                body: JSON.stringify(payload),
            });
            await selectTournament(selectedTournament.idgiaidau);
            showMessage("Tạo trận tự động thành công theo cấu hình vòng đấu. Hãy tiếp tục xếp thời gian, sân và trọng tài.");
        } catch (error) {
            showMessage(error.message || "Không thể tạo trận tự động.", true);
        } finally {
            btnGenerateStandard.disabled = !canGenerateForSelectedRound();
        }
    }

    async function deleteMatch() {
        if (!editingMatchId || !window.confirm("Xóa trận đấu? Hệ thống sẽ chuyển trạng thái trận sang DA_HUY.")) {
            return;
        }

        mmDelete.disabled = true;

        try {
            await requestJson(tournamentUrl(selectedTournament.idgiaidau, `/matches/${editingMatchId}/delete`), {
                method: "POST",
                body: JSON.stringify({ lydo: "Xoa tran dau tu giao dien quan ly lich thi dau" }),
            });
            closeMatchModal();
            await selectTournament(selectedTournament.idgiaidau);
            showMessage("Xóa trận đấu thành công.");
        } catch (error) {
            showModalError(mmAlert, error.message || "Không thể xóa trận đấu.");
            mmDelete.disabled = false;
        }
    }

    tRefresh.addEventListener("click", loadTournaments);
    tQ.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            loadTournaments();
        }
    });
    vRound.addEventListener("change", () => {
        selectedRoundKey = vRound.value;
        btnAddGroup.disabled = !canAddGroupForSelectedRound();
        btnGenerateStandard.disabled = !canGenerateForSelectedRound();
        renderRounds();
        renderGroups();
        renderMatches();
    });
    gQ.addEventListener("input", renderGroups);
    mGroup.addEventListener("change", renderMatches);
    mmRoundSelect.addEventListener("change", syncMatchRoundInput);
    [mmSlot1Source, mmSlot2Source].forEach((element) => element.addEventListener("change", syncSlotInputs));
    btnGenerateStandard.addEventListener("click", generateStandardSchedule);
    btnAddGroup.addEventListener("click", openCreateGroup);
    btnAddMatch.addEventListener("click", openCreateMatch);

    gmClose.addEventListener("click", closeGroupModal);
    gmCancel.addEventListener("click", closeGroupModal);
    gmSave.addEventListener("click", saveGroup);
    gmDelete.addEventListener("click", deleteGroup);
    gmTeamPicker.addEventListener("change", () => {
        const teamId = Number(gmTeamPicker.value || 0);

        if (teamId > 0 && !selectedGroupTeamIds.includes(teamId)) {
            selectedGroupTeamIds.push(teamId);
            renderSelectedGroupTeams();
        }
    });
    gmSelectedTeams.addEventListener("click", (event) => {
        const button = event.target.closest("[data-action='remove-group-team']");

        if (!button) {
            return;
        }

        selectedGroupTeamIds = selectedGroupTeamIds.filter((teamId) => teamId !== Number(button.dataset.id));
        renderSelectedGroupTeams();
    });

    mmClose.addEventListener("click", closeMatchModal);
    mmCancel.addEventListener("click", closeMatchModal);
    mmSave.addEventListener("click", saveMatch);
    mmDelete.addEventListener("click", deleteMatch);
    mmAddReferee.addEventListener("click", () => addRefereeRow());

    mmReferees.addEventListener("click", (event) => {
        const button = event.target.closest("[data-action='remove-referee']");

        if (button) {
            button.closest(".referee-row")?.remove();
            updateRefereeNotice();
        }
    });

    tList.addEventListener("click", (event) => {
        const item = event.target.closest("[data-action='select-tournament']");

        if (item) {
            selectTournament(item.dataset.id);
        }
    });

    vRounds.addEventListener("click", (event) => {
        const item = event.target.closest("[data-action='select-round']");

        if (!item) {
            return;
        }

        selectedRoundKey = selectedRoundKey === item.dataset.key ? "" : item.dataset.key;
        vRound.value = selectedRoundKey;
        btnAddGroup.disabled = !canAddGroupForSelectedRound();
        btnGenerateStandard.disabled = !canGenerateForSelectedRound();
        renderRounds();
        renderGroups();
        renderMatches();
    });

    gTbody.addEventListener("click", (event) => {
        const button = event.target.closest("[data-action='edit-group']");

        if (button) {
            openEditGroup(button.dataset.id);
        }
    });

    mTbody.addEventListener("click", (event) => {
        const button = event.target.closest("[data-action='edit-match']");

        if (button) {
            openEditMatch(button.dataset.id);
        }
    });

    loadTournaments();

    function fillSlotControls(slotNo, slot) {
        const source = document.getElementById(`mm_slot${slotNo}_source`);
        const sourceMatch = document.getElementById(`mm_slot${slotNo}_match`);
        const seed = document.getElementById(`mm_slot${slotNo}_seed`);
        const team = document.getElementById(`mm_team${slotNo}`);

        source.value = slot.source_type || "TEAM";
        sourceMatch.value = slot.source_match_id || "";
        seed.value = slot.source_seed_no || "";

        if (slot.iddoibong) {
            team.value = String(slot.iddoibong);
        }
    }

    function syncSlotInputs() {
        [1, 2].forEach((slotNo) => {
            const source = document.getElementById(`mm_slot${slotNo}_source`);
            const team = document.getElementById(`mm_team${slotNo}`);
            const sourceMatch = document.getElementById(`mm_slot${slotNo}_match`);
            const seed = document.getElementById(`mm_slot${slotNo}_seed`);
            const isTeam = source.value === "TEAM";
            const isMatchSource = ["WINNER", "LOSER"].includes(source.value);
            const isSeed = source.value === "SEED";

            team.disabled = !isTeam;
            sourceMatch.disabled = !isMatchSource;
            seed.disabled = !isSeed;
        });
    }

    function slotPayload(slotNo) {
        const source = document.getElementById(`mm_slot${slotNo}_source`).value;
        const team = document.getElementById(`mm_team${slotNo}`).value;
        const sourceMatch = document.getElementById(`mm_slot${slotNo}_match`).value;
        const seed = document.getElementById(`mm_slot${slotNo}_seed`).value;

        return {
            slot_so: slotNo,
            source_type: source,
            iddoibong: source === "TEAM" && team ? Number(team) : null,
            source_match_id: ["WINNER", "LOSER"].includes(source) && sourceMatch ? Number(sourceMatch) : null,
            source_seed_no: source === "SEED" && seed ? Number(seed) : null,
        };
    }

    function normalizeAssignmentStatus(status) {
        const normalized = String(status || "DA_XAC_NHAN").toUpperCase();

        return REFEREE_ASSIGNMENT_STATUSES[normalized] ? normalized : "DA_XAC_NHAN";
    }

    function refereeAssignmentsKey(assignments) {
        return (Array.isArray(assignments) ? assignments : [])
            .map((assignment) => {
                const refereeId = Number(assignment.idtrongtai || assignment.referee_id || 0);
                const role = String(assignment.vaitro || assignment.role || "").toUpperCase();

                return refereeId > 0 && role ? `${refereeId}:${role}` : "";
            })
            .filter(Boolean)
            .sort()
            .join("|");
    }

    function setRefereeRowStatus(row, status) {
        const normalized = normalizeAssignmentStatus(status);
        const [badgeClass, label] = REFEREE_ASSIGNMENT_STATUSES[normalized];
        const badge = row.querySelector("[data-field='assignment-status']");
        const note = row.querySelector("[data-field='assignment-note']");

        row.dataset.assignmentStatus = normalized;
        row.classList.toggle("has-assignment-warning", normalized !== "DA_XAC_NHAN");

        if (badge) {
            badge.className = `badge ${badgeClass} referee-assignment-status`;
            badge.textContent = label;
        }

        if (note) {
            const noteText = REFEREE_ASSIGNMENT_NOTES[normalized] || "";
            note.textContent = noteText;
            note.classList.toggle("is-empty", noteText === "");
        }
    }

    function refereeRowStates() {
        return Array.from(mmReferees.querySelectorAll(".referee-row")).map((row) => ({
            row,
            refereeId: Number(row.querySelector("[data-field='referee']")?.value || 0),
            refereeName: row.querySelector("[data-field='referee']")?.selectedOptions?.[0]?.textContent?.trim() || "",
            role: row.querySelector("[data-field='role']")?.value || "",
            status: row.dataset.assignmentStatus || "DA_XAC_NHAN",
        }));
    }

    function refereeReadiness() {
        const selectedRows = refereeRowStates().filter((item) => item.refereeId > 0);
        const confirmedRows = selectedRows.filter((item) => item.status === "DA_XAC_NHAN");
        const missing = [];

        if (!confirmedRows.some((item) => item.role === "TRONG_TAI_CHINH")) {
            missing.push("trọng tài chính");
        }

        if (!confirmedRows.some((item) => item.role === "GIAM_SAT")) {
            missing.push("trọng tài giám sát");
        }

        return {
            missing,
            canceled: selectedRows.filter((item) => ["TU_CHOI", "DA_HUY"].includes(item.status)),
        };
    }

    function updateRefereeNotice() {
        const readiness = refereeReadiness();
        const messages = [];

        if (readiness.canceled.length > 0) {
            const canceledLabels = readiness.canceled
                .map((item) => `${item.refereeName || "Trọng tài chưa chọn"} (${REFEREE_ROLE_LABELS[item.role] || "vai trò chưa rõ"})`)
                .join(", ");
            messages.push(`Có trọng tài đã hủy xác nhận: ${canceledLabels}.`);
        }

        if (readiness.missing.length > 0) {
            messages.push(`Trận chưa đủ điều kiện bắt đầu: thiếu ${readiness.missing.map((role) => `${role} đã xác nhận`).join(" và ")}.`);
        }

        mmRefereeNotice.textContent = messages.join(" ");
        mmRefereeNotice.classList.toggle("hidden", messages.length === 0);
    }

    function renderRefereeRows(assignments) {
        mmReferees.innerHTML = "";

        assignments.forEach((assignment) => {
            addRefereeRow(assignment.idtrongtai, assignment.vaitro, assignment.trangthai || assignment.phancong_trangthai);
        });

        updateRefereeNotice();
    }

    function addRefereeRow(refereeId = "", role = "TRONG_TAI_CHINH", status = "DA_XAC_NHAN") {
        const row = document.createElement("div");
        row.className = "referee-row";
        row.innerHTML = `
            <select data-field="referee"></select>
            <select data-field="role">
                <option value="TRONG_TAI_CHINH">Trọng tài chính</option>
                <option value="TRONG_TAI_PHU">Trọng tài phụ</option>
                <option value="GIAM_SAT">Giám sát</option>
            </select>
            <span class="badge gray referee-assignment-status" data-field="assignment-status"></span>
            <button class="btn" type="button" data-action="remove-referee">Xóa</button>
            <small class="referee-assignment-note is-empty" data-field="assignment-note"></small>
        `;
        const refereeSelect = row.querySelector("[data-field='referee']");
        const roleSelect = row.querySelector("[data-field='role']");
        const initialRefereeId = String(refereeId || "");
        const initialStatus = normalizeAssignmentStatus(status);

        fillSelect(
            refereeSelect,
            referees,
            "idtrongtai",
            (referee) => `${referee.hoten || ""} (${referee.username || ""})${referee.capbac ? ` - ${referee.capbac}` : ""}`,
            "Chọn trọng tài",
            refereeId
        );
        roleSelect.value = role;
        row.dataset.originalRefereeId = initialRefereeId;
        row.dataset.originalAssignmentStatus = initialStatus;
        setRefereeRowStatus(row, initialStatus);
        refereeSelect.addEventListener("change", () => {
            const selectedRefereeId = String(refereeSelect.value || "");
            const nextStatus = selectedRefereeId === row.dataset.originalRefereeId
                ? row.dataset.originalAssignmentStatus
                : "DA_XAC_NHAN";

            setRefereeRowStatus(row, nextStatus);
            updateRefereeNotice();
        });
        roleSelect.addEventListener("change", updateRefereeNotice);
        mmReferees.appendChild(row);
        updateRefereeNotice();
    }

    function refereeAssignmentsPayload() {
        return Array.from(mmReferees.querySelectorAll(".referee-row"))
            .map((row) => ({
                idtrongtai: Number(row.querySelector("[data-field='referee']").value || 0),
                vaitro: row.querySelector("[data-field='role']").value,
                trangthai: row.dataset.assignmentStatus || "DA_XAC_NHAN",
            }))
            .filter((assignment) => assignment.idtrongtai > 0);
    }
})();
