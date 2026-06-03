const root = document.querySelector(".organizer-teams");
const tournamentsApi = root?.dataset.tournamentsApi || "/api/organizer/tournaments";
const teamsApi = root?.dataset.teamsApi || "/api/organizer/teams";

let tournaments = [];
let teams = [];
let currentProfile = null;
let searchTimer = null;
let memberSearchTimer = null;

const tbody = document.getElementById("tbody");
const q = document.getElementById("q");
const tournamentFilter = document.getElementById("tournamentFilter");
const teamStatusFilter = document.getElementById("teamStatusFilter");
const regStatusFilter = document.getElementById("regStatusFilter");
const btnRefresh = document.getElementById("btnRefresh");
const pageMessage = document.getElementById("pageMessage");

const detailModal = document.getElementById("detailModal");
const mClose = document.getElementById("m_close");
const mCloseBtn = document.getElementById("m_closeBtn");
const mAlert = document.getElementById("m_alert");

const fields = {
    teamName: document.getElementById("m_teamName"),
    teamSub: document.getElementById("m_teamSub"),
    teamId: document.getElementById("m_teamId"),
    coach: document.getElementById("m_coach"),
    local: document.getElementById("m_local"),
    logo: document.getElementById("m_logo"),
    desc: document.getElementById("m_desc"),
    status: document.getElementById("m_status"),
    tournament: document.getElementById("m_tournament"),
    memberStatus: document.getElementById("m_memberStatus"),
    memberRole: document.getElementById("m_memberRole"),
    memberSearch: document.getElementById("m_memberQ"),
    members: document.getElementById("m_members"),
};

const teamStatusLabels = {
    CHO_DUYET: "Chờ duyệt",
    HOAT_DONG: "Hoạt động",
    TAM_KHOA: "Tạm khóa",
    GIAI_THE: "Giải thể",
};

const registrationStatusLabels = {
    CHO_DUYET: "Chờ duyệt",
    DA_DUYET: "Đã duyệt",
    TU_CHOI: "Từ chối",
    DA_HUY: "Đã hủy",
};

const accountStatusLabels = {
    HOAT_DONG: "Hoạt động",
    CHUA_KICH_HOAT: "Chưa kích hoạt",
    CHO_DUYET: "Chờ duyệt",
    TAM_KHOA: "Tạm khóa",
    DA_HUY: "Đã hủy",
};

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

function showAlert(message) {
    mAlert.textContent = message;
    mAlert.classList.remove("hidden");
}

function hideAlert() {
    mAlert.textContent = "";
    mAlert.classList.add("hidden");
}

function teamId(item) {
    return Number(item.iddoibong || item.id);
}

function tournamentId(item) {
    return Number(item.idgiaidau || item.tournament_id);
}

function teamStatusClass(status) {
    if (status === "CHO_DUYET") {
        return "wait";
    }

    if (status === "HOAT_DONG") {
        return "ok";
    }

    if (status === "TAM_KHOA") {
        return "lock";
    }

    return "gray";
}

function registrationStatusClass(status) {
    if (status === "CHO_DUYET") {
        return "wait";
    }

    if (status === "DA_DUYET") {
        return "ok";
    }

    if (status === "TU_CHOI") {
        return "lock";
    }

    return "gray";
}

