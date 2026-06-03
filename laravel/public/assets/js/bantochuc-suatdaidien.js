const root = document.querySelector(".organizer-teams");
const eligibilityApi = root?.dataset.eligibilityApi || "/api/organizer/higher-eligibility";

let candidates = [];
let incoming = [];
let searchTimer = null;
let currentCandidate = null;
let currentProfile = null;
let reviewReachedBottom = false;

const sourceTournamentOptions = new Map();
const reviewViewed = new Set();
const reviewApproved = new Set();

const q = document.getElementById("q");
const sourceTournamentFilter = document.getElementById("sourceTournamentFilter");
const achievementFilter = document.getElementById("achievementFilter");
const btnRefresh = document.getElementById("btnRefresh");
const candidateBody = document.getElementById("candidateBody");
const incomingBody = document.getElementById("incomingBody");
const pageMessage = document.getElementById("pageMessage");
const reviewModal = document.getElementById("reviewModal");
const reviewModalContent = document.getElementById("reviewModalContent");
const reviewClose = document.getElementById("reviewClose");
const reviewCloseBtn = document.getElementById("reviewCloseBtn");
const reviewTeamName = document.getElementById("reviewTeamName");
const reviewTeamSub = document.getElementById("reviewTeamSub");
const reviewTeamId = document.getElementById("reviewTeamId");
const reviewTeamLocal = document.getElementById("reviewTeamLocal");
const reviewTeamStatus = document.getElementById("reviewTeamStatus");
const reviewMemberCount = document.getElementById("reviewMemberCount");
const reviewCoach = document.getElementById("reviewCoach");
const reviewMembers = document.getElementById("reviewMembers");
const reviewPercent = document.getElementById("reviewPercent");
const reviewProgress = document.getElementById("reviewProgress");
const reviewProgressText = document.getElementById("reviewProgressText");
const reviewAlert = document.getElementById("reviewAlert");
const reviewMark = document.getElementById("reviewMark");
const reviewNominate = document.getElementById("reviewNominate");
const personModal = document.getElementById("personModal");
const personClose = document.getElementById("personClose");
const personCloseBtn = document.getElementById("personCloseBtn");
const personTitle = document.getElementById("personTitle");
const personSub = document.getElementById("personSub");
const personDetails = document.getElementById("personDetails");

const statusLabels = {
    CHUA_CO_GIAI_CAP_TREN: "Chưa có giải cấp trên",
    DU_DIEU_KIEN: "Đủ điều kiện",
    DA_DE_CU: "Đã đề cử",
    DA_XAC_NHAN: "Đã xác nhận",
    TU_CHOI: "Từ chối",
};

