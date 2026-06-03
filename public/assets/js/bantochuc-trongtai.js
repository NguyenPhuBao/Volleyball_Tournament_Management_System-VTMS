const root = document.querySelector(".organizer-referees");
const refereesApi = root?.dataset.refereesApi || "/api/organizer/referees";
const tournamentsApi = root?.dataset.tournamentsApi || "/api/organizer/tournaments";
const matchesApi = root?.dataset.matchesApi || "/api/organizer/referee-matches";
const leavesApi = root?.dataset.leavesApi || "/api/organizer/referee-leaves";

let referees = [];
let tournaments = [];
let matches = [];
let leaves = [];
let currentAssignmentMatchId = null;
let refereeSearchTimer = null;

const tabButtons = document.querySelectorAll(".tab");
const panels = document.querySelectorAll(".refs-panel");
const pageMessage = document.getElementById("pageMessage");

const btnAddRef = document.getElementById("btnAddRef");
const rQ = document.getElementById("r_q");
const rStatus = document.getElementById("r_status");
const rRefresh = document.getElementById("r_refresh");
const rTbody = document.getElementById("r_tbody");

const aTournament = document.getElementById("a_tournament");
const aMatch = document.getElementById("a_match");
const btnAssign = document.getElementById("btnAssign");
const aTbody = document.getElementById("a_tbody");

const lRef = document.getElementById("l_ref");
const btnCreateLeave = document.getElementById("btnCreateLeave");
const lStatus = document.getElementById("l_status");
const lFrom = document.getElementById("l_from");
const lTo = document.getElementById("l_to");
const lRefresh = document.getElementById("l_refresh");
const lTbody = document.getElementById("l_tbody");

const addModal = document.getElementById("addModal");
const addClose = document.getElementById("add_close");
const addCancel = document.getElementById("add_cancel");
const addSave = document.getElementById("add_save");
const addAlert = document.getElementById("add_alert");

const assignModal = document.getElementById("assignModal");
const asClose = document.getElementById("as_close");
const asCancel = document.getElementById("as_cancel");
const asSave = document.getElementById("as_save");
const asRef = document.getElementById("as_ref");
const asRole = document.getElementById("as_role");
const asAlert = document.getElementById("as_alert");

const leaveModal = document.getElementById("leaveModal");
const lvClose = document.getElementById("lv_close");
const lvCancel = document.getElementById("lv_cancel");
const lvSave = document.getElementById("lv_save");
const lvRef = document.getElementById("lv_ref");
const lvFrom = document.getElementById("lv_from");
const lvTo = document.getElementById("lv_to");
const lvReason = document.getElementById("lv_reason");
const lvAlert = document.getElementById("lv_alert");

const addFields = {
    username: document.getElementById("add_username"),
    email: document.getElementById("add_email"),
    phone: document.getElementById("add_phone"),
    password: document.getElementById("add_password"),
    hodem: document.getElementById("add_hodem"),
    ten: document.getElementById("add_ten"),
    gioitinh: document.getElementById("add_gioitinh"),
    ngaysinh: document.getElementById("add_ngaysinh"),
    capbac: document.getElementById("add_capbac"),
    kinhnghiem: document.getElementById("add_kinhnghiem"),
    diachi: document.getElementById("add_diachi"),
};

const statusLabels = {
    HOAT_DONG: "Hoạt động",
    CHO_DUYET: "Chờ duyệt",
    DANG_NGHI: "Đang nghỉ",
    NGUNG_HOAT_DONG: "Ngưng hoạt động",
    CHUA_KICH_HOAT: "Chưa kích hoạt",
    TAM_KHOA: "Tạm khóa",
    DA_HUY: "Đã hủy",
    CHO_XAC_NHAN: "Chờ xác nhận",
    DA_XAC_NHAN: "Đã xác nhận",
    TU_CHOI: "Từ chối",
    DA_DUYET: "Đã duyệt",
};

