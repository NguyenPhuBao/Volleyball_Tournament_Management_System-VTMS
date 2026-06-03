const root = document.querySelector(".admin-users");
const accountsApi = root?.dataset.accountsApi || "/api/admin/accounts";
const rolesApi = root?.dataset.rolesApi || "/api/admin/roles";

let users = [];
let editingId = null;
let searchTimer = null;

const table = document.getElementById("userTable");
const modal = document.getElementById("userModal");
const modalTitle = document.getElementById("modalTitle");
const modalMessage = document.getElementById("modalMessage");
const pageMessage = document.getElementById("pageMessage");
const passwordHint = document.getElementById("passwordHint");
const saveButton = document.getElementById("btnSave");

const fields = {
    username: document.getElementById("username"),
    email: document.getElementById("email"),
    password: document.getElementById("password"),
    role: document.getElementById("role"),
    status: document.getElementById("status"),
    filterRole: document.getElementById("filterRole"),
    search: document.getElementById("searchInput"),
};

const roleLabels = {
    ADMIN: "ADMIN",
    BAN_TO_CHUC: "BAN TỔ CHỨC",
    TRONG_TAI: "TRỌNG TÀI",
    HUAN_LUYEN_VIEN: "HLV",
    VAN_DONG_VIEN: "VĐV",
};

const statusLabels = {
    HOAT_DONG: "Hoạt động",
    CHUA_KICH_HOAT: "Chưa kích hoạt",
    CHO_DUYET: "Chờ duyệt",
    TAM_KHOA: "Tạm khóa",
    DA_HUY: "Đã hủy",
};

function accountId(user) {
    return Number(user.idtaikhoan || user.id);
}

function accountStatus(user) {
    return user.trangthai || user.status || "";
}

