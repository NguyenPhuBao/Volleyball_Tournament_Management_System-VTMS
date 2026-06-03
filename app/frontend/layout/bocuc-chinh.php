<?php

use App\Backend\Core\Auth\Auth;
use App\Backend\Models\Giaidau;

$authUser = Auth::user();
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$role = $authUser['role'] ?? null;
$displayName = trim((string)($authUser['name'] ?? $authUser['hoten'] ?? $authUser['username'] ?? ''));
$displayName = $displayName !== '' ? $displayName : 'Người dùng';

$roleLabels = [
    'ADMIN' => 'Quản trị hệ thống',
    'BAN_TO_CHUC' => 'Ban tổ chức',
    'TRONG_TAI' => 'Trọng tài',
    'HUAN_LUYEN_VIEN' => 'Huấn luyện viên',
    'VAN_DONG_VIEN' => 'Vận động viên',
];

$roleInitials = [
    'ADMIN' => 'AD',
    'BAN_TO_CHUC' => 'BT',
    'TRONG_TAI' => 'TT',
    'HUAN_LUYEN_VIEN' => 'HL',
    'VAN_DONG_VIEN' => 'VD',
];

$navByRole = [
    'ADMIN' => [
        'Tổng quan' => [
            ['icon' => 'TC', 'label' => 'Trang chủ', 'href' => '/dashboard'],
        ],
        'Quản lý' => [
            ['icon' => 'TK', 'label' => 'Tài khoản', 'href' => '/admin/users'],
            ['icon' => 'ND', 'label' => 'Người dùng', 'href' => '/admin/nguoi-dung'],
            ['icon' => 'NK', 'label' => 'Nhật ký hệ thống', 'href' => '/admin/logs'],
            ['icon' => 'XN', 'label' => 'Xác nhận thông tin BTC', 'href' => '/admin/xac-nhan-thong-tin-btc'],
        ],
    ],
    'BAN_TO_CHUC' => [
        'Tổng quan' => [
            ['icon' => 'TC', 'label' => 'Trang chủ', 'href' => '/dashboard'],
            ['icon' => 'GD', 'label' => 'Giải đấu', 'href' => '/ban-to-chuc/giai-dau'],
            ['icon' => 'LT', 'label' => 'Lịch thi đấu', 'href' => '/ban-to-chuc/lich-thi-dau'],
        ],
        'Quản lý' => [
            ['icon' => 'DB', 'label' => 'Đội bóng', 'href' => '/ban-to-chuc/doi-bong'],
            ['icon' => 'DC', 'label' => 'Suất đại diện', 'href' => '/ban-to-chuc/tu-cach-cap-tren', 'requires' => 'higher_eligibility'],
            ['icon' => 'SD', 'label' => 'Sân đấu', 'href' => '/ban-to-chuc/san-dau'],
            ['icon' => 'TT', 'label' => 'Trọng tài', 'href' => '/ban-to-chuc/trong-tai'],
            ['icon' => 'TK', 'label' => 'Tài khoản HLV', 'href' => '/ban-to-chuc/tai-khoan-hlv', 'requires' => 'coach_account_approval'],
            ['icon' => 'TK', 'label' => 'Tài khoản trọng tài', 'href' => '/ban-to-chuc/tai-khoan-trong-tai', 'requires' => 'referee_account_approval'],
            ['icon' => 'HL', 'label' => 'Huấn luyện viên', 'href' => '/ban-to-chuc/huan-luyen-vien'],
            ['icon' => 'VD', 'label' => 'Vận động viên', 'href' => '/ban-to-chuc/van-dong-vien'],
        ],
        'Nghiệp vụ' => [
            ['icon' => 'KN', 'label' => 'Khiếu nại', 'href' => '/ban-to-chuc/khieu-nai'],
            ['icon' => 'KQ', 'label' => 'Kết quả', 'href' => '/ban-to-chuc/ket-qua'],
            ['icon' => 'XH', 'label' => 'Xếp hạng', 'href' => '/ban-to-chuc/xep-hang'],
            ['icon' => 'XN', 'label' => 'Xác nhận thông tin', 'href' => '/ban-to-chuc/xac-nhan-thong-tin-ca-nhan'],
        ],
    ],
    'TRONG_TAI' => [
        'Tổng quan' => [
            ['icon' => 'TC', 'label' => 'Trang chủ', 'href' => '/dashboard'],
            ['icon' => 'PC', 'label' => 'Lịch phân công', 'href' => '/trong-tai/lich-phan-cong'],
            ['icon' => 'GS', 'label' => 'Giám sát trận đấu', 'href' => '/trong-tai/giam-sat'],
        ],
        'Nghiệp vụ' => [
            ['icon' => 'BC', 'label' => 'Báo cáo sự cố', 'href' => '/trong-tai/bao-cao-su-co'],
            ['icon' => 'NP', 'label' => 'Xin nghỉ phép', 'href' => '/trong-tai/xin-nghi-phep'],
        ],
    ],
    'HUAN_LUYEN_VIEN' => [
        'Tổng quan' => [
            ['icon' => 'TC', 'label' => 'Trang chủ', 'href' => '/dashboard'],
            ['icon' => 'DB', 'label' => 'Đội bóng của tôi', 'href' => '/huan-luyen-vien/doi-bong'],
            ['icon' => 'LT', 'label' => 'Lịch thi đấu đội', 'href' => '/huan-luyen-vien/lich-thi-dau-doi'],
            ['icon' => 'KQ', 'label' => 'Kết quả thi đấu', 'href' => '/huan-luyen-vien/ket-qua'],
        ],
        'Quản lý' => [
            ['icon' => 'VD', 'label' => 'Tài khoản VĐV', 'href' => '/huan-luyen-vien/van-dong-vien'],
            ['icon' => 'TV', 'label' => 'Thành viên đội', 'href' => '/huan-luyen-vien/thanh-vien'],
            ['icon' => 'DH', 'label' => 'Đội hình', 'href' => '/huan-luyen-vien/doi-hinh'],
            ['icon' => 'GD', 'label' => 'Đăng ký giải đấu', 'href' => '/huan-luyen-vien/giai-dau'],
            ['icon' => 'YC', 'label' => 'Yêu cầu VĐV', 'href' => '/huan-luyen-vien/yeu-cau-vdv'],
        ],
    ],
    'VAN_DONG_VIEN' => [
        'Tổng quan' => [
            ['icon' => 'TC', 'label' => 'Trang chủ', 'href' => '/dashboard'],
            ['icon' => 'TB', 'label' => 'Thông báo', 'href' => '/van-dong-vien/thong-bao'],
            ['icon' => 'LM', 'label' => 'Lời mời đội bóng', 'href' => '/van-dong-vien/loi-moi-doi-bong'],
        ],
        'Cá nhân' => [
            ['icon' => 'DB', 'label' => 'Đội bóng của tôi', 'href' => '/van-dong-vien/doi-bong-cua-toi'],
            ['icon' => 'DH', 'label' => 'Đội hình', 'href' => '/van-dong-vien/doi-hinh'],
            ['icon' => 'LT', 'label' => 'Lịch thi đấu', 'href' => '/van-dong-vien/lich-thi-dau-ca-nhan'],
            ['icon' => 'HS', 'label' => 'Hồ sơ', 'href' => '/van-dong-vien/ho-so'],
            ['icon' => 'NP', 'label' => 'Nghỉ phép thi đấu', 'href' => '/van-dong-vien/nghi-phep-thi-dau'],
        ],
    ],
];