const roleLabels = {
    TRONG_TAI_CHINH: "Trọng tài chính",
    TRONG_TAI_PHU: "Trọng tài phụ",
    GIAM_SAT: "Giám sát",
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

function showAlert(element, message) {
    element.textContent = message;
    element.classList.remove("hidden");
}

function hideAlert(element) {
    element.textContent = "";
    element.classList.add("hidden");
}

function badgeClass(status) {
    if (["HOAT_DONG", "DA_XAC_NHAN", "DA_DUYET"].includes(status)) {
        return "ok";
    }

    if (["CHO_DUYET", "CHO_XAC_NHAN", "CHUA_KICH_HOAT"].includes(status)) {
        return "wait";
    }

    if (["TU_CHOI", "NGUNG_HOAT_DONG", "TAM_KHOA"].includes(status)) {
        return "lock";
    }

    return "gray";
}

function statusBadge(status) {
    const label = statusLabels[status] || status || "-";

    return `<span class="badge ${badgeClass(status)}">${escapeHtml(label)}</span>`;
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

function buildRefereesUrl() {
    const params = new URLSearchParams();

    if (rQ.value.trim() !== "") {
        params.set("q", rQ.value.trim());
    }

    if (rStatus.value !== "") {
        params.set("status", rStatus.value);
    }

    const query = params.toString();

    return query === "" ? refereesApi : `${refereesApi}?${query}`;
}

function buildMatchesUrl() {
    const params = new URLSearchParams();

    if (aTournament.value !== "") {
        params.set("tournament_id", aTournament.value);
    }

    const query = params.toString();

    return query === "" ? matchesApi : `${matchesApi}?${query}`;
}

function buildLeavesUrl() {
    const params = new URLSearchParams();

    if (lRef.value !== "") {
        params.set("referee_id", lRef.value);
    }

    if (lStatus.value !== "") {
        params.set("status", lStatus.value);
    }

    if (lFrom.value !== "") {
        params.set("from", lFrom.value);
    }

    if (lTo.value !== "") {
        params.set("to", lTo.value);
    }

    const query = params.toString();

    return query === "" ? leavesApi : `${leavesApi}?${query}`;
}

function refereeId(referee) {
    return Number(referee.idtrongtai || referee.id);
}

function matchId(match) {
    return Number(match.idtrandau || match.id);
}

function tournamentId(tournament) {
    return Number(tournament.idgiaidau || tournament.id);
}

function fullName(referee) {
    return referee.hoten || [referee.hodem, referee.ten].filter(Boolean).join(" ") || referee.username || "-";
}

function activeReferees() {
    return referees.filter((referee) => referee.trangthai === "HOAT_DONG" && referee.trangthai_taikhoan === "HOAT_DONG");
}

function fillRefereeSelect(select, items, placeholder) {
    select.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>` + items.map((referee) => {
        const id = refereeId(referee);
        return `<option value="${id}">${escapeHtml(fullName(referee))} (${escapeHtml(referee.username)})</option>`;
    }).join("");
}

function fillTournamentSelect() {
    aTournament.innerHTML = '<option value="">Tất cả giải đấu</option>' + tournaments.map((tournament) => {
        return `<option value="${tournamentId(tournament)}">${escapeHtml(tournament.tengiaidau || tournament.name)}</option>`;
    }).join("");
}

function fillMatchSelect() {
    aMatch.innerHTML = '<option value="">Chọn trận đấu</option>' + matches.map((match) => {
        const label = `#${matchId(match)} - ${match.doi1 || ""} vs ${match.doi2 || ""} - ${match.thoigianbatdau || ""}`;
        return `<option value="${matchId(match)}">${escapeHtml(label)}</option>`;
    }).join("");
}

function refreshRefereeSelects() {
    const refs = activeReferees();
    fillRefereeSelect(asRef, refs, refs.length === 0 ? "Không có trọng tài hoạt động" : "Chọn trọng tài");
    fillRefereeSelect(lRef, referees, "Tất cả trọng tài");
    fillRefereeSelect(lvRef, refs, refs.length === 0 ? "Không có trọng tài hoạt động" : "Chọn trọng tài");
}