const achievementLabels = {
    VO_DICH: "Vô địch",
    A_QUAN: "Á quân",
    HANG_BA: "Hạng ba",
    TOP_4: "Top 4",
    TOP_8: "Top 8",
    THAM_DU: "Tham dự",
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

function setPageMessage(message, success = false) {
    pageMessage.textContent = message || "";
    pageMessage.classList.toggle("success", success);
}

function showReviewAlert(message) {
    reviewAlert.textContent = message || "";
    reviewAlert.classList.toggle("hidden", !message);
}

function statusClass(status) {
    if (status === "DU_DIEU_KIEN" || status === "DA_XAC_NHAN") return "ok";
    if (status === "DA_DE_CU") return "wait";
    if (status === "TU_CHOI") return "lock";
    return "gray";
}

function candidateStatus(item) {
    if (Number(item?.idgiaidau_dich || 0) <= 0) {
        return "CHUA_CO_GIAI_CAP_TREN";
    }

    return item?.trangthai_decu || "CHUA_DANH_DAU";
}

function canMarkCandidate(item) {
    return Number(item?.idgiaidau_dich || 0) > 0 && ["CHUA_DANH_DAU", "TU_CHOI"].includes(candidateStatus(item));
}

function canNominateCandidate(item) {
    return candidateStatus(item) === "DU_DIEU_KIEN" && Number(item.iddecu || 0) > 0;
}

function activeMembers(profile = currentProfile) {
    return (profile?.members || []).filter((item) => item.trangthaithanhvien === "DANG_THAM_GIA");
}

function requiredReviewKeys() {
    if (!currentProfile) return [];

    return ["coach", ...activeMembers().map((item) => `member:${Number(item.idthanhvien)}`)];
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

function buildUrl() {
    const params = new URLSearchParams();
    if (q.value.trim() !== "") params.set("q", q.value.trim());
    if (sourceTournamentFilter.value !== "") params.set("source_tournament_id", sourceTournamentFilter.value);
    if (achievementFilter.value !== "") params.set("achievement", achievementFilter.value);
    const query = params.toString();
    return query === "" ? eligibilityApi : `${eligibilityApi}?${query}`;
}

function syncSourceTournamentOptions(items) {
    items.forEach((item) => {
        const id = Number(item.idgiaidau_nguon || item.idgiaidau || 0);
        if (id > 0) {
            sourceTournamentOptions.set(id, item.tengiaidau_nguon || item.tengiaidau || `Giải #${id}`);
        }
    });

    const selected = sourceTournamentFilter.value;
    const options = [...sourceTournamentOptions.entries()]
        .sort((left, right) => String(left[1]).localeCompare(String(right[1]), "vi"))
        .map(([id, label]) => `<option value="${id}">${escapeHtml(label)}</option>`)
        .join("");

    sourceTournamentFilter.innerHTML = `<option value="">Tất cả giải đấu nguồn</option>${options}`;
    sourceTournamentFilter.value = selected;
}

async function loadData() {
    candidateBody.innerHTML = '<tr><td colspan="6" class="empty">Đang tải dữ liệu...</td></tr>';
    incomingBody.innerHTML = '<tr><td colspan="6" class="empty">Đang tải dữ liệu...</td></tr>';
    setPageMessage("");

    try {
        const payload = await apiRequest(buildUrl());
        candidates = payload.data?.candidates || [];
        incoming = payload.data?.incoming || [];
        syncSourceTournamentOptions([...(payload.data?.source_tournaments || []), ...candidates]);
        renderCandidates();
        renderIncoming();
    } catch (error) {
        candidates = [];
        incoming = [];
        renderCandidates();
        renderIncoming();
        setPageMessage(error.message);
    }
}

function renderCandidates() {
    if (candidates.length === 0) {
        candidateBody.innerHTML = '<tr><td colspan="6" class="empty">Chưa có đội phù hợp để đề cử.</td></tr>';
        return;
    }

    candidateBody.innerHTML = candidates.map((item) => {
        const status = candidateStatus(item);
        const statusText = statusLabels[status] || "Chưa đánh dấu";
        const hasTargetTournament = Number(item.idgiaidau_dich || 0) > 0;

        return `
            <tr>
                <td>
                    <strong>${escapeHtml(item.tendoibong)}</strong>
                    <span class="sub">${escapeHtml(item.tenkhuvuc_doi || item.diaphuong || "")}</span>
                </td>
                <td>
                    <strong>${escapeHtml(item.tengiaidau_nguon)}</strong>
                    <span class="sub">${escapeHtml(item.tencapgiaidau_nguon)} - ${escapeHtml(achievementLabels[item.danhhieu] || item.danhhieu || "")} ${escapeHtml(item.ngay_cong_nhan || "")}</span>
                </td>
                <td>
                    <strong>${escapeHtml(item.tengiaidau_dich || "Chưa có giải cấp trên")}</strong>
                    <span class="sub">${escapeHtml(item.tencapgiaidau_dich)} - ${escapeHtml(item.tenkhuvuc_dich || "")}</span>
                </td>
                <td>${escapeHtml(item.bantochuc_nhan || "Chưa có")}</td>
                <td><span class="badge ${statusClass(status)}">${escapeHtml(statusText)}</span></td>
                <td>
                    <div class="row-actions">
                        <button class="btn" type="button" data-action="view" data-achievement-id="${Number(item.idthanhtich)}" data-target-id="${Number(item.idgiaidau_dich)}" ${hasTargetTournament ? "" : "disabled"}>Xem</button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function renderIncoming() {
    if (incoming.length === 0) {
        incomingBody.innerHTML = '<tr><td colspan="6" class="empty">Chưa có đề cử gửi đến.</td></tr>';
        return;
    }

    incomingBody.innerHTML = incoming.map((item) => {
        const status = item.trangthai || "";
        const actionable = status === "DA_DE_CU";

        return `
            <tr>
                <td>
                    <strong>${escapeHtml(item.tendoibong)}</strong>
                    <span class="sub">${escapeHtml(item.tenkhuvuc_doi || item.diaphuong || "")}</span>
                </td>
                <td>${escapeHtml(item.bantochuc_decu || "")}</td>
                <td>
                    <strong>${escapeHtml(item.tengiaidau_nguon)}</strong>
                    <span class="sub">${escapeHtml(item.tencapgiaidau_nguon)} - ${escapeHtml(achievementLabels[item.danhhieu] || item.danhhieu || "")} ${escapeHtml(item.ngay_cong_nhan || "")}</span>
                </td>
                <td>
                    <strong>${escapeHtml(item.tengiaidau_dich)}</strong>
                    <span class="sub">${escapeHtml(item.tencapgiaidau_dich)}</span>
                </td>
                <td><span class="badge ${statusClass(status)}">${escapeHtml(statusLabels[status] || status)}</span></td>
                <td>
                    <div class="row-actions">
                        <button class="btn primary" type="button" data-action="approve" data-id="${Number(item.iddecu)}" ${actionable ? "" : "disabled"}>Xác nhận</button>
                        <button class="btn" type="button" data-action="reject" data-id="${Number(item.iddecu)}" ${actionable ? "" : "disabled"}>Từ chối</button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function reviewCheck(key) {
    const checked = reviewApproved.has(key);
    const disabled = !canMarkCandidate(currentCandidate) || !reviewViewed.has(key);

    return `
        <label class="review-check${checked ? " checked" : ""}">
            <input type="checkbox" data-review-key="${escapeHtml(key)}" ${checked ? "checked" : ""} ${disabled ? "disabled" : ""} />
            <span aria-hidden="true">✓</span>
            Đủ điều kiện
        </label>
    `;
}

function renderCoachReview() {
    if (!currentProfile) {
        reviewCoach.innerHTML = "";
        return;
    }

    reviewCoach.innerHTML = `
        <article class="review-person">
            <div>
                <strong>${escapeHtml(currentProfile.huanluyenvien_hoten || currentProfile.huanluyenvien_username || "HLV")}</strong>
                <span class="sub">${escapeHtml(currentProfile.huanluyenvien_email || "")}</span>
            </div>
            <div class="row-actions">
                <button class="btn" type="button" data-person-key="coach">Xem</button>
                ${reviewCheck("coach")}
            </div>
        </article>
    `;
}

function renderMemberReviews() {
    const members = activeMembers();

    reviewMemberCount.textContent = String(members.length);

    if (members.length === 0) {
        reviewMembers.innerHTML = '<tr><td colspan="5" class="empty">Đội chưa có VĐV đang tham gia để xét.</td></tr>';
        return;
    }

    reviewMembers.innerHTML = members.map((item) => {
        const key = `member:${Number(item.idthanhvien)}`;

        return `
            <tr>
                <td>${escapeHtml(item.mavandongvien || "")}</td>
                <td>${escapeHtml(item.hoten || "")}</td>
                <td>${escapeHtml(item.vitri || "")}</td>
                <td>${escapeHtml(item.vaitrotrongdoi || "")}</td>
                <td>
                    <div class="row-actions">
                        <button class="btn" type="button" data-person-key="${escapeHtml(key)}">Xem</button>
                        ${reviewCheck(key)}
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function updateReviewProgress() {
    const keys = requiredReviewKeys();
    const approved = keys.filter((key) => reviewApproved.has(key)).length;
    const progress = keys.length > 0 ? Math.round((approved / keys.length) * 100) : 0;
    const markable = canMarkCandidate(currentCandidate);

    reviewPercent.textContent = `${progress}%`;
    reviewProgress.value = progress;

    if (!markable) {
        reviewProgressText.textContent = "Hồ sơ này đã qua bước đánh dấu đủ điều kiện.";
    } else if (progress < 100) {
        reviewProgressText.textContent = `Đã xác nhận ${approved}/${keys.length} người cần xét.`;
    } else if (!reviewReachedBottom) {
        reviewProgressText.textContent = "Đã đạt 100%. Lướt xuống cuối hồ sơ để mở nút đủ điều kiện.";
    } else {
        reviewProgressText.textContent = "Đã xem đủ hồ sơ, có thể đánh dấu đủ điều kiện.";
    }

    reviewMark.classList.toggle("hidden", !markable);
    reviewMark.disabled = !markable || keys.length === 0 || progress < 100 || !reviewReachedBottom;
    reviewNominate.classList.toggle("hidden", !canNominateCandidate(currentCandidate));
}

function fillReview(candidate, profile) {
    const status = candidateStatus(candidate);

    reviewTeamName.textContent = profile.tendoibong || "Xem xét đội bóng";
    reviewTeamSub.textContent = `${candidate.tengiaidau_nguon || ""} - ${achievementLabels[candidate.danhhieu] || candidate.danhhieu || ""} - ${statusLabels[status] || "Chưa đánh dấu"}`;
    reviewTeamId.textContent = profile.iddoibong || "-";
    reviewTeamLocal.textContent = profile.diaphuong || candidate.tenkhuvuc_doi || "-";
    reviewTeamStatus.textContent = profile.trangthaidoibong || "-";
    renderCoachReview();
    renderMemberReviews();
    showReviewAlert("");
    updateReviewProgress();
}

async function openReview(achievementId, targetTournamentId) {
    const candidate = candidates.find((item) =>
        Number(item.idthanhtich) === achievementId && Number(item.idgiaidau_dich) === targetTournamentId
    );

    if (!candidate) {
        setPageMessage("Không tìm thấy đội cần xem xét.");
        return;
    }

    setPageMessage("");
    try {
        const params = new URLSearchParams({
            idthanhtich: String(Number(candidate.idthanhtich)),
            idgiaidau_dich: String(Number(candidate.idgiaidau_dich)),
        });
        const payload = await apiRequest(`${eligibilityApi}/review?${params.toString()}`);
        currentCandidate = candidate;
        currentProfile = payload.data?.profile || null;
        reviewViewed.clear();
        reviewApproved.clear();
        reviewReachedBottom = false;
        reviewModalContent.scrollTop = 0;
        fillReview(candidate, currentProfile || {});
        reviewModal.classList.remove("hidden");
        reviewModal.setAttribute("aria-hidden", "false");
        requestAnimationFrame(updateReviewScrollState);
    } catch (error) {
        setPageMessage(error.message);
    }
}

function closeReview() {
    closePerson();
    reviewModal.classList.add("hidden");
    reviewModal.setAttribute("aria-hidden", "true");
    currentCandidate = null;
    currentProfile = null;
    reviewViewed.clear();
    reviewApproved.clear();
    showReviewAlert("");
}

function personData(key) {
    if (!currentProfile) return null;

    if (key === "coach") {
        return {
            title: currentProfile.huanluyenvien_hoten || currentProfile.huanluyenvien_username || "Huấn luyện viên",
            subtitle: "Huấn luyện viên quản lý đội",
            fields: [
                ["Tài khoản", currentProfile.huanluyenvien_username],
                ["Email", currentProfile.huanluyenvien_email],
                ["Số điện thoại", currentProfile.huanluyenvien_sodienthoai],
                ["Giới tính", currentProfile.huanluyenvien_gioitinh],
                ["Ngày sinh", currentProfile.huanluyenvien_ngaysinh],
                ["Quê quán", currentProfile.huanluyenvien_quequan],
                ["Địa chỉ", currentProfile.huanluyenvien_diachi],
                ["Bằng cấp", currentProfile.huanluyenvien_bangcap],
                ["Kinh nghiệm", currentProfile.huanluyenvien_kinhnghiem],
                ["Trạng thái", currentProfile.huanluyenvien_trangthai],
            ],
        };
    }

    const memberId = Number(String(key).replace("member:", ""));
    const member = activeMembers().find((item) => Number(item.idthanhvien) === memberId);
    if (!member) return null;

    return {
        title: member.hoten || member.username || "Vận động viên",
        subtitle: `VĐV ${member.mavandongvien || ""}`.trim(),
        fields: [
            ["Tài khoản", member.username],
            ["Email", member.email],
            ["Số điện thoại", member.sodienthoai],
            ["Giới tính", member.gioitinh],
            ["Ngày sinh", member.ngaysinh],
            ["Quê quán", member.quequan],
            ["Địa chỉ", member.diachi],
            ["Vị trí", member.vitri],
            ["Chiều cao", member.chieucao],
            ["Cân nặng", member.cannang],
            ["Vai trò trong đội", member.vaitrotrongdoi],
            ["Trạng thái thành viên", member.trangthaithanhvien],
            ["Trạng thái đấu giải", member.trangthaidaugiai],
        ],
    };
}

function openPerson(key) {
    const person = personData(key);
    if (!person) return;

    reviewViewed.add(key);
    renderCoachReview();
    renderMemberReviews();
    updateReviewProgress();

    personTitle.textContent = person.title;
    personSub.textContent = person.subtitle || "-";
    personDetails.innerHTML = person.fields.map(([label, value]) => `
        <div>
            <dt>${escapeHtml(label)}</dt>
            <dd>${escapeHtml(value || "-")}</dd>
        </div>
    `).join("");
    personModal.classList.remove("hidden");
    personModal.setAttribute("aria-hidden", "false");
}

function closePerson() {
    personModal.classList.add("hidden");
    personModal.setAttribute("aria-hidden", "true");
}

function updateReviewScrollState() {
    if (reviewModal.classList.contains("hidden")) return;

    const bottomGap = reviewModalContent.scrollHeight - reviewModalContent.scrollTop - reviewModalContent.clientHeight;
    if (bottomGap <= 8) {
        reviewReachedBottom = true;
    }
    updateReviewProgress();
}

async function markEligible() {
    if (!currentCandidate || !currentProfile) return;

    const note = window.prompt("Ghi chú xét đủ điều kiện", "Đã xem HLV và toàn bộ VĐV đang tham gia của đội.") || "";
    const reviewedMemberIds = activeMembers()
        .map((item) => Number(item.idthanhvien))
        .filter((id) => reviewApproved.has(`member:${id}`));

    await apiRequest(`${eligibilityApi}/mark`, {
        method: "POST",
        body: JSON.stringify({
            idthanhtich: Number(currentCandidate.idthanhtich),
            idgiaidau_dich: Number(currentCandidate.idgiaidau_dich),
            ghichu: note,
            reviewed_coach: reviewApproved.has("coach"),
            reviewed_member_ids: reviewedMemberIds,
        }),
    });

    closeReview();
    setPageMessage("Đã đánh dấu đủ điều kiện.", true);
    await loadData();
}

async function nominate(proposalId) {
    const note = window.prompt("Ghi chú gửi BTC cấp cao hơn", "Đề cử đội đủ điều kiện tham gia giải cấp cao hơn.") || "";

    await apiRequest(`${eligibilityApi}/${proposalId}/nominate`, {
        method: "POST",
        body: JSON.stringify({ ghichu: note }),
    });
    setPageMessage("Đã gửi đề cử.", true);
    await loadData();
}

async function nominateCurrent() {
    if (!currentCandidate || !canNominateCandidate(currentCandidate)) return;

    await nominate(Number(currentCandidate.iddecu));
    closeReview();
}

async function approve(proposalId) {
    const note = window.prompt("Ghi chú xác nhận đề cử", "Đội hợp lệ, xác nhận suất tham gia cấp cao hơn.") || "";

    await apiRequest(`${eligibilityApi}/${proposalId}/approve`, {
        method: "POST",
        body: JSON.stringify({ ghichu: note }),
    });
    setPageMessage("Đã xác nhận đề cử.", true);
    await loadData();
}

async function reject(proposalId) {
    const reason = window.prompt("Lý do từ chối đề cử");
    if (reason === null || reason.trim() === "") return;

    await apiRequest(`${eligibilityApi}/${proposalId}/reject`, {
        method: "POST",
        body: JSON.stringify({ lydo: reason.trim() }),
    });
    setPageMessage("Đã từ chối đề cử.", true);
    await loadData();
}

async function handleCandidateAction(button) {
    try {
        if (button.dataset.action === "view") {
            await openReview(Number(button.dataset.achievementId), Number(button.dataset.targetId));
        }
    } catch (error) {
        setPageMessage(error.message);
    }
}

async function handleIncomingAction(button) {
    try {
        const action = button.dataset.action;
        if (action === "approve") {
            await approve(Number(button.dataset.id));
        } else if (action === "reject") {
            await reject(Number(button.dataset.id));
        }
    } catch (error) {
        setPageMessage(error.message);
    }
}

candidateBody.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-action]");
    if (button) handleCandidateAction(button);
});

incomingBody.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-action]");
    if (button) handleIncomingAction(button);
});

reviewModal.addEventListener("click", (event) => {
    if (event.target === reviewModal) {
        closeReview();
        return;
    }

    const personButton = event.target.closest("button[data-person-key]");
    if (personButton) {
        openPerson(personButton.dataset.personKey);
    }
});

reviewModal.addEventListener("change", (event) => {
    const checkbox = event.target.closest("input[data-review-key]");
    if (!checkbox) return;

    const key = checkbox.dataset.reviewKey;
    if (checkbox.checked) {
        reviewApproved.add(key);
    } else {
        reviewApproved.delete(key);
    }

    renderCoachReview();
    renderMemberReviews();
    updateReviewProgress();
});

personModal.addEventListener("click", (event) => {
    if (event.target === personModal) {
        closePerson();
    }
});

reviewClose.addEventListener("click", closeReview);
reviewCloseBtn.addEventListener("click", closeReview);
personClose.addEventListener("click", closePerson);
personCloseBtn.addEventListener("click", closePerson);
reviewMark.addEventListener("click", async () => {
    try {
        reviewMark.disabled = true;
        await markEligible();
    } catch (error) {
        showReviewAlert(error.message);
        updateReviewProgress();
    }
});
reviewNominate.addEventListener("click", async () => {
    try {
        reviewNominate.disabled = true;
        await nominateCurrent();
    } catch (error) {
        showReviewAlert(error.message);
    } finally {
        reviewNominate.disabled = false;
    }
});
reviewModalContent.addEventListener("scroll", updateReviewScrollState);

btnRefresh.addEventListener("click", loadData);
sourceTournamentFilter.addEventListener("change", loadData);
achievementFilter.addEventListener("change", loadData);
q.addEventListener("input", () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadData, 250);
});

document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;

    if (!personModal.classList.contains("hidden")) {
        closePerson();
    } else if (!reviewModal.classList.contains("hidden")) {
        closeReview();
    }
});

loadData();