$navGroups = $role !== null && isset($navByRole[$role]) ? $navByRole[$role] : [
    'Tổng quan' => [
        ['icon' => 'TC', 'label' => 'Trang chủ', 'href' => '/dashboard'],
    ],
];

$navRequirements = [
    'higher_eligibility' => false,
    'coach_account_approval' => false,
    'referee_account_approval' => false,
];

if ($role === 'BAN_TO_CHUC' && !empty($authUser['id'])) {
    try {
        $organizer = (new Giaidau())->findOrganizerByAccountId((int) $authUser['id']);

        if ($organizer !== null) {
            $organizerActive = (string) ($organizer['trangthai'] ?? '') === 'HOAT_DONG';
            $unitActive = (string) ($organizer['trangthai_donvi'] ?? '') === 'HOAT_DONG';
            $unitTypeActive = (string) ($organizer['trangthai_loaidonvi'] ?? '') === 'HOAT_DONG';
            $canOrganize = (int) ($organizer['duoc_to_chuc_giai'] ?? 0) === 1;
            $unitType = (string) ($organizer['maloaidonvi'] ?? '');
            $isHighestOrganizerLevel = (int) ($organizer['idcapgiaidau_quanly'] ?? 0) > 0
                && array_key_exists('idcapgiaidau_cha_quanly', $organizer)
                && $organizer['idcapgiaidau_cha_quanly'] === null;
            $hasOrganizerAuthority = $organizerActive && $unitActive && $unitTypeActive && $canOrganize;

            $navRequirements['higher_eligibility'] = $hasOrganizerAuthority;
            $navRequirements['coach_account_approval'] = $hasOrganizerAuthority
                && $isHighestOrganizerLevel
                && $unitType === 'LIEN_DOAN_BONG_CHUYEN_VN';
            $navRequirements['referee_account_approval'] = $navRequirements['coach_account_approval'];
        }
    } catch (Throwable) {
        $navRequirements = [
            'higher_eligibility' => false,
            'coach_account_approval' => false,
            'referee_account_approval' => false,
        ];
    }

    foreach ($navGroups as $groupTitle => $items) {
        $navGroups[$groupTitle] = array_values(array_filter(
            $items,
            static fn (array $item): bool => !isset($item['requires']) || ($navRequirements[$item['requires']] ?? false)
        ));

        if ($navGroups[$groupTitle] === []) {
            unset($navGroups[$groupTitle]);
        }
    }
}