async function loadReferees() {
    rTbody.innerHTML = '<tr><td colspan="7" class="empty">Đang tải dữ liệu...</td></tr>';
    setPageMessage("");

    try {
        const payload = await apiRequest(buildRefereesUrl());
        referees = payload.data || [];
        renderReferees();
        refreshRefereeSelects();
    } catch (error) {
        referees = [];
        renderReferees();
        refreshRefereeSelects();
        setPageMessage(error.message);
    }
}

async function loadTournaments() {
    try {
        const payload = await apiRequest(tournamentsApi);
        tournaments = payload.data || [];
        fillTournamentSelect();
    } catch (error) {
        tournaments = [];
        fillTournamentSelect();
        setPageMessage(error.message);
    }
}

async function loadMatches() {
    aTbody.innerHTML = '<tr><td colspan="5" class="empty">Chọn trận đấu để xem phân công.</td></tr>';
    currentAssignmentMatchId = null;

    try {
        const payload = await apiRequest(buildMatchesUrl());
        matches = payload.data || [];
        fillMatchSelect();
    } catch (error) {
        matches = [];
        fillMatchSelect();
        setPageMessage(error.message);
    }
}

async function loadLeaves() {
    lTbody.innerHTML = '<tr><td colspan="7" class="empty">Đang tải dữ liệu...</td></tr>';

    try {
        const payload = await apiRequest(buildLeavesUrl());
        leaves = payload.data || [];
        renderLeaves();
    } catch (error) {
        leaves = [];
        renderLeaves();
        setPageMessage(error.message);
    }
}

function renderReferees() {
    if (referees.length === 0) {
        rTbody.innerHTML = '<tr><td colspan="7" class="empty">Không có trọng tài phù hợp.</td></tr>';
        return;
    }

    rTbody.innerHTML = referees.map((referee) => `
        <tr>
            <td>${refereeId(referee)}</td>
            <td>${escapeHtml(fullName(referee))}</td>
            <td>${escapeHtml(referee.username)}</td>
            <td>${escapeHtml(referee.capbac || "")}</td>
            <td>${Number(referee.kinhnghiem || 0)}</td>
            <td>${statusBadge(referee.trangthai)}</td>
            <td>${statusBadge(referee.trangthai_taikhoan)}</td>
        </tr>
    `).join("");
}

function parseAssignments(match) {
    const raw = match?.assignments || "";

    if (raw === "") {
        return [];
    }

    return raw.split("|").map((item) => {
        const [idphancong, idtrongtai, vaitro, trangthai, username] = item.split(":");
        const referee = referees.find((candidate) => refereeId(candidate) === Number(idtrongtai));

        return {
            idphancong: Number(idphancong),
            idtrongtai: Number(idtrongtai),
            vaitro,
            trangthai,
            username,
            hoten: referee ? fullName(referee) : username,
        };
    });
}

function renderAssignments() {
    const selectedMatch = matches.find((match) => matchId(match) === currentAssignmentMatchId);
    const assignments = parseAssignments(selectedMatch);

    if (!currentAssignmentMatchId) {
        aTbody.innerHTML = '<tr><td colspan="5" class="empty">Chọn trận đấu để xem phân công.</td></tr>';
        return;
    }

    if (assignments.length === 0) {
        aTbody.innerHTML = '<tr><td colspan="5" class="empty">Trận đấu chưa có trọng tài được phân công.</td></tr>';
        return;
    }

    aTbody.innerHTML = assignments.map((assignment) => `
        <tr>
            <td>${assignment.idphancong}</td>
            <td>${escapeHtml(assignment.hoten)} <span class="sub">${escapeHtml(assignment.username || "")}</span></td>
            <td>${escapeHtml(roleLabels[assignment.vaitro] || assignment.vaitro)}</td>
            <td>${statusBadge(assignment.trangthai)}</td>
            <td>-</td>
        </tr>
    `).join("");
}

