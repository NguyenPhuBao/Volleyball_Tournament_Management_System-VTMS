(function () {
    const page = document.querySelector(".athlete-personal-schedule");
    if (!page) return;

    const UI = window.AthleteUI;
    const tbody = document.getElementById("tbody");
    const empty = document.getElementById("empty");
    const table = document.getElementById("scheduleTable");

    async function load() {
        try {
            const payload = await UI.requestJson(page.dataset.scheduleApi);
            const matches = Array.isArray(payload.data) ? payload.data : [];
            empty.classList.toggle("hidden", matches.length > 0);
            table.classList.toggle("hidden", matches.length === 0);

            tbody.innerHTML = matches.length === 0
                ? ""
                : matches.map((match) => `
                    <tr>
                        <td>${UI.escapeHtml(UI.formatDateTime(match.thoigianbatdau))}</td>
                        <td>${UI.escapeHtml(match.tengiaidau || "-")}</td>
                        <td>${UI.escapeHtml(match.doi1 || "-")} vs ${UI.escapeHtml(match.doi2 || "-")}</td>
                        <td>${UI.escapeHtml(match.tensandau || "-")}</td>
                        <td>${UI.escapeHtml(match.vongdau || match.tenbang || "-")}</td>
                        <td>${UI.badgeHtml(match.trangthai)}</td>
                    </tr>
                `).join("");
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="6" class="empty">${UI.escapeHtml(UI.errorsText(error))}</td></tr>`;
        }
    }

    load();
})();