function accountStatusClass(status) {
    if (status === "HOAT_DONG") {
        return "ok";
    }

    if (status === "CHUA_KICH_HOAT" || status === "CHO_DUYET") {
        return "wait";
    }

    if (status === "TAM_KHOA" || status === "DA_HUY") {
        return "lock";
    }

    return "gray";
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

function fillTournamentSelects() {
    const options = tournaments.map((item) => `<option value="${Number(item.idgiaidau)}">${escapeHtml(item.tengiaidau)}</option>`).join("");
    tournamentFilter.innerHTML = `<option value="">Tất cả giải đấu</option>${options}`;
    fields.tournament.innerHTML = `<option value="">-</option>${options}`;
}

async function loadTournaments() {
    const payload = await apiRequest(tournamentsApi);
    tournaments = payload.data || [];
    fillTournamentSelects();
}

function buildTeamsUrl() {
    const params = new URLSearchParams();

    if (q.value.trim() !== "") {
        params.set("q", q.value.trim());
    }

    if (tournamentFilter.value !== "") {
        params.set("tournament_id", tournamentFilter.value);
    }

    if (teamStatusFilter.value !== "") {
        params.set("team_status", teamStatusFilter.value);
    }

    if (regStatusFilter.value !== "") {
        params.set("registration_status", regStatusFilter.value);
    }

    const query = params.toString();

    return query === "" ? teamsApi : `${teamsApi}?${query}`;
}

async function loadTeams() {
    tbody.innerHTML = '<tr><td colspan="8" class="empty">Đang tải dữ liệu...</td></tr>';
    setPageMessage("");

    try {
        const payload = await apiRequest(buildTeamsUrl());
        teams = payload.data || [];
        renderTeams();
    } catch (error) {
        teams = [];
        renderTeams();
        setPageMessage(error.message);
    }
}

function renderTeams() {
    if (teams.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty">Không có hồ sơ đội bóng phù hợp.</td></tr>';
        return;
    }

    tbody.innerHTML = teams.map((item) => {
        const id = teamId(item);
        const tid = tournamentId(item);
        const teamStatus = item.trangthaidoibong || "";
        const registrationStatus = item.trangthaidangky || "";

        return `
            <tr>
                <td>${id}</td>
                <td>
                    <strong>${escapeHtml(item.tendoibong)}</strong>
                    <span class="truncate" title="${escapeHtml(item.mota || "")}">${escapeHtml(item.mota || "")}</span>
                </td>
                <td><span class="truncate" title="${escapeHtml(item.tengiaidau || "")}">${escapeHtml(item.tengiaidau || "")}</span></td>
                <td>${escapeHtml(item.diaphuong || "")}</td>
                <td>
                    ${escapeHtml(item.huanluyenvien_hoten || item.huanluyenvien_username || "")}
                    <span class="sub">${escapeHtml(item.huanluyenvien_email || "")}</span>
                </td>
                <td><span class="badge ${teamStatusClass(teamStatus)}">${escapeHtml(teamStatusLabels[teamStatus] || teamStatus)}</span></td>
                <td><span class="badge ${registrationStatusClass(registrationStatus)}">${escapeHtml(registrationStatusLabels[registrationStatus] || registrationStatus)}</span></td>
                <td>
                    <div class="row-actions">
                        <button class="btn" type="button" data-action="detail" data-id="${id}" data-tournament-id="${tid}">Xem</button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

async function openDetail(id, tid) {
    hideAlert();
    setPageMessage("");

    try {
        const payload = await apiRequest(`${tournamentsApi}/${tid}/teams/${id}`);
        currentProfile = payload.data;
        fillDetail(currentProfile);
        detailModal.classList.remove("hidden");
        detailModal.setAttribute("aria-hidden", "false");
    } catch (error) {
        setPageMessage(error.message);
    }
}

function fillDetail(profile) {
    fields.teamName.textContent = profile.tendoibong || "Chi tiết đội bóng";
    fields.teamSub.textContent = `${profile.tengiaidau || ""} - ĐK: ${registrationStatusLabels[profile.trangthaidangky] || profile.trangthaidangky || ""}`;
    fields.teamId.value = profile.iddoibong || "";
    fields.coach.value = profile.huanluyenvien_hoten || profile.huanluyenvien_username || "";
    fields.local.value = profile.diaphuong || "";
    fields.logo.value = profile.logo || "";
    fields.desc.value = profile.mota || "";
    fields.status.value = profile.trangthaidoibong || "CHO_DUYET";
    fields.tournament.value = profile.idgiaidau || "";
    fields.memberStatus.value = "";
    fields.memberRole.value = "";
    fields.memberSearch.value = "";
    renderMembers();
}

function closeDetail() {
    detailModal.classList.add("hidden");
    detailModal.setAttribute("aria-hidden", "true");
    currentProfile = null;
    hideAlert();
}

function filterMembers() {
    const members = currentProfile?.members || [];
    const status = fields.memberStatus.value;
    const role = fields.memberRole.value;
    const keyword = fields.memberSearch.value.trim().toLowerCase();

    return members.filter((item) => {
        const accountStatus = item.trangthai_taikhoan || item.trangthainguoidung || "";

        if (status !== "" && accountStatus !== status) {
            return false;
        }

        if (role !== "" && item.vaitrotrongdoi !== role) {
            return false;
        }

        const haystack = `${item.mavandongvien || ""} ${item.hoten || ""}`.toLowerCase();

        return keyword === "" || haystack.includes(keyword);
    });
}

function renderMembers() {
    const members = filterMembers();

    if (members.length === 0) {
        fields.members.innerHTML = '<tr><td colspan="8" class="empty">Không có thành viên phù hợp.</td></tr>';
        return;
    }

    fields.members.innerHTML = members.map((item) => {
        const accountStatus = item.trangthai_taikhoan || item.trangthainguoidung || "";

        return `
            <tr>
                <td>${Number(item.idthanhvien || 0)}</td>
                <td>${escapeHtml(item.mavandongvien || "")}</td>
                <td>${escapeHtml(item.hoten || "")}</td>
                <td>${escapeHtml(item.vitri || "")}</td>
                <td>${escapeHtml(item.vaitrotrongdoi || "")}</td>
                <td><span class="badge ${accountStatusClass(accountStatus)}">${escapeHtml(accountStatusLabels[accountStatus] || accountStatus)}</span></td>
                <td>${escapeHtml(item.ngaythamgia || "")}</td>
                <td>${escapeHtml(item.ngayroi || "")}</td>
            </tr>
        `;
    }).join("");
}

tbody.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-action='detail']");

    if (!button) {
        return;
    }

    openDetail(Number(button.dataset.id), Number(button.dataset.tournamentId));
});

mClose.addEventListener("click", closeDetail);
mCloseBtn.addEventListener("click", closeDetail);
btnRefresh.addEventListener("click", loadTeams);

q.addEventListener("input", () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadTeams, 250);
});
tournamentFilter.addEventListener("change", loadTeams);
teamStatusFilter.addEventListener("change", loadTeams);
regStatusFilter.addEventListener("change", loadTeams);

fields.tournament.addEventListener("change", () => {
    if (!currentProfile || fields.tournament.value === "") {
        return;
    }

    openDetail(Number(currentProfile.iddoibong), Number(fields.tournament.value));
});
fields.memberStatus.addEventListener("change", renderMembers);
fields.memberRole.addEventListener("change", renderMembers);
fields.memberSearch.addEventListener("input", () => {
    clearTimeout(memberSearchTimer);
    memberSearchTimer = setTimeout(renderMembers, 200);
});

detailModal.addEventListener("click", (event) => {
    if (event.target === detailModal) {
        closeDetail();
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !detailModal.classList.contains("hidden")) {
        closeDetail();
    }
});

(async function init() {
    try {
        await loadTournaments();
        await loadTeams();
    } catch (error) {
        setPageMessage(error.message);
    }
})();