function renderLeaves() {
    if (leaves.length === 0) {
        lTbody.innerHTML = '<tr><td colspan="7" class="empty">Không có đơn nghỉ phù hợp.</td></tr>';
        return;
    }

    lTbody.innerHTML = leaves.map((leave) => `
        <tr>
            <td>${Number(leave.iddonnghi || 0)}</td>
            <td>${escapeHtml(leave.hoten || leave.username || "-")}</td>
            <td>${escapeHtml(leave.tungay)}</td>
            <td>${escapeHtml(leave.denngay)}</td>
            <td>${escapeHtml(leave.lydo)}</td>
            <td>${statusBadge(leave.trangthai)}</td>
            <td>${escapeHtml(leave.ngaygui || "")}</td>
        </tr>
    `).join("");
}

function openAddModal() {
    hideAlert(addAlert);
    Object.values(addFields).forEach((field) => {
        if (field.tagName === "SELECT") {
            field.selectedIndex = 0;
            return;
        }

        field.value = "";
    });
    addFields.kinhnghiem.value = "0";
    addModal.classList.remove("hidden");
}

function closeAddModal() {
    addModal.classList.add("hidden");
}

async function saveReferee() {
    hideAlert(addAlert);

    const payload = {
        username: addFields.username.value.trim(),
        email: addFields.email.value.trim(),
        sodienthoai: addFields.phone.value.trim() || null,
        password: addFields.password.value,
        hodem: addFields.hodem.value.trim(),
        ten: addFields.ten.value.trim(),
        gioitinh: addFields.gioitinh.value,
        ngaysinh: addFields.ngaysinh.value || null,
        diachi: addFields.diachi.value.trim() || null,
        capbac: addFields.capbac.value.trim() || null,
        kinhnghiem: Number(addFields.kinhnghiem.value || 0),
    };

    if (!payload.username || !payload.email || !payload.password || !payload.hodem || !payload.ten) {
        showAlert(addAlert, "Vui lòng nhập đầy đủ username, email, mật khẩu, họ đệm và tên.");
        return;
    }

    if (payload.password.length < 6) {
        showAlert(addAlert, "Mật khẩu tối thiểu 6 ký tự.");
        return;
    }

    addSave.disabled = true;

    try {
        await apiRequest(refereesApi, {
            method: "POST",
            body: JSON.stringify(payload),
        });
        closeAddModal();
        await loadReferees();
        setPageMessage("Thêm trọng tài thành công, chờ duyệt.", true);
    } catch (error) {
        showAlert(addAlert, error.message);
    } finally {
        addSave.disabled = false;
    }
}

function openAssignModal() {
    hideAlert(asAlert);

    if (!currentAssignmentMatchId) {
        showAlert(asAlert, "Vui lòng chọn trận đấu trước khi phân công.");
    }

    refreshRefereeSelects();
    assignModal.classList.remove("hidden");
}

function closeAssignModal() {
    assignModal.classList.add("hidden");
}

async function postAssignment(replace = false) {
    const refId = Number(asRef.value);

    if (!currentAssignmentMatchId) {
        throw new Error("Chưa chọn trận đấu.");
    }

    if (!refId) {
        throw new Error("Vui lòng chọn trọng tài.");
    }

    return apiRequest(`${refereesApi}/${refId}/assignments`, {
        method: "POST",
        body: JSON.stringify({
            idtrandau: currentAssignmentMatchId,
            vaitro: asRole.value,
            replace,
        }),
    });
}