$roleLabel = $role !== null ? ($roleLabels[$role] ?? $role) : 'Hệ thống';
$roleInitial = $role !== null ? ($roleInitials[$role] ?? 'VT') : 'VT';
$appTitle = trim((string)($pageTitle ?? $moduleTitle ?? 'Trang chủ quản lý bóng chuyền'));
$appTitle = preg_replace('/^VTMS\s*[-|]\s*/i', '', $appTitle) ?: $appTitle;
$appSubtitle = trim((string)($moduleDescription ?? $roleLabel));
$isDashboardPage = $authUser && rtrim($currentPath, '/') === '/dashboard';

if ($isDashboardPage && preg_match('/^Trang\s+ch(?:ủ|u)\b/iu', $appTitle)) {
    $appTitle = 'Trang chủ';
    $appSubtitle = '';
}

$isActive = static function (string $href) use ($currentPath): bool {
    return $currentPath === $href || ($href !== '/' && strncmp($currentPath, $href . '/', strlen($href) + 1) === 0);
};

$isAuthPage = in_array($currentPath, ['/login', '/forgot-password'], true);
?>
<!doctype html>
<html lang="vi" data-vtms-lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? config('app.name', 'VTMS')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <?php foreach (($styles ?? []) as $style): ?>
        <link rel="stylesheet" href="<?= e(asset($style)) ?>">
    <?php endforeach; ?>
    <?php if ($authUser): ?>
        <link rel="stylesheet" href="<?= e(asset('css/theme-overrides.css')) ?>">
    <?php endif; ?>
