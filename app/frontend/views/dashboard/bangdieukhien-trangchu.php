<?php

$dashboard = is_array($dashboardData ?? null) ? $dashboardData : [];
$hero = is_array($dashboard['hero'] ?? null) ? $dashboard['hero'] : [];
$stats = is_array($dashboard['stats'] ?? null) ? $dashboard['stats'] : [];
$mainPanel = is_array($dashboard['main_panel'] ?? null) ? $dashboard['main_panel'] : [];
$sidePanel = is_array($dashboard['side_panel'] ?? null) ? $dashboard['side_panel'] : [];
$actions = is_array($dashboard['actions'] ?? null) ? $dashboard['actions'] : [];
$variant = (string) ($dashboard['variant'] ?? 'admin');

$initials = static function (string $text): string {
    $text = trim($text);
    if ($text === '') {
        return 'VT';
    }

    $parts = preg_split('/\s+/', preg_replace('/[^A-Za-z0-9 ]/', ' ', $text) ?: $text) ?: [];
    $letters = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= strtoupper($part[0]);
        }

        if (strlen($letters) >= 2) {
            break;
        }
    }

    return $letters !== '' ? $letters : strtoupper(substr($text, 0, 2));
};

$renderTable = static function (array $panel): void {
    $columns = is_array($panel['columns'] ?? null) ? $panel['columns'] : [];
    $rows = is_array($panel['rows'] ?? null) ? $panel['rows'] : [];
    ?>
    <?php if ($rows === []): ?>
        <div class="dashboard-empty"><?= e((string) ($panel['empty'] ?? 'Chưa có dữ liệu.')) ?></div>
    <?php else: ?>
        <div class="dashboard-table-wrap">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <th><?= e((string) $column) ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ((array) $row as $cell): ?>
                            <td><?= e((string) $cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
};

$renderList = static function (array $panel): void {
    $items = is_array($panel['items'] ?? null) ? $panel['items'] : [];
    ?>
    <?php if ($items === []): ?>
        <div class="dashboard-empty"><?= e((string) ($panel['empty'] ?? 'Chưa có dữ liệu.')) ?></div>
    <?php else: ?>
        <div class="dashboard-side-list">
            <?php foreach ($items as $item): ?>
                <a class="dashboard-side-item" href="<?= e(url((string) ($item['href'] ?? '/dashboard'))) ?>">
                    <span>
                        <strong><?= e((string) ($item['title'] ?? '-')) ?></strong>
                        <small><?= e((string) ($item['meta'] ?? '')) ?></small>
                    </span>
                    <b><?= e((string) ($item['value'] ?? '')) ?></b>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
};

$renderRanking = static function (array $panel): void {
    $items = is_array($panel['items'] ?? null) ? $panel['items'] : [];
    ?>
    <?php if ($items === []): ?>
        <div class="dashboard-empty"><?= e((string) ($panel['empty'] ?? 'Chưa có bảng xếp hạng.')) ?></div>
    <?php else: ?>
        <div class="dashboard-ranking-list">
            <?php foreach ($items as $item): ?>
                <a class="dashboard-ranking-item" href="<?= e(url((string) ($item['href'] ?? '/dashboard'))) ?>">
                    <span class="dashboard-rank">#<?= e((string) ($item['rank'] ?? '-')) ?></span>
                    <span class="dashboard-team-mark"><?= e(substr((string) ($item['title'] ?? 'VT'), 0, 2)) ?></span>
                    <span class="dashboard-ranking-name">
                        <strong><?= e((string) ($item['title'] ?? '-')) ?></strong>
                        <small><?= e((string) ($item['meta'] ?? '')) ?></small>
                    </span>
                    <b><?= e((string) ($item['value'] ?? '')) ?></b>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
};

$renderDetail = static function (array $panel): void {
    $items = is_array($panel['items'] ?? null) ? $panel['items'] : [];
    ?>
    <?php if ($items === []): ?>
        <div class="dashboard-empty"><?= e((string) ($panel['empty'] ?? 'Chưa có dữ liệu.')) ?></div>
    <?php else: ?>
        <div class="dashboard-detail-list">
            <?php foreach ($items as $item): ?>
                <div class="dashboard-detail-row">
                    <span><?= e((string) ($item['label'] ?? '')) ?></span>
                    <strong><?= e((string) ($item['value'] ?? '')) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
};

$renderPanelBody = static function (array $panel) use ($renderTable, $renderList, $renderRanking, $renderDetail): void {
    match ((string) ($panel['type'] ?? 'empty')) {
        'table' => $renderTable($panel),
        'list' => $renderList($panel),
        'ranking' => $renderRanking($panel),
        'detail' => $renderDetail($panel),
        default => print '<div class="dashboard-empty">' . e((string) ($panel['empty'] ?? 'Chưa có dữ liệu.')) . '</div>',
    };
};

$heroCard = is_array($hero['card'] ?? null) ? $hero['card'] : null;
?>

<section class="dashboard-home dashboard-home--<?= e($variant) ?>">
    <section class="dashboard-hero dashboard-hero--<?= e($variant) ?>">
        <div class="dashboard-hero__copy">
            <p class="dashboard-eyebrow"><?= e((string) ($dashboard['eyebrow'] ?? 'Tổng quan')) ?></p>
            <h2><?= e((string) ($hero['title'] ?? 'Trang chủ quản lý bóng chuyền')) ?></h2>
            <p><?= e((string) ($hero['description'] ?? 'Theo dõi thông tin quan trọng của hệ thống.')) ?></p>

            <div class="dashboard-actions">
                <?php if (is_array($hero['primary'] ?? null)): ?>
                    <a class="button button--light" href="<?= e(url((string) $hero['primary']['href'])) ?>">
                        <?= e((string) $hero['primary']['label']) ?>
                    </a>
                <?php endif; ?>
                <?php if (is_array($hero['secondary'] ?? null)): ?>
                    <a class="button button--outline" href="<?= e(url((string) $hero['secondary']['href'])) ?>">
                        <?= e((string) $hero['secondary']['label']) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <aside class="dashboard-next-match">
            <h3><?= e((string) ($heroCard['title'] ?? 'Trọng tâm hiện tại')) ?></h3>
            <?php if (!empty($heroCard['empty'])): ?>
                <div class="dashboard-empty dashboard-empty--dark"><?= e((string) $heroCard['empty']) ?></div>
            <?php elseif ($heroCard !== null): ?>
                <div class="dashboard-matchup">
                    <div class="dashboard-match-team">
                        <span><?= e($initials((string) ($heroCard['team1'] ?? 'Đội 1'))) ?></span>
                        <strong><?= e((string) ($heroCard['team1'] ?? '-')) ?></strong>
                    </div>
                    <b>VS</b>
                    <div class="dashboard-match-team">
                        <span><?= e($initials((string) ($heroCard['team2'] ?? 'Đội 2'))) ?></span>
                        <strong><?= e((string) ($heroCard['team2'] ?? '-')) ?></strong>
                    </div>
                </div>

                <div class="dashboard-match-meta">
                    <p><span>Thời gian</span><strong><?= e((string) ($heroCard['time'] ?? '-')) ?></strong></p>
                    <p><span>Sân đấu</span><strong><?= e((string) ($heroCard['venue'] ?? '-')) ?></strong></p>
                    <p><span>Giải đấu</span><strong><?= e((string) ($heroCard['tournament'] ?? '-')) ?></strong></p>
                    <p><span>Vòng đấu</span><strong><?= e((string) ($heroCard['round'] ?? '-')) ?></strong></p>
                </div>
            <?php else: ?>
                <div class="dashboard-empty dashboard-empty--dark">Chưa có dữ liệu nổi bật.</div>
            <?php endif; ?>
        </aside>
    </section>

    <?php if ($stats !== []): ?>
        <section class="dashboard-stats-grid">
            <?php foreach ($stats as $stat): ?>
                <article class="dashboard-stat-card dashboard-stat-card--<?= e((string) ($stat['tone'] ?? 'blue')) ?>">
                    <div class="dashboard-stat-top">
                        <span><?= e((string) ($stat['icon'] ?? 'VT')) ?></span>
                        <small><?= e((string) ($stat['hint'] ?? '')) ?></small>
                    </div>
                    <strong><?= e((string) ($stat['value'] ?? '0')) ?></strong>
                    <p><?= e((string) ($stat['label'] ?? 'Chỉ số')) ?></p>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="dashboard-content-grid">
        <article class="dashboard-panel dashboard-panel--main">
            <div class="dashboard-panel__head">
                <div>
                    <h3><?= e((string) ($mainPanel['title'] ?? 'Dữ liệu chính')) ?></h3>
                    <p><?= e((string) ($mainPanel['subtitle'] ?? '')) ?></p>
                </div>
                <?php if (!empty($mainPanel['badge'])): ?>
                    <span><?= e((string) $mainPanel['badge']) ?></span>
                <?php endif; ?>
            </div>
            <?php $renderPanelBody($mainPanel); ?>
        </article>

        <aside class="dashboard-panel dashboard-panel--side">
            <div class="dashboard-panel__head">
                <div>
                    <h3><?= e((string) ($sidePanel['title'] ?? 'Thông tin nhanh')) ?></h3>
                    <p><?= e((string) ($sidePanel['subtitle'] ?? '')) ?></p>
                </div>
                <?php if (!empty($sidePanel['badge'])): ?>
                    <span><?= e((string) $sidePanel['badge']) ?></span>
                <?php endif; ?>
            </div>
            <?php $renderPanelBody($sidePanel); ?>
        </aside>
    </section>

    <?php if ($actions !== []): ?>
        <section class="dashboard-quick-actions" aria-label="Chức năng nhanh">
            <?php foreach ($actions as $index => $action): ?>
                <a class="dashboard-action-card" href="<?= e(url((string) ($action['href'] ?? '/dashboard'))) ?>">
                    <span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                    <strong><?= e((string) ($action['label'] ?? 'Chức năng')) ?></strong>
                    <small><?= e((string) ($action['desc'] ?? '')) ?></small>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</section>