async function saveAssignment() {
    hideAlert(asAlert);
    asSave.disabled = true;

    try {
        const selectedMatchId = currentAssignmentMatchId;
        await postAssignment(false);
        closeAssignModal();
        await loadMatches();
        currentAssignmentMatchId = selectedMatchId;
        aMatch.value = String(selectedMatchId || "");
        renderAssignments();
        setPageMessage("Phân công trọng tài thành công.", true);
    } catch (error) {
        if (error.message.includes("replace=true")) {
            const accepted = window.confirm("Vai trò này đã có trọng tài. Bạn có muốn thay đổi trọng tài không?");

            if (accepted) {
                try {
                    const selectedMatchId = currentAssignmentMatchId;
                    await postAssignment(true);
                    closeAssignModal();
                    await loadMatches();
                    currentAssignmentMatchId = selectedMatchId;
                    aMatch.value = String(selectedMatchId || "");
                    renderAssignments();
                    setPageMessage("Thay đổi phân công trọng tài thành công.", true);
                } catch (retryError) {
                    showAlert(asAlert, retryError.message);
                }
            }
        } else {
            showAlert(asAlert, error.message);
        }
    } finally {
        asSave.disabled = false;
    }
}

function openLeaveModal() {
    hideAlert(lvAlert);
    refreshRefereeSelects();
    lvRef.value = lRef.value || "";
    lvFrom.value = "";
    lvTo.value = "";
    lvReason.value = "";
    leaveModal.classList.remove("hidden");
}

function closeLeaveModal() {
    leaveModal.classList.add("hidden");
}

async function saveLeave() {
    hideAlert(lvAlert);

    const refId = Number(lvRef.value);
    const payload = {
        tungay: lvFrom.value,
        denngay: lvTo.value,
        lydo: lvReason.value.trim(),
    };

    if (!refId || !payload.tungay || !payload.denngay || !payload.lydo) {
        showAlert(lvAlert, "Vui lòng nhập đầy đủ trọng tài, từ ngày, đến ngày và lý do.");
        return;
    }

    if (new Date(payload.denngay) < new Date(payload.tungay)) {
        showAlert(lvAlert, "Đến ngày phải lớn hơn hoặc bằng từ ngày.");
        return;
    }

    lvSave.disabled = true;

    try {
        await apiRequest(`${refereesApi}/${refId}/leave`, {
            method: "POST",
            body: JSON.stringify(payload),
        });
        closeLeaveModal();
        await loadReferees();
        await loadLeaves();
        setPageMessage("Đã ghi nhận cho nghỉ trọng tài, chờ quản trị viên xử lý tài khoản.", true);
    } catch (error) {
        showAlert(lvAlert, error.message);
    } finally {
        lvSave.disabled = false;
    }
}

tabButtons.forEach((button) => {
    button.addEventListener("click", () => {
        tabButtons.forEach((item) => item.classList.remove("active"));
        button.classList.add("active");
        panels.forEach((panel) => panel.classList.toggle("hidden", panel.id !== button.dataset.tab));
    });
});

rQ.addEventListener("input", () => {
    clearTimeout(refereeSearchTimer);
    refereeSearchTimer = setTimeout(loadReferees, 250);
});
rStatus.addEventListener("change", loadReferees);
rRefresh.addEventListener("click", loadReferees);

aTournament.addEventListener("change", loadMatches);
aMatch.addEventListener("change", () => {
    currentAssignmentMatchId = Number(aMatch.value || 0) || null;
    renderAssignments();
});
btnAssign.addEventListener("click", openAssignModal);

lRef.addEventListener("change", loadLeaves);
lStatus.addEventListener("change", loadLeaves);
lFrom.addEventListener("change", loadLeaves);
lTo.addEventListener("change", loadLeaves);
lRefresh.addEventListener("click", loadLeaves);
btnCreateLeave.addEventListener("click", openLeaveModal);

btnAddRef.addEventListener("click", openAddModal);
addClose.addEventListener("click", closeAddModal);
addCancel.addEventListener("click", closeAddModal);
addSave.addEventListener("click", saveReferee);

asClose.addEventListener("click", closeAssignModal);
asCancel.addEventListener("click", closeAssignModal);
asSave.addEventListener("click", saveAssignment);

lvClose.addEventListener("click", closeLeaveModal);
lvCancel.addEventListener("click", closeLeaveModal);
lvSave.addEventListener("click", saveLeave);

Promise.all([loadReferees(), loadTournaments()])
    .then(() => Promise.all([loadMatches(), loadLeaves()]))
    .catch((error) => setPageMessage(error.message));
