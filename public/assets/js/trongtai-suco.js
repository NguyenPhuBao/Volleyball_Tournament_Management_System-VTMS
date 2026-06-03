(function () {
    const root = document.querySelector(".referee-incidents");

    if (!root) {
        return;
    }

    const reportsApi = root.dataset.reportsApi || "/api/trongtai/incident-reports";
    const matchesApi = root.dataset.matchesApi || "/api/trongtai/reportable-matches";

    const tbody = document.getElementById("tbody");
    const q = document.getElementById("q");
    const matchFilter = document.getElementById("matchFilter");
    const statusFilter = document.getElementById("statusFilter");
    const fromDate = document.getElementById("fromDate");
    const toDate = document.getElementById("toDate");
    const btnRefresh = document.getElementById("btnRefresh");
    const pageAlert = document.getElementById("pageAlert");

    const sTotal = document.getElementById("sTotal");
    const sNew = document.getElementById("sNew");
    const sDone = document.getElementById("sDone");

    const btnCreate = document.getElementById("btnCreate");
    const reportModal = document.getElementById("reportModal");
    const mClose = document.getElementById("m_close");
    const mCancel = document.getElementById("m_cancel");
    const mSubmit = document.getElementById("m_submit");
    const mAlert = document.getElementById("m_alert");
    const mMatch = document.getElementById("m_match");
    const mTitle = document.getElementById("m_title");
    const mContent = document.getElementById("m_content");
    const mEvidence = document.getElementById("m_evidence");

    const detailModal = document.getElementById("detailModal");
    const dClose = document.getElementById("d_close");
    const dCloseBtn = document.getElementById("d_closeBtn");
    const dSub = document.getElementById("d_sub");
    const dAlert = document.getElementById("d_alert");
    const dId = document.getElementById("d_id");
    const dStatus = document.getElementById("d_status");
    const dMatch = document.getElementById("d_match");
    const dTournament = document.getElementById("d_tournament");
    const dCreated = document.getElementById("d_created");
    const dTitle = document.getElementById("d_title");
    const dContent = document.getElementById("d_content");
    const dEvidence = document.getElementById("d_evidence");

    const statusMap = {
        DA_GUI: ["new", "Đã gửi"],
        DA_TIEP_NHAN: ["proc", "Đã tiếp nhận"],
        DA_XU_LY: ["done", "Đã xử lý"],
        TU_CHOI: ["bad", "Từ chối"],
    };

    let reports = [];
    let matches = [];

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

    function showPage(message, isSuccess = false) {
        pageAlert.textContent = message || "";
        pageAlert.classList.toggle("hidden", !message);
        pageAlert.classList.toggle("success", Boolean(isSuccess));
    }

    function showModalAlert(element, message) {
        element.textContent = message || "";
        element.classList.toggle("hidden", !message);
    }

    function formatDateTime(value) {
        if (!value) {
            return "";
        }

        return String(value).replace("T", " ").slice(0, 19);
    }

    function statusBadge(status) {
        return statusMap[status] || ["new", status || "-"];
    }

    function matchName(item) {
        return `${item.doi1 || "-"} vs ${item.doi2 || "-"}`;
    }

    function matchLabel(item) {
        const time = item.thoigianbatdau ? ` • ${formatDateTime(item.thoigianbatdau)}` : "";
        return `#${item.idtrandau} • ${matchName(item)}${time}`;
    }

    function reportMatchLabel(item) {
        return `#${item.idtrandau} • ${matchName(item)}`;
    }

    function renderEvidence(value) {
        if (!value) {
            return "—";
        }

        const raw = String(value);

        try {
            const url = new URL(raw);

            if (url.protocol === "http:" || url.protocol === "https:") {
                const safe = escapeHtml(url.toString());
                return `<a href="${safe}" target="_blank" rel="noopener">Link</a>`;
            }
        } catch (error) {
            // Fall through and render the evidence as plain text.
        }

        return escapeHtml(raw);
    }

    function updateStats(meta) {
        const stats = meta?.stats || {};
        sTotal.textContent = String(reports.length);
        sNew.textContent = String(Number(stats.DA_GUI ?? reports.filter((item) => item.trangthai === "DA_GUI").length));
        sDone.textContent = String(Number(stats.DA_XU_LY ?? reports.filter((item) => item.trangthai === "DA_XU_LY").length));
    }

    async function loadMatches() {
        try {
            const payload = await requestJson(matchesApi);
            matches = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            const options = matches.map((item) => (
                `<option value="${escapeHtml(item.idtrandau)}">${escapeHtml(matchLabel(item))}</option>`
            )).join("");

            matchFilter.innerHTML = '<option value="">Tất cả trận đấu</option>' + options;
            mMatch.innerHTML = '<option value="">Chọn trận đấu</option>' + options;
        } catch (error) {
            matches = [];
            matchFilter.innerHTML = '<option value="">Tất cả trận đấu</option>';
            mMatch.innerHTML = '<option value="">Chọn trận đấu</option>';
            showPage(error.message || "Không thể tải danh sách trận đấu.");
        }
    }

    async function loadReports() {
        showPage("");

        try {
            const payload = await requestJson(apiUrl(reportsApi, {
                q: q.value.trim(),
                match_id: matchFilter.value,
                status: statusFilter.value,
                from: fromDate.value,
                to: toDate.value,
            }));
            reports = Array.isArray(responseData(payload)) ? responseData(payload) : [];
            updateStats(payload.meta);
            render();
        } catch (error) {
            reports = [];
            updateStats(null);
            tbody.innerHTML = '<tr><td colspan="7" class="empty">Không thể tải báo cáo sự cố.</td></tr>';
            showPage(error.message || "Không thể tải báo cáo sự cố.");
        }
    }

    function render() {
        if (reports.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty">Không có báo cáo phù hợp.</td></tr>';
            return;
        }

        tbody.innerHTML = reports.map((item) => {
            const [className, label] = statusBadge(item.trangthai);

            return `
                <tr>
                    <td>${escapeHtml(item.idbaocao)}</td>
                    <td>${escapeHtml(reportMatchLabel(item))}</td>
                    <td>${escapeHtml(item.tieude || "")}</td>
                    <td><span class="badge ${className}">${escapeHtml(label)}</span></td>
                    <td>${escapeHtml(formatDateTime(item.ngaybaocao))}</td>
                    <td>${renderEvidence(item.minhchung)}</td>
                    <td><button class="btn" type="button" data-action="view" data-id="${escapeHtml(item.idbaocao)}">Xem</button></td>
                </tr>
            `;
        }).join("");
    }

    function openReportModal() {
        showModalAlert(mAlert, "");
        mMatch.value = matchFilter.value || "";
        mTitle.value = "";
        mContent.value = "";
        mEvidence.value = "";
        reportModal.classList.remove("hidden");
    }

    function closeReportModal() {
        reportModal.classList.add("hidden");
    }

    function closeDetailModal() {
        detailModal.classList.add("hidden");
    }

    function validate() {
        if (!mMatch.value) {
            return "Vui lòng chọn trận đấu liên quan.";
        }

        if (!mTitle.value.trim()) {
            return "Vui lòng nhập tiêu đề.";
        }

        if (!mContent.value.trim()) {
            return "Vui lòng nhập nội dung báo cáo.";
        }

        return null;
    }

    async function submitReport() {
        showModalAlert(mAlert, "");

        const error = validate();

        if (error) {
            showModalAlert(mAlert, error);
            return;
        }

        mSubmit.disabled = true;

        try {
            await requestJson(reportsApi, {
                method: "POST",
                body: JSON.stringify({
                    idtrandau: Number(mMatch.value),
                    tieude: mTitle.value.trim(),
                    noidung: mContent.value.trim(),
                    minhchung: mEvidence.value.trim() || null,
                }),
            });

            closeReportModal();
            await loadReports();
            showPage("Báo cáo sự cố thành công", true);
        } catch (submitError) {
            const errors = submitError.payload?.errors || {};
            const firstError = Object.values(errors)[0];
            showModalAlert(mAlert, String(firstError || submitError.message || "Không thể gửi báo cáo sự cố."));
        } finally {
            mSubmit.disabled = false;
        }
    }

    async function openDetail(reportId) {
        showModalAlert(dAlert, "");
        dId.value = reportId || "";
        dStatus.value = "";
        dMatch.value = "";
        dTournament.value = "";
        dCreated.value = "";
        dTitle.value = "";
        dContent.value = "";
        dEvidence.textContent = "—";
        dSub.textContent = "Đang tải chi tiết...";
        detailModal.classList.remove("hidden");

        try {
            const payload = await requestJson(`${reportsApi.replace(/\/+$/, "")}/${encodeURIComponent(reportId)}`);
            const detail = responseData(payload);

            if (!detail) {
                throw new Error("Không tìm thấy báo cáo sự cố.");
            }

            dId.value = detail.idbaocao || "";
            dStatus.value = statusBadge(detail.trangthai)[1];
            dMatch.value = reportMatchLabel(detail);
            dTournament.value = detail.tengiaidau || "";
            dCreated.value = formatDateTime(detail.ngaybaocao);
            dTitle.value = detail.tieude || "";
            dContent.value = detail.noidung || "";
            dSub.textContent = `Báo cáo #${detail.idbaocao} • Trận #${detail.idtrandau} • ${formatDateTime(detail.ngaybaocao)}`;

            if (detail.minhchung) {
                dEvidence.innerHTML = renderEvidence(detail.minhchung);
            } else {
                dEvidence.textContent = "—";
            }
        } catch (error) {
            showModalAlert(dAlert, error.message || "Không thể tải chi tiết báo cáo sự cố.");
        }
    }

    tbody.addEventListener("click", (event) => {
        const button = event.target.closest("button[data-action='view']");

        if (button) {
            openDetail(button.dataset.id);
        }
    });

    btnCreate.addEventListener("click", openReportModal);
    mClose.addEventListener("click", closeReportModal);
    mCancel.addEventListener("click", closeReportModal);
    mSubmit.addEventListener("click", submitReport);
    dClose.addEventListener("click", closeDetailModal);
    dCloseBtn.addEventListener("click", closeDetailModal);
    btnRefresh.addEventListener("click", () => {
        loadMatches();
        loadReports();
    });
    q.addEventListener("input", loadReports);
    matchFilter.addEventListener("change", loadReports);
    statusFilter.addEventListener("change", loadReports);
    fromDate.addEventListener("change", loadReports);
    toDate.addEventListener("change", loadReports);

    Promise.all([loadMatches(), loadReports()]);
})();
