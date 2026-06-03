const root = document.querySelector(".admin-logs");
const logsApi = root?.dataset.logsApi || "/api/admin/system-logs";
const optionsApi = root?.dataset.optionsApi || "/api/admin/system-logs/options";

let logs = [];
let page = 1;
let totalPages = 1;
let searchTimer = null;

const perPage = 20;
const tbody = document.getElementById("tbody");
const pageMessage = document.getElementById("pageMessage");
const pageInfo = document.getElementById("pageInfo");
const prevPage = document.getElementById("prevPage");
const nextPage = document.getElementById("nextPage");

const fields = {
    q: document.getElementById("q"),
    userFilter: document.getElementById("userFilter"),
    fromDate: document.getElementById("fromDate"),
    toDate: document.getElementById("toDate"),
};

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function actorLabel(log) {
    const username = log.username || "";
    const fullName = (log.hoten || `${log.hodem || ""} ${log.ten || ""}`).trim();

    return {
        primary: username || fullName || "Hệ thống",
        secondary: fullName && fullName !== username ? fullName : (log.role || ""),
    };
}

function setPageMessage(message, success = false) {
    pageMessage.textContent = message || "";
    pageMessage.classList.toggle("success", success);
}

function render() {
    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty">Không có nhật ký phù hợp.</td></tr>';
        return;
    }

    tbody.innerHTML = logs.map((log) => {
        const actor = actorLabel(log);

        return `
            <tr>
                <td>${escapeHtml(log.thoigian)}</td>
                <td>
                    <div class="actor-name">
                        <strong>${escapeHtml(actor.primary)}</strong>
                        ${actor.secondary ? `<span>${escapeHtml(actor.secondary)}</span>` : ""}
                    </div>
                </td>
                <td>${escapeHtml(log.hanhdong)}</td>
                <td>${escapeHtml(log.bangtacdong)}</td>
                <td><span class="target-id">${escapeHtml(log.iddoituong ?? "")}</span></td>
                <td>${escapeHtml(log.ipaddress ?? "")}</td>
                <td>${escapeHtml(log.ghichu ?? "")}</td>
            </tr>
        `;
    }).join("");
}

function updatePagination(meta = {}) {
    const pagination = meta.pagination || {};
    page = Number(pagination.page || page || 1);
    totalPages = Math.max(Number(pagination.total_pages || 1), 1);
    const total = Number(pagination.total || 0);

    pageInfo.textContent = `Trang ${page} / ${totalPages} - ${total} dòng`;
    prevPage.disabled = page <= 1;
    nextPage.disabled = page >= totalPages;
}

async function apiRequest(url) {
    const response = await fetch(url, {
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
        },
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || payload.success === false) {
        throw new Error(payload.message || "Yêu cầu không thành công.");
    }

    return payload;
}

function buildLogsUrl() {
    const params = new URLSearchParams();
    const query = fields.q.value.trim();

    if (query !== "") {
        params.set("q", query);
    }

    if (fields.userFilter.value !== "") {
        params.set("idtaikhoan", fields.userFilter.value);
    }

    if (fields.fromDate.value !== "") {
        params.set("from", fields.fromDate.value);
    }

    if (fields.toDate.value !== "") {
        params.set("to", fields.toDate.value);
    }

    params.set("page", String(page));
    params.set("per_page", String(perPage));

    return `${logsApi}?${params.toString()}`;
}

async function loadOptions() {
    try {
        const payload = await apiRequest(optionsApi);
        const actors = payload.data?.actors || [];
        const options = actors.map((actor) => {
            const id = actor.idtaikhoan;
            const username = actor.username || `#${id}`;
            const fullName = (actor.hoten || "").trim();
            const label = fullName !== "" ? `${username} - ${fullName}` : username;

            return `<option value="${escapeHtml(id)}">${escapeHtml(label)}</option>`;
        }).join("");

        fields.userFilter.innerHTML = '<option value="">Tất cả người dùng</option>' + options;
    } catch (error) {
        setPageMessage(error.message);
    }
}

async function loadLogs() {
    tbody.innerHTML = '<tr><td colspan="7" class="empty">Đang tải dữ liệu...</td></tr>';
    setPageMessage("");

    try {
        const payload = await apiRequest(buildLogsUrl());
        logs = payload.data || [];
        render();
        updatePagination(payload.meta);
    } catch (error) {
        logs = [];
        render();
        updatePagination();
        setPageMessage(error.message);
    }
}

function reloadFromFirstPage() {
    page = 1;
    loadLogs();
}

document.getElementById("btnReset").addEventListener("click", () => {
    fields.q.value = "";
    fields.userFilter.value = "";
    fields.fromDate.value = "";
    fields.toDate.value = "";
    reloadFromFirstPage();
});

fields.q.addEventListener("input", () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(reloadFromFirstPage, 250);
});

fields.userFilter.addEventListener("change", reloadFromFirstPage);
fields.fromDate.addEventListener("change", reloadFromFirstPage);
fields.toDate.addEventListener("change", reloadFromFirstPage);

prevPage.addEventListener("click", () => {
    if (page <= 1) {
        return;
    }

    page -= 1;
    loadLogs();
});

nextPage.addEventListener("click", () => {
    if (page >= totalPages) {
        return;
    }

    page += 1;
    loadLogs();
});

loadOptions().finally(loadLogs);