</head>
<body class="<?= $authUser ? 'has-app-shell' : 'public-shell' ?>">
<?php if ($authUser): ?>
    <div class="app-shell">
        <div class="app-overlay" data-app-overlay></div>

        <aside class="app-sidebar" data-app-sidebar>
            <a class="app-brand" href="<?= e(url('/dashboard')) ?>">
                <span class="app-brand__logo">VT</span>
                <span>
                    <strong>Volley Manager</strong>
                    <small data-i18n-text>Hệ thống quản lý giải đấu</small>
                </span>
            </a>

            <nav class="app-nav" aria-label="Điều hướng chính">
                <?php foreach ($navGroups as $groupTitle => $items): ?>
                    <p class="app-nav__title" data-i18n-text><?= e($groupTitle) ?></p>
                    <?php foreach ($items as $item): ?>
                        <a class="app-nav__link <?= $isActive($item['href']) ? 'active' : '' ?>" href="<?= e(url($item['href'])) ?>">
                            <span class="app-nav__icon"><?= e($item['icon']) ?></span>
                            <span data-i18n-text><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </nav>

            <section class="app-settings" aria-label="Cài đặt giao diện">
                <button class="app-settings__toggle" type="button" data-settings-toggle>
                    <span class="app-settings__icon" aria-hidden="true">⚙</span>
                    <span data-i18n-text>Cài đặt</span>
                </button>
                <div class="app-settings__panel" data-settings-panel>
                    <p data-i18n-text>Tài khoản</p>
                    <a class="app-settings__link" href="<?= e(url('/tai-khoan/doi-mat-khau')) ?>">
                        <span class="app-settings__icon" aria-hidden="true">MK</span>
                        <span data-i18n-text>Đổi mật khẩu</span>
                    </a>
                    <p data-i18n-text>Ngôn ngữ</p>
                    <div class="app-language" role="group" aria-label="Chọn ngôn ngữ">
                        <button type="button" data-lang-option="vi">Tiếng Việt</button>
                        <button type="button" data-lang-option="en">English</button>
                    </div>
                </div>
            </section>
        </aside>

        <button class="app-sidebar-toggle" type="button" data-sidebar-toggle aria-label="Thu gọn thanh menu" aria-expanded="true" title="Thu gọn thanh menu">
            <span data-sidebar-toggle-icon>&lt;</span>
        </button>

        <div class="app-main">
            <header class="app-topbar">
                <button class="app-menu-btn" type="button" data-app-menu aria-label="Mở menu">☰</button>

                <div class="app-topbar__title">
                    <h1><?= e($appTitle) ?></h1>
                    <?php if ($appSubtitle !== ''): ?>
                        <p><?= e($appSubtitle) ?></p>
                    <?php endif; ?>
                </div>

                <div class="app-search-group">
                    <label class="app-search">
                        <span class="app-search__icon" aria-hidden="true"></span>
                        <input type="search" data-app-search data-i18n-placeholder placeholder="Tìm trận đấu, đội bóng...">
                    </label>
                    <button class="app-search-submit" type="button" data-app-search-button data-i18n-text>Tìm kiếm</button>
                </div>

                <div class="app-user">
                    <div class="app-user__text">
                        <strong><?= e($displayName) ?></strong>
                        <span data-i18n-text><?= e($roleLabel) ?></span>
                    </div>
                    <div class="app-avatar"><?= e($roleInitial) ?></div>
                    <form method="post" action="<?= e(url('/logout')) ?>" class="app-logout">
                        <?= csrf_field() ?>
                        <button type="submit" title="Đăng xuất" data-i18n-title data-i18n-text>Đăng xuất</button>
                    </form>
                </div>
            </header>

            <main class="page app-page" data-app-content>
                <?= $content ?>
            </main>
        </div>
    </div>

    <script src="<?= e(asset('js/i18n.js')) ?>"></script>
    <script src="<?= e(asset('js/app-shell.js')) ?>"></script>
<?php else: ?>
    <?php if (!$isAuthPage): ?>
        <header class="public-topbar">
            <a class="public-brand" href="<?= e(url('/')) ?>">
                <span class="public-brand__logo">VT</span>
                <span>Volley Manager</span>
            </a>
            <nav class="public-nav">
                <a href="<?= e(url('/')) ?>">Trang chủ</a>
                <a class="button primary" href="<?= e(url('/login')) ?>">Đăng nhập</a>
            </nav>
        </header>
    <?php endif; ?>

    <main class="page <?= $isAuthPage ? 'page--auth' : '' ?>">
        <?= $content ?>
    </main>
<?php endif; ?>

<?php foreach (($scripts ?? []) as $script): ?>
    <script src="<?= e(asset($script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
