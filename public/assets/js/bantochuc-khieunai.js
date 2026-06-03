(function () {
    const root = document.querySelector(".organizer-complaints");

    if (!root) {
        return;
    }

    const complaintsApi = root.dataset.complaintsApi || "/api/organizer/complaints";
    const tournamentsApi = root.dataset.tournamentsApi || "/api/organizer/tournaments";

    const tbody = document.getElementById("tbody");
    const q = document.getElementById("q");
    const tournamentFilter = document.getElementById("tournamentFilter");
    const statusFilter = document.getElementById("statusFilter");
    const fromDate = document.getElementById("fromDate");
    const toDate = document.getElementById("toDate");
    const btnRefresh = document.getElementById("btnRefresh");
    const pageMessage = document.getElementById("pageMessage");

    const sNew = document.getElementById("sNew");
    const sPending = document.getElementById("sPending");
    const sDone = document.getElementById("sDone");

    const detailModal = document.getElementById("detailModal");
    const mClose = document.getElementById("m_close");
    const btnClose = document.getElementById("btnClose");
    const mAlert = document.getElementById("m_alert");
    const mTitle = document.getElementById("m_title");
    const mSub = document.getElementById("m_sub");
    const mId = document.getElementById("m_id");
    const mStatus = document.getElementById("m_status");
    const mTournament = document.getElementById("m_tournament");
    const mRelated = document.getElementById("m_related");
    const mSender = document.getElementById("m_sender");
    const mCreated = document.getElementById("m_created");
    const mContent = document.getElementById("m_content");
    const mEvidence = document.getElementById("m_evidence");
    const mNote = document.getElementById("m_note");
    const mResultBlock = document.getElementById("m_result_block");
    const mScore1 = document.getElementById("m_score1");
    const mScore2 = document.getElementById("m_score2");
    const mSets1 = document.getElementById("m_sets1");
    const mSets2 = document.getElementById("m_sets2");
    const mWinner = document.getElementById("m_winner");
    const mScore1Label = document.getElementById("m_score1_label");
    const mScore2Label = document.getElementById("m_score2_label");
    const mSets1Label = document.getElementById("m_sets1_label");
    const mSets2Label = document.getElementById("m_sets2_label");

    const btnAccept = document.getElementById("btnAccept");
    const btnReject = document.getElementById("btnReject");
    const btnResolved = document.getElementById("btnResolved");
    const btnNoAction = document.getElementById("btnNoAction");

    const statusMap = {
        CHO_TIEP_NHAN: ["new", "Chờ tiếp nhận"],
        DANG_XU_LY: ["proc", "Chờ xử lý"],
        DA_XU_LY: ["done", "Đã xử lý"],
        TU_CHOI: ["bad", "Từ chối tiếp nhận"],
        KHONG_XU_LY: ["gray", "Không xử lý"],
    };

    let complaints = [];
    let current = null;

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
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

    function statusLabel(status) {
        return statusMap[status]?.[1] || status || "-";
    }

    function badge(status) {
        return statusMap[status] || ["gray", status || "-"];
    }

    function senderName(item) {
        const username = item.nguoigui_username || "";
        const fullName = item.nguoigui_hoten || "";

        if (username && fullName) {
            return `${username} (${fullName})`;
        }

        return username || fullName || `#${item.idnguoigui}`;
    }

    function relatedText(item) {
        if (item.idtrandau) {
            const teams = [item.trandau_doi1, item.trandau_doi2].filter(Boolean).join(" vs ");
            const round = item.trandau_vong ? ` - ${item.trandau_vong}` : "";
            const code = item.ma_tran || `#${item.idtrandau}`;
            return `Trận ${code}${teams ? ` (${teams})` : ""}${round}`;
        }

        return "Giải đấu";
    }

    function responseData(payload) {
        return payload && Object.prototype.hasOwnProperty.call(payload, "data") ? payload.data : null;
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

    function updateStats(meta) {
        const stats = meta?.stats || {};
        sNew.textContent = String(stats.CHO_TIEP_NHAN || 0);
        sPending.textContent = String(stats.DANG_XU_LY || 0);
        sDone.textContent = String(stats.DA_XU_LY || 0);
    }

    async function loadComplaints() {
        showPageMessage("Đang tải dữ liệu...");

        try {
            const payload = await requestJson(apiUrl(complaintsApi, {
                q: q.value.trim(),
                tournament_id: tournamentFilter.value,
                status: statusFilter.value,
                from: fromDate.value,
                to: toDate.value,
            }));

            complaints = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            updateStats(payload.meta);
            render();
            showPageMessage("");
        } catch (error) {
            complaints = [];
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Không thể tải danh sách khiếu nại.</td></tr>';
            showPageMessage(error.message || "Không thể tải danh sách khiếu nại.", true);
        }
    }

    function render() {
        if (complaints.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty">Không có khiếu nại phù hợp.</td></tr>';
            return;
        }

        tbody.innerHTML = complaints.map((item) => {
            const [className, label] = badge(item.trangthai);

            return `
                <tr>
                    <td>${escapeHtml(item.idkhieunai)}</td>
                    <td>${escapeHtml(item.ngaygui)}</td>
                    <td>${escapeHtml(item.tengiaidau)}</td>
                    <td>${escapeHtml(relatedText(item))}</td>
                    <td>${escapeHtml(senderName(item))}</td>
                    <td>${escapeHtml(item.tieude)}</td>
                    <td><span class="badge ${className}">${escapeHtml(label)}</span></td>
                    <td><button class="btn" type="button" data-action="detail" data-id="${escapeHtml(item.idkhieunai)}">Xem</button></td>
                </tr>
            `;
        }).join("");
    }

    async function openDetail(id) {
        hideAlert();

        try {
            const payload = await requestJson(`${complaintsApi}/${id}`);
            current = responseData(payload);
        } catch (error) {
            showPageMessage(error.message || "Không thể tải chi tiết khiếu nại.", true);
            return;
        }

        mTitle.textContent = current.tieude || "Chi tiết khiếu nại";
        mSub.textContent = `Gửi lúc ${current.ngaygui || "-"} - ${statusLabel(current.trangthai)}`;
        mId.value = current.idkhieunai || "";
        mStatus.value = statusLabel(current.trangthai);
        mTournament.value = current.tengiaidau || "";
        mRelated.value = relatedText(current);
        mSender.value = senderName(current);
        mCreated.value = current.ngaygui || "";
        mContent.value = current.noidung || "";
        mNote.value = "";

        if (current.minhchung) {
            mEvidence.textContent = current.minhchung;
            mEvidence.href = current.minhchung;
        } else {
            mEvidence.textContent = "—";
            mEvidence.href = "#";
        }

        const status = current.trangthai;
        setupResultEditor();
        btnAccept.disabled = status !== "CHO_TIEP_NHAN";
        btnReject.disabled = status !== "CHO_TIEP_NHAN";
        btnResolved.disabled = status !== "DANG_XU_LY";
        btnNoAction.disabled = status !== "DANG_XU_LY";

        detailModal.classList.remove("hidden");
    }

    function closeModal() {
        detailModal.classList.add("hidden");
        current = null;
    }

    function setupResultEditor() {
        if (!current?.idketqua) {
            mResultBlock.classList.add("hidden");
            return;
        }

        mResultBlock.classList.remove("hidden");
        const teamOne = current.trandau_doi1 || "Đội 1";
        const teamTwo = current.trandau_doi2 || "Đội 2";
        mScore1Label.textContent = `Điểm ${teamOne}`;
        mScore2Label.textContent = `Điểm ${teamTwo}`;
        mSets1Label.textContent = `Số set ${teamOne}`;
        mSets2Label.textContent = `Số set ${teamTwo}`;
        mScore1.value = current.diemdoi1 ?? "";
        mScore2.value = current.diemdoi2 ?? "";
        mSets1.value = current.sosetdoi1 ?? "";
        mSets2.value = current.sosetdoi2 ?? "";
        mWinner.innerHTML = `
            <option value="${escapeHtml(current.iddoibong1 || "")}">${escapeHtml(teamOne)}</option>
            <option value="${escapeHtml(current.iddoibong2 || "")}">${escapeHtml(teamTwo)}</option>
        `;
        mWinner.value = current.iddoithang || current.iddoibong1 || "";
    }

    function noteValue(required) {
        const note = mNote.value.trim();

        if (required && !note) {
            showAlert("Vui lòng nhập ghi chú cho thao tác này.");
            return null;
        }

        return note;
    }

    function scorePayloadForResolve() {
        if (!current?.idketqua) {
            return {};
        }

        const payload = {
            diemdoi1: mScore1.value.trim(),
            diemdoi2: mScore2.value.trim(),
            sosetdoi1: mSets1.value.trim(),
            sosetdoi2: mSets2.value.trim(),
            iddoithang: mWinner.value,
        };

        if (!payload.diemdoi1 || !payload.diemdoi2 || !payload.sosetdoi1 || !payload.sosetdoi2 || !payload.iddoithang) {
            showAlert("Vui lòng nhập đủ tỷ số, số set và đội thắng trước khi xác nhận xử lý.");
            return null;
        }

        return payload;
    }

    function setActionDisabled(disabled) {
        btnAccept.disabled = disabled || current?.trangthai !== "CHO_TIEP_NHAN";
        btnReject.disabled = disabled || current?.trangthai !== "CHO_TIEP_NHAN";
        btnResolved.disabled = disabled || current?.trangthai !== "DANG_XU_LY";
        btnNoAction.disabled = disabled || current?.trangthai !== "DANG_XU_LY";
    }

    async function submitAction(action, endpoint, requiredNote) {
        if (!current) {
            return;
        }

        hideAlert();
        const note = noteValue(requiredNote);

        if (note === null) {
            return;
        }

        setActionDisabled(true);

        try {
            const body = { note, reason: note };
            if (endpoint === "resolve") {
                const scorePayload = scorePayloadForResolve();
                if (scorePayload === null) {
                    setActionDisabled(false);
                    return;
                }
                Object.assign(body, scorePayload);
            }

            await requestJson(`${complaintsApi}/${current.idkhieunai}/${endpoint}`, {
                method: "POST",
                body: JSON.stringify(body),
            });
            closeModal();
            await loadComplaints();
            showPageMessage(action);
        } catch (error) {
            showAlert(error.message || "Không thể cập nhật khiếu nại.");
            setActionDisabled(false);
        }
    }

    btnAccept.addEventListener("click", () => {
        if (current?.trangthai === "CHO_TIEP_NHAN") {
            submitAction("Tiếp nhận khiếu nại thành công.", "receive", true);
        }
    });

    btnReject.addEventListener("click", () => {
        if (current?.trangthai === "CHO_TIEP_NHAN") {
            submitAction("Từ chối tiếp nhận khiếu nại thành công.", "reject", true);
        }
    });

    btnResolved.addEventListener("click", () => {
        if (current?.trangthai === "DANG_XU_LY") {
            submitAction("Ghi nhận khiếu nại đã xử lý thành công.", "resolve", true);
        }
    });

    btnNoAction.addEventListener("click", () => {
        if (current?.trangthai === "DANG_XU_LY") {
            submitAction("Ghi nhận khiếu nại không xử lý thành công.", "no-process", true);
        }
    });

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("[data-action='detail']");

        if (button) {
            openDetail(button.dataset.id);
        }
    });

    mClose.addEventListener("click", closeModal);
    btnClose.addEventListener("click", closeModal);
    btnRefresh.addEventListener("click", loadComplaints);
    q.addEventListener("input", loadComplaints);
    tournamentFilter.addEventListener("change", loadComplaints);
    statusFilter.addEventListener("change", loadComplaints);
    fromDate.addEventListener("change", loadComplaints);
    toDate.addEventListener("change", loadComplaints);

    loadTournaments().then(loadComplaints);
})();
