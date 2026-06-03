(function () {
    const page = document.querySelector(".athlete-personal-stats");
    if (!page) return;

    const UI = window.AthleteUI;
    const message = document.getElementById("pageMessage");
    const detail = document.getElementById("detail");

    function number(value) {
        return Number(value || 0);
    }

    async function load() {
        try {
            const payload = await UI.requestJson(page.dataset.statsApi);
            const rows = Array.isArray(payload.data) ? payload.data : [];
            const summary = payload.meta?.summary || {};
            const totalMatches = summary.total_matches ?? new Set(rows.map((row) => row.idtrandau)).size;
            const totalPoints = summary.total_points ?? rows.reduce((sum, row) => sum + number(row.sodiem), 0);
            const totalServes = summary.total_serves ?? rows.reduce((sum, row) => sum + number(row.solanphatbong), 0);
            const totalScores = summary.total_scores ?? rows.reduce((sum, row) => sum + number(row.solanghidiem), 0);

            document.getElementById("matches").textContent = totalMatches;
            document.getElementById("points").textContent = totalPoints;
            document.getElementById("serves").textContent = totalServes;
            document.getElementById("scores").textContent = totalScores;

            detail.innerHTML = rows.length === 0
                ? `<tr><td colspan="6" class="empty">Chưa có thống kê cá nhân.</td></tr>`
                : rows.map((row) => `
                    <tr>
                        <td>${UI.escapeHtml(row.tengiaidau || "-")}</td>
                        <td>${UI.escapeHtml(row.doi1 || "-")} vs ${UI.escapeHtml(row.doi2 || "-")}</td>
                        <td>${UI.escapeHtml(row.sodiem ?? 0)}</td>
                        <td>${UI.escapeHtml(row.solanphatbong ?? 0)}</td>
                        <td>${UI.escapeHtml(row.solanchanbong ?? 0)}</td>
                        <td>${UI.escapeHtml(row.solanghidiem ?? 0)}</td>
                    </tr>
                `).join("");
        } catch (error) {
            UI.showMessage(message, UI.errorsText(error), true);
            detail.innerHTML = `<tr><td colspan="6" class="empty">Không thể tải thống kê.</td></tr>`;
        }
    }

    load();
})();