function accountRole(user) {
    return user.role || user.namerole || "";
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function statusClass(status) {
    if (status === "HOAT_DONG") {
        return "active";
    }

    if (status === "CHO_DUYET" || status === "CHUA_KICH_HOAT") {
        return "pending";
    }

    if (status === "DA_HUY") {
        return "deleted";
    }

    return "locked";
}

function setPageMessage(message, success = false) {
    pageMessage.textContent = message || "";
    pageMessage.classList.toggle("success", success);
}

function setModalMessage(message) {
    modalMessage.textContent = message || "";
}

function render() {
    if (users.length === 0) {
        table.innerHTML = '<tr><td colspan="5" class="empty">Không có tài khoản phù hợp.</td></tr>';
        return;
    }

    table.innerHTML = users.map((user) => {
        const id = accountId(user);
        const status = accountStatus(user);
        const role = accountRole(user);

        return `
            <tr>
                <td>${escapeHtml(user.username)}</td>
                <td>${escapeHtml(user.email)}</td>
                <td>${escapeHtml(roleLabels[role] || role)}</td>
                <td><span class="status-pill ${statusClass(status)}">${escapeHtml(statusLabels[status] || status)}</span></td>
                <td>
                    <div class="table-actions">
                        <button class="btn" type="button" data-action="edit" data-id="${id}">Sửa</button>
                        <button class="btn danger" type="button" data-action="delete" data-id="${id}">Xóa</button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
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

async function loadRoles() {
    try {
        const payload = await apiRequest(rolesApi);
        const roles = payload.data || [];
        const options = roles.map((role) => {
            const value = role.namerole;
            return `<option value="${escapeHtml(value)}">${escapeHtml(roleLabels[value] || value)}</option>`;
        }).join("");

        if (options !== "") {
            fields.role.innerHTML = options;
            fields.filterRole.innerHTML = '<option value="">-- Tất cả vai trò --</option>' + options;
        }
    } catch (error) {
        setPageMessage(error.message);
    }
}

async function loadUsers() {
    const params = new URLSearchParams();
    const query = fields.search.value.trim();
    const role = fields.filterRole.value;

    if (query !== "") {
        params.set("q", query);
    }

    if (role !== "") {
        params.set("role", role);
    }

    table.innerHTML = '<tr><td colspan="5" class="empty">Đang tải dữ liệu...</td></tr>';
    setPageMessage("");

    try {
        const url = params.toString() === "" ? accountsApi : `${accountsApi}?${params.toString()}`;
        const payload = await apiRequest(url);
        users = payload.data || [];
        render();
    } catch (error) {
        users = [];
        render();
        setPageMessage(error.message);
    }
}

function resetForm() {
    fields.username.value = "";
    fields.email.value = "";
    fields.password.value = "";
    fields.role.value = "ADMIN";
    fields.status.value = "HOAT_DONG";
    setModalMessage("");
}

function openModal(isEdit = false) {
    modal.classList.remove("hidden");
    modal.setAttribute("aria-hidden", "false");
    modalTitle.textContent = isEdit ? "Sửa tài khoản" : "Thêm tài khoản";
    passwordHint.classList.toggle("visible", isEdit);
    fields.password.placeholder = isEdit ? "Để trống nếu không đổi" : "";
    fields.username.focus();
}

function closeModal() {
    modal.classList.add("hidden");
    modal.setAttribute("aria-hidden", "true");
    editingId = null;
    resetForm();
}

function fillForm(user) {
    fields.username.value = user.username || "";
    fields.email.value = user.email || "";
    fields.password.value = "";
    fields.role.value = accountRole(user);
    fields.status.value = accountStatus(user);
}

async function editUser(id) {
    setPageMessage("");

    try {
        const payload = await apiRequest(`${accountsApi}/${id}`);
        const user = payload.data;
        editingId = id;
        fillForm(user);
        openModal(true);
    } catch (error) {
        setPageMessage(error.message);
    }
}

async function deleteUser(id) {
    const user = users.find((item) => accountId(item) === id);
    const username = user?.username || `#${id}`;

    if (!confirm(`Bạn chắc chắn muốn xóa tài khoản ${username}?`)) {
        return;
    }

    setPageMessage("");

    try {
        await apiRequest(`${accountsApi}/${id}`, { method: "DELETE" });
        setPageMessage("Xóa tài khoản thành công.", true);
        await loadUsers();
    } catch (error) {
        setPageMessage(error.message);
    }
}

function formPayload() {
    const payload = {
        username: fields.username.value.trim(),
        email: fields.email.value.trim(),
        role: fields.role.value,
        status: fields.status.value,
    };

    if (fields.password.value !== "") {
        payload.password = fields.password.value;
    }

    return payload;
}

async function saveUser() {
    setModalMessage("");
    saveButton.disabled = true;

    try {
        const payload = formPayload();
        const isEdit = editingId !== null;
        const url = isEdit ? `${accountsApi}/${editingId}` : accountsApi;
        const method = isEdit ? "PATCH" : "POST";

        await apiRequest(url, {
            method,
            body: JSON.stringify(payload),
        });

        closeModal();
        setPageMessage(isEdit ? "Cập nhật tài khoản thành công." : "Thêm tài khoản thành công.", true);
        await loadUsers();
    } catch (error) {
        setModalMessage(error.message);
    } finally {
        saveButton.disabled = false;
    }
}

document.getElementById("btnAdd").addEventListener("click", () => {
    editingId = null;
    resetForm();
    openModal(false);
});

document.getElementById("btnCancel").addEventListener("click", closeModal);
saveButton.addEventListener("click", saveUser);

modal.addEventListener("click", (event) => {
    if (event.target === modal) {
        closeModal();
    }
});

table.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-action]");

    if (!button) {
        return;
    }

    const id = Number(button.dataset.id);

    if (button.dataset.action === "edit") {
        editUser(id);
    }

    if (button.dataset.action === "delete") {
        deleteUser(id);
    }
});

fields.filterRole.addEventListener("change", loadUsers);
fields.search.addEventListener("input", () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadUsers, 250);
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.classList.contains("hidden")) {
        closeModal();
    }
});

loadRoles().finally(loadUsers);
