<?php

declare(strict_types=1);

namespace App\Backend\Services\Shared;

use App\Backend\Core\Database;
use PDO;
use Throwable;

final class DashboardSummaryService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function forUser(array $user, ?string $ipAddress = null): array
    {
        $accountId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        try {
            $data = match ($role) {
                'ADMIN' => $this->admin(),
                'BAN_TO_CHUC' => $this->organizer($accountId),
                'TRONG_TAI' => $this->referee($accountId),
                'HUAN_LUYEN_VIEN' => $this->coach($accountId),
                'VAN_DONG_VIEN' => $this->athlete($accountId),
                default => $this->fallback($role),
            };

            $this->recordDashboardLog($accountId, $role, $ipAddress);

            return $data;
        } catch (Throwable) {
            return $this->fallback($role);
        }
    }

    private function admin(): array
    {
        $roleRows = $this->rows(
            "SELECT r.namerole AS role_name, COUNT(tk.idtaikhoan) AS total
             FROM `Role` r
             LEFT JOIN Taikhoan tk ON tk.idrole = r.idrole
             GROUP BY r.idrole, r.namerole
             ORDER BY r.idrole"
        );

        $latestLogs = $this->rows(
            "SELECT
                nk.idnhatky,
                nk.hanhdong,
                nk.bangtacdong,
                nk.thoigian,
                COALESCE(NULLIF(TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))), ''), tk.username, 'He thong') AS actor_name
             FROM Nhatkyhethong nk
             LEFT JOIN Taikhoan tk ON tk.idtaikhoan = nk.idtaikhoan
             LEFT JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan
             ORDER BY nk.thoigian DESC, nk.idnhatky DESC
             LIMIT 6"
        );

        return $this->baseDashboard(
            'admin',
            'Quan tri vien',
            'Trang chu quan tri he thong',
            'Tong quan tai khoan, nguoi dung, yeu cau xac nhan va nhat ky van hanh.',
            'Van hanh he thong tap trung, ro rang va co kiem soat',
            'Theo doi tai khoan, phan quyen, ho so nguoi dung va cac dau vet nghiep vu quan trong tren mot man hinh.',
            ['label' => 'Quan ly tai khoan', 'href' => '/admin/users'],
            ['label' => 'Nhat ky he thong', 'href' => '/admin/logs'],
            [
                $this->stat('Tai khoan', $this->count('Taikhoan'), 'Ho so dang nhap', 'blue', 'TK'),
                $this->stat('Dang hoat dong', $this->count('Taikhoan', "trangthai = 'HOAT_DONG'"), 'Co the dang nhap', 'green', 'HD'),
                $this->stat('Cho xac nhan BTC', $this->pendingOrganizerChanges(), 'Can admin xu ly', 'orange', 'XN'),
                $this->stat('Nhat ky', $this->count('Nhatkyhethong'), 'Dau vet he thong', 'purple', 'NK'),
            ],
            [
                'title' => 'Nhat ky gan day',
                'subtitle' => 'Cac thao tac moi nhat trong he thong.',
                'badge' => count($latestLogs) . ' dong',
                'type' => 'table',
                'columns' => ['Thoi gian', 'Nguoi thuc hien', 'Hanh dong', 'Bang tac dong'],
                'rows' => array_map(fn (array $row): array => [
                    $this->dateTime($row['thoigian'] ?? null),
                    (string) $row['actor_name'],
                    (string) $row['hanhdong'],
                    (string) $row['bangtacdong'],
                ], $latestLogs),
                'empty' => 'Chua co nhat ky he thong.',
            ],
            [
                'title' => 'Phan bo tai khoan',
                'subtitle' => 'So tai khoan theo vai tro.',
                'badge' => 'Vai tro',
                'type' => 'list',
                'items' => array_map(fn (array $row): array => [
                    'title' => $this->roleLabel((string) $row['role_name']),
                    'meta' => 'Tai khoan',
                    'value' => $this->number((int) $row['total']),
                    'href' => '/admin/users',
                ], $roleRows),
            ],
            [
                ['label' => 'Tai khoan', 'href' => '/admin/users', 'desc' => 'Tao, khoa va cap nhat vai tro.'],
                ['label' => 'Nguoi dung', 'href' => '/admin/nguoi-dung', 'desc' => 'Quan ly ho so nguoi dung.'],
                ['label' => 'Nhat ky', 'href' => '/admin/logs', 'desc' => 'Kiem tra dau vet nghiep vu.'],
                ['label' => 'Xac nhan BTC', 'href' => '/admin/xac-nhan-thong-tin-btc', 'desc' => 'Duyet thay doi thong tin ban to chuc.'],
            ]
        );
    }

    private function organizer(int $accountId): array
    {
        $organizer = $this->organizerByAccount($accountId);
        $organizerId = (int) ($organizer['idbantochuc'] ?? 0);
        $nextMatch = $organizerId > 0 ? $this->nextOrganizerMatch($organizerId) : null;
        $matches = $organizerId > 0 ? $this->organizerMatches($organizerId) : [];
        $rankingRows = $organizerId > 0 ? $this->latestOrganizerRankingRows($organizerId) : [];

        return $this->baseDashboard(
            'organizer',
            'Ban to chuc',
            'Trang chu ban to chuc',
            'Tong quan giai dau, lich thi dau, doi tham gia va cac nghiep vu can xu ly.',
            'Dieu phoi giai dau chuyen nghiep tren mot man hinh',
            'Quan ly giai dau, san dau, lich thi dau, trong tai, ket qua va bang xep hang theo dung quy trinh van hanh.',
            ['label' => 'Quan ly lich thi dau', 'href' => '/ban-to-chuc/lich-thi-dau'],
            ['label' => 'Cong bo ket qua', 'href' => '/ban-to-chuc/ket-qua'],
            [
                $this->stat('Giai dau', $this->countOrganizerTournaments($organizerId), 'Thuoc ban to chuc', 'blue', 'GD'),
                $this->stat('Doi tham gia', $this->countOrganizerTeams($organizerId), 'Da duyet dang ky', 'green', 'DB'),
                $this->stat('Tran dau', $this->countOrganizerMatches($organizerId), 'Trong cac giai', 'purple', 'TD'),
                $this->stat('Khieu nai cho xu ly', $this->countOrganizerComplaints($organizerId), 'Can tiep nhan/xu ly', 'orange', 'KN'),
            ],
            [
                'title' => 'Lich thi dau gan nhat',
                'subtitle' => 'Cac tran cua giai dau do ban to chuc quan ly.',
                'badge' => count($matches) . ' tran',
                'type' => 'table',
                'columns' => ['Thoi gian', 'Tran dau', 'San', 'Trang thai'],
                'rows' => array_map(fn (array $row): array => [
                    $this->dateTime($row['thoigianbatdau'] ?? null),
                    trim((string) $row['doi1'] . ' vs ' . (string) $row['doi2']),
                    (string) $row['tensandau'],
                    $this->matchStatusLabel((string) $row['trangthai']),
                ], $matches),
                'empty' => 'Chua co lich thi dau.',
            ],
            [
                'title' => 'Bang xep hang nhanh',
                'subtitle' => 'Du lieu tu bang xep hang da cong bo moi nhat.',
                'badge' => 'Top 5',
                'type' => 'ranking',
                'items' => array_map(fn (array $row): array => [
                    'rank' => (int) $row['hang'],
                    'title' => (string) $row['tendoibong'],
                    'meta' => (int) $row['thang'] . ' thang - ' . (int) $row['thua'] . ' thua',
                    'value' => (int) $row['diem'] . 'd',
                    'href' => '/ban-to-chuc/xep-hang',
                ], $rankingRows),
                'empty' => 'Chua co bang xep hang da cong bo.',
            ],
            [
                ['label' => 'Giai dau', 'href' => '/ban-to-chuc/giai-dau', 'desc' => 'Tao, cap nhat va cong bo giai dau.'],
                ['label' => 'Lich thi dau', 'href' => '/ban-to-chuc/lich-thi-dau', 'desc' => 'Lap bang dau, tran dau, thoi gian va san.'],
                ['label' => 'Ket qua', 'href' => '/ban-to-chuc/ket-qua', 'desc' => 'Dieu chinh va cong bo ket qua.'],
                ['label' => 'Xep hang', 'href' => '/ban-to-chuc/xep-hang', 'desc' => 'Tao va cong bo bang xep hang.'],
            ],
            $this->matchHeroCard($nextMatch, '/ban-to-chuc/lich-thi-dau')
        );
    }

    private function referee(int $accountId): array
    {
        $referee = $this->refereeByAccount($accountId);
        $refereeId = (int) ($referee['idtrongtai'] ?? 0);
        $nextMatch = $refereeId > 0 ? $this->nextRefereeAssignment($refereeId) : null;
        $assignments = $refereeId > 0 ? $this->refereeAssignments($refereeId) : [];
        $stats = $refereeId > 0 ? $this->refereeStats($refereeId) : ['total' => 0, 'pending' => 0, 'upcoming' => 0, 'incidents' => 0];

        return $this->baseDashboard(
            'referee',
            'Trong tai',
            'Trang chu trong tai',
            'Theo doi lich phan cong, xac nhan tham gia, giam sat tran dau va bao cao su co.',
            'San sang dieu hanh tran dau dung lich, dung vai tro',
            'Xem cac tran duoc phan cong, tinh trang xac nhan, thong tin san dau va cac nghiep vu giam sat dang cho thuc hien.',
            ['label' => 'Lich phan cong', 'href' => '/trong-tai/lich-phan-cong'],
            ['label' => 'Giam sat tran dau', 'href' => '/trong-tai/giam-sat'],
            [
                $this->stat('Tong phan cong', $stats['total'], 'Tat ca trang thai', 'blue', 'PC'),
                $this->stat('Sap dien ra', $stats['upcoming'], 'Can theo doi', 'green', 'SD'),
                $this->stat('Cho xac nhan', $stats['pending'], 'Can phan hoi', 'orange', 'XN'),
                $this->stat('Bao cao su co', $stats['incidents'], 'Da gui', 'purple', 'BC'),
            ],
            [
                'title' => 'Lich phan cong gan nhat',
                'subtitle' => 'Cac tran trong tai duoc phan cong.',
                'badge' => count($assignments) . ' muc',
                'type' => 'table',
                'columns' => ['Thoi gian', 'Tran dau', 'Vai tro', 'Trang thai'],
                'rows' => array_map(fn (array $row): array => [
                    $this->dateTime($row['thoigianbatdau'] ?? null),
                    trim((string) $row['doi1'] . ' vs ' . (string) $row['doi2']),
                    $this->assignmentRoleLabel((string) $row['vaitro']),
                    $this->assignmentStatusLabel((string) $row['phancong_trangthai']),
                ], $assignments),
                'empty' => 'Chua co phan cong tran dau.',
            ],
            [
                'title' => 'Tran sap phu trach',
                'subtitle' => 'Thong tin nhanh de trong tai chuan bi.',
                'badge' => $nextMatch ? 'Gan nhat' : 'Trong',
                'type' => 'detail',
                'items' => $this->matchDetailItems($nextMatch),
                'empty' => 'Chua co tran sap dien ra.',
            ],
            [
                ['label' => 'Lich phan cong', 'href' => '/trong-tai/lich-phan-cong', 'desc' => 'Xem va phan hoi phan cong.'],
                ['label' => 'Giam sat tran dau', 'href' => '/trong-tai/giam-sat', 'desc' => 'Bat dau, tam dung, tiep tuc, ket thuc tran.'],
                ['label' => 'Bao cao su co', 'href' => '/trong-tai/bao-cao-su-co', 'desc' => 'Gui bao cao su co phat sinh.'],
                ['label' => 'Xin nghi phep', 'href' => '/trong-tai/xin-nghi-phep', 'desc' => 'Tao va theo doi don nghi phep.'],
            ],
            $this->matchHeroCard($nextMatch, '/trong-tai/lich-phan-cong')
        );
    }

    private function coach(int $accountId): array
    {
        $coach = $this->coachByAccount($accountId);
        $coachId = (int) ($coach['idhuanluyenvien'] ?? 0);
        $team = $coachId > 0 ? $this->coachPrimaryTeam($coachId) : null;
        $teamId = (int) ($team['iddoibong'] ?? 0);
        $nextMatch = $teamId > 0 ? $this->nextTeamMatch($teamId) : null;
        $schedule = $teamId > 0 ? $this->teamScheduleRows($teamId) : [];
        $rankingRows = $nextMatch ? $this->latestTournamentRankingRows((int) $nextMatch['idgiaidau']) : [];

        return $this->baseDashboard(
            'coach',
            'Huan luyen vien',
            'Trang chu huan luyen vien',
            'Quan ly doi bong, thanh vien, doi hinh, dang ky giai va lich thi dau cua doi.',
            'Quan ly doi bong, nhan su va lich thi dau tap trung',
            'Theo doi tinh trang doi bong, so luong thanh vien, lich thi dau sap toi va cac yeu cau can HLV xu ly.',
            ['label' => 'Doi bong cua toi', 'href' => '/huan-luyen-vien/doi-bong'],
            ['label' => 'Doi hinh thi dau', 'href' => '/huan-luyen-vien/doi-hinh'],
            [
                $this->stat('Doi bong', $this->countCoachTeams($coachId), 'Thuoc HLV', 'blue', 'DB'),
                $this->stat('Thanh vien', $teamId > 0 ? $this->countTeamMembers($teamId) : 0, 'Dang tham gia', 'green', 'TV'),
                $this->stat('Doi hinh', $teamId > 0 ? $this->countTeamLineups($teamId) : 0, 'Da tao', 'purple', 'DH'),
                $this->stat('Yeu cau VDV', $this->countCoachAthleteRequests($coachId), 'Cho duyet', 'orange', 'YC'),
            ],
            [
                'title' => 'Lich thi dau cua doi',
                'subtitle' => $team ? (string) $team['tendoibong'] : 'Chua co doi bong.',
                'badge' => count($schedule) . ' tran',
                'type' => 'table',
                'columns' => ['Thoi gian', 'Giai dau', 'Doi thu', 'San'],
                'rows' => array_map(fn (array $row): array => [
                    $this->dateTime($row['thoigianbatdau'] ?? null),
                    (string) $row['tengiaidau'],
                    (int) $row['iddoibong1'] === $teamId ? (string) $row['doi2'] : (string) $row['doi1'],
                    (string) $row['tensandau'],
                ], $schedule),
                'empty' => 'Chua co lich thi dau cho doi.',
            ],
            [
                'title' => 'Bang xep hang lien quan',
                'subtitle' => 'Top doi trong giai dau gan nhat cua doi.',
                'badge' => 'Top 5',
                'type' => 'ranking',
                'items' => array_map(fn (array $row): array => [
                    'rank' => (int) $row['hang'],
                    'title' => (string) $row['tendoibong'],
                    'meta' => (int) $row['thang'] . ' thang - ' . (int) $row['thua'] . ' thua',
                    'value' => (int) $row['diem'] . 'd',
                    'href' => '/huan-luyen-vien/lich-thi-dau-doi',
                ], $rankingRows),
                'empty' => 'Chua co bang xep hang lien quan.',
            ],
            [
                ['label' => 'Tai khoan VDV', 'href' => '/huan-luyen-vien/van-dong-vien', 'desc' => 'Tao tai khoan van dong vien.'],
                ['label' => 'Thanh vien doi', 'href' => '/huan-luyen-vien/thanh-vien', 'desc' => 'Them, xoa, chuyen vai tro thanh vien.'],
                ['label' => 'Doi hinh', 'href' => '/huan-luyen-vien/doi-hinh', 'desc' => 'Tao va cap nhat doi hinh thi dau.'],
                ['label' => 'Dang ky giai', 'href' => '/huan-luyen-vien/giai-dau', 'desc' => 'Dang ky doi tham gia giai.'],
            ],
            $this->matchHeroCard($nextMatch, '/huan-luyen-vien/lich-thi-dau-doi')
        );
    }

    private function athlete(int $accountId): array
    {
        $athlete = $this->athleteByAccount($accountId);
        $athleteId = (int) ($athlete['idvandongvien'] ?? 0);
        $team = $athleteId > 0 ? $this->athleteTeam($athleteId) : null;
        $schedule = $athleteId > 0 ? $this->athleteScheduleRows($athleteId) : [];
        $nextMatch = $schedule[0] ?? null;

        return $this->baseDashboard(
            'athlete',
            'Van dong vien',
            'Trang chu van dong vien',
            'Theo doi doi bong, doi hinh, lich thi dau ca nhan va cac yeu cau ca nhan.',
            'Nam ro lich thi dau va trang thai ca nhan',
            'Xem lich thi dau lien quan, thong tin doi bong dang tham gia va loi moi doi bong cua ban.',
            ['label' => 'Lich thi dau ca nhan', 'href' => '/van-dong-vien/lich-thi-dau-ca-nhan'],
            ['label' => 'Doi hinh', 'href' => '/van-dong-vien/doi-hinh'],
            [
                $this->stat('Tran lien quan', count($schedule), 'Trong lich ca nhan', 'blue', 'LT'),
                $this->stat('Doi bong', $team ? 1 : 0, $team ? 'Dang tham gia' : 'Chua co doi', 'green', 'DB'),
                $this->stat('Loi moi cho phan hoi', $this->countAthleteInvitations($athleteId), 'Can xu ly', 'orange', 'LM'),
                $this->stat('Don nghi phep', $this->countAthleteLeaves($athleteId), 'Cho duyet', 'purple', 'NP'),
            ],
            [
                'title' => 'Lich thi dau ca nhan',
                'subtitle' => $team ? (string) $team['tendoibong'] : 'Chua co doi bong dang tham gia.',
                'badge' => count($schedule) . ' tran',
                'type' => 'table',
                'columns' => ['Thoi gian', 'Giai dau', 'Tran dau', 'San'],
                'rows' => array_map(fn (array $row): array => [
                    $this->dateTime($row['thoigianbatdau'] ?? null),
                    (string) $row['tengiaidau'],
                    trim((string) $row['doi1'] . ' vs ' . (string) $row['doi2']),
                    (string) $row['tensandau'],
                ], $schedule),
                'empty' => 'Chua co lich thi dau ca nhan.',
            ],
            [
                'title' => 'Ho so thi dau',
                'subtitle' => 'Thong tin tom tat cua van dong vien.',
                'badge' => $team ? 'Dang tham gia' : 'Chua co doi',
                'type' => 'detail',
                'items' => [
                    ['label' => 'Doi bong', 'value' => (string) ($team['tendoibong'] ?? 'Chua co')],
                    ['label' => 'Vi tri', 'value' => (string) ($athlete['vitri'] ?? '-')],
                    ['label' => 'Ma VDV', 'value' => (string) ($athlete['mavandongvien'] ?? '-')],
                    ['label' => 'Trang thai doi', 'value' => $team ? 'Dang tham gia' : 'Chua co doi'],
                ],
            ],
            [
                ['label' => 'Loi moi doi bong', 'href' => '/van-dong-vien/loi-moi-doi-bong', 'desc' => 'Dong y hoac tu choi loi moi.'],
                ['label' => 'Doi bong cua toi', 'href' => '/van-dong-vien/doi-bong-cua-toi', 'desc' => 'Xem thong tin doi bong.'],
                ['label' => 'Doi hinh', 'href' => '/van-dong-vien/doi-hinh', 'desc' => 'Xem doi hinh da thiet lap.'],
                ['label' => 'Nghi phep thi dau', 'href' => '/van-dong-vien/nghi-phep-thi-dau', 'desc' => 'Gui yeu cau nghi thi dau.'],
            ],
            $this->matchHeroCard($nextMatch, '/van-dong-vien/lich-thi-dau-ca-nhan')
        );
    }

    private function baseDashboard(
        string $variant,
        string $eyebrow,
        string $topTitle,
        string $topSubtitle,
        string $title,
        string $description,
        array $primary,
        ?array $secondary,
        array $stats,
        array $mainPanel,
        array $sidePanel,
        array $actions,
        ?array $heroCard = null
    ): array {
        return $this->localizeDashboard([
            'variant' => $variant,
            'eyebrow' => $eyebrow,
            'top_title' => $topTitle,
            'top_subtitle' => $topSubtitle,
            'hero' => [
                'title' => $title,
                'description' => $description,
                'primary' => $primary,
                'secondary' => $secondary,
                'card' => $heroCard,
            ],
            'stats' => $stats,
            'main_panel' => $mainPanel,
            'side_panel' => $sidePanel,
            'actions' => $actions,
            'loaded' => true,
        ]);
    }

    private function fallback(string $role): array
    {
        return $this->baseDashboard(
            'admin',
            $role !== '' ? $this->roleLabel($role) : 'He thong',
            'Trang chu',
            'Du lieu tong quan dang duoc nap.',
            'Trang chu quan ly bong chuyen',
            'He thong khong the nap du lieu tong quan tai thoi diem nay. Vui long thu lai hoac truy cap truc tiep cac chuc nang trong menu.',
            ['label' => 'Lam moi', 'href' => '/dashboard'],
            null,
            [],
            [
                'title' => 'Chuc nang',
                'subtitle' => 'Dung menu ben trai de tiep tuc.',
                'badge' => 'Tam thoi',
                'type' => 'empty',
                'rows' => [],
                'empty' => 'Khong co du lieu hien thi.',
            ],
            [
                'title' => 'Trang thai',
                'subtitle' => 'Khong the doc du lieu dashboard.',
                'badge' => 'Offline',
                'type' => 'detail',
                'items' => [['label' => 'Ket noi', 'value' => 'Can kiem tra CSDL']],
            ],
            []
        );
    }

    private function localizeDashboard(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->localizeDashboard($item);
            }

            return $value;
        }

        if (!is_string($value) || $value === '') {
            return $value;
        }

        static $map = [
            'He thong khong the nap du lieu tong quan tai thoi diem nay. Vui long thu lai hoac truy cap truc tiep cac chuc nang trong menu.' => 'Hệ thống không thể nạp dữ liệu tổng quan tại thời điểm này. Vui lòng thử lại hoặc truy cập trực tiếp các chức năng trong menu.',
            'Theo doi tai khoan, phan quyen, ho so nguoi dung va cac dau vet nghiep vu quan trong tren mot man hinh.' => 'Theo dõi tài khoản, phân quyền, hồ sơ người dùng và các dấu vết nghiệp vụ quan trọng trên một màn hình.',
            'Quan ly giai dau, san dau, lich thi dau, trong tai, ket qua va bang xep hang theo dung quy trinh van hanh.' => 'Quản lý giải đấu, sân đấu, lịch thi đấu, trọng tài, kết quả và bảng xếp hạng theo đúng quy trình vận hành.',
            'Xem cac tran duoc phan cong, tinh trang xac nhan, thong tin san dau va cac nghiep vu giam sat dang cho thuc hien.' => 'Xem các trận được phân công, tình trạng xác nhận, thông tin sân đấu và các nghiệp vụ giám sát đang chờ thực hiện.',
            'Theo doi tinh trang doi bong, so luong thanh vien, lich thi dau sap toi va cac yeu cau can HLV xu ly.' => 'Theo dõi tình trạng đội bóng, số lượng thành viên, lịch thi đấu sắp tới và các yêu cầu cần HLV xử lý.',
            'Xem lich thi dau lien quan, thong tin doi bong dang tham gia, loi moi doi bong va thong ke thi dau cua ban.' => 'Xem lịch thi đấu liên quan, thông tin đội bóng đang tham gia, lời mời đội bóng và thống kê thi đấu của bạn.',
            'Tong quan tai khoan, nguoi dung, yeu cau xac nhan va nhat ky van hanh.' => 'Tổng quan tài khoản, người dùng, yêu cầu xác nhận và nhật ký vận hành.',
            'Tong quan giai dau, lich thi dau, doi tham gia va cac nghiep vu can xu ly.' => 'Tổng quan giải đấu, lịch thi đấu, đội tham gia và các nghiệp vụ cần xử lý.',
            'Theo doi lich phan cong, xac nhan tham gia, giam sat tran dau va bao cao su co.' => 'Theo dõi lịch phân công, xác nhận tham gia, giám sát trận đấu và báo cáo sự cố.',
            'Quan ly doi bong, thanh vien, doi hinh, dang ky giai va lich thi dau cua doi.' => 'Quản lý đội bóng, thành viên, đội hình, đăng ký giải và lịch thi đấu của đội.',
            'Theo doi doi bong, doi hinh, lich thi dau ca nhan, thong ke va cac yeu cau ca nhan.' => 'Theo dõi đội bóng, đội hình, lịch thi đấu cá nhân, thống kê và các yêu cầu cá nhân.',
            'Van hanh he thong tap trung, ro rang va co kiem soat' => 'Vận hành hệ thống tập trung, rõ ràng và có kiểm soát',
            'Dieu phoi giai dau chuyen nghiep tren mot man hinh' => 'Điều phối giải đấu chuyên nghiệp trên một màn hình',
            'San sang dieu hanh tran dau dung lich, dung vai tro' => 'Sẵn sàng điều hành trận đấu đúng lịch, đúng vai trò',
            'Quan ly doi bong, nhan su va lich thi dau tap trung' => 'Quản lý đội bóng, nhân sự và lịch thi đấu tập trung',
            'Nam ro lich thi dau va trang thai ca nhan' => 'Nắm rõ lịch thi đấu và trạng thái cá nhân',
            'Du lieu tong quan dang duoc nap.' => 'Dữ liệu tổng quan đang được nạp.',
            'Dung menu ben trai de tiep tuc.' => 'Dùng menu bên trái để tiếp tục.',
            'Khong the doc du lieu dashboard.' => 'Không thể đọc dữ liệu dashboard.',
            'Khong co du lieu hien thi.' => 'Không có dữ liệu hiển thị.',
            'Trang chu quan tri he thong' => 'Trang chủ quản trị hệ thống',
            'Trang chu ban to chuc' => 'Trang chủ ban tổ chức',
            'Trang chu trong tai' => 'Trang chủ trọng tài',
            'Trang chu huan luyen vien' => 'Trang chủ huấn luyện viên',
            'Trang chu van dong vien' => 'Trang chủ vận động viên',
            'Trang chu quan ly bong chuyen' => 'Trang chủ quản lý bóng chuyền',
            'Quan tri vien' => 'Quản trị viên',
            'Ban to chuc' => 'Ban tổ chức',
            'Trong tai chinh' => 'Trọng tài chính',
            'Trong tai phu' => 'Trọng tài phụ',
            'Trong tai' => 'Trọng tài',
            'Huan luyen vien' => 'Huấn luyện viên',
            'Van dong vien' => 'Vận động viên',
            'He thong' => 'Hệ thống',
            'Tai khoan VDV' => 'Tài khoản VĐV',
            'Tai khoan' => 'Tài khoản',
            'Dang hoat dong' => 'Đang hoạt động',
            'Ho so dang nhap' => 'Hồ sơ đăng nhập',
            'Co the dang nhap' => 'Có thể đăng nhập',
            'Cho xac nhan BTC' => 'Chờ xác nhận BTC',
            'Can admin xu ly' => 'Cần admin xử lý',
            'Dau vet he thong' => 'Dấu vết hệ thống',
            'Nhat ky gan day' => 'Nhật ký gần đây',
            'Nhat ky he thong' => 'Nhật ký hệ thống',
            'Nhat ky' => 'Nhật ký',
            'Cac thao tac moi nhat trong he thong.' => 'Các thao tác mới nhất trong hệ thống.',
            'Nguoi thuc hien' => 'Người thực hiện',
            'Bang tac dong' => 'Bảng tác động',
            'Hanh dong' => 'Hành động',
            'Chua co nhat ky he thong.' => 'Chưa có nhật ký hệ thống.',
            'Phan bo tai khoan' => 'Phân bổ tài khoản',
            'So tai khoan theo vai tro.' => 'Số tài khoản theo vai trò.',
            'Vai tro' => 'Vai trò',
            'Tao, khoa va cap nhat vai tro.' => 'Tạo, khóa và cập nhật vai trò.',
            'Quan ly ho so nguoi dung.' => 'Quản lý hồ sơ người dùng.',
            'Kiem tra dau vet nghiep vu.' => 'Kiểm tra dấu vết nghiệp vụ.',
            'Duyet thay doi thong tin ban to chuc.' => 'Duyệt thay đổi thông tin ban tổ chức.',
            'Nguoi dung' => 'Người dùng',
            'Xac nhan BTC' => 'Xác nhận BTC',
            'Giai dau' => 'Giải đấu',
            'Doi tham gia' => 'Đội tham gia',
            'Da duyet dang ky' => 'Đã duyệt đăng ký',
            'Tran dau' => 'Trận đấu',
            'Trong cac giai' => 'Trong các giải',
            'Khieu nai cho xu ly' => 'Khiếu nại chờ xử lý',
            'Can tiep nhan/xu ly' => 'Cần tiếp nhận/xử lý',
            'Lich thi dau gan nhat' => 'Lịch thi đấu gần nhất',
            'Cac tran cua giai dau do ban to chuc quan ly.' => 'Các trận của giải đấu do ban tổ chức quản lý.',
            'Chua co lich thi dau.' => 'Chưa có lịch thi đấu.',
            'Bang xep hang nhanh' => 'Bảng xếp hạng nhanh',
            'Du lieu tu bang xep hang da cong bo moi nhat.' => 'Dữ liệu từ bảng xếp hạng đã công bố mới nhất.',
            'Chua co bang xep hang da cong bo.' => 'Chưa có bảng xếp hạng đã công bố.',
            'Tao, cap nhat va cong bo giai dau.' => 'Tạo, cập nhật và công bố giải đấu.',
            'Lap bang dau, tran dau, thoi gian va san.' => 'Lập bảng đấu, trận đấu, thời gian và sân.',
            'Dieu chinh va cong bo ket qua.' => 'Điều chỉnh và công bố kết quả.',
            'Tao va cong bo bang xep hang.' => 'Tạo và công bố bảng xếp hạng.',
            'Quan ly lich thi dau' => 'Quản lý lịch thi đấu',
            'Cong bo ket qua' => 'Công bố kết quả',
            'Tong phan cong' => 'Tổng phân công',
            'Tat ca trang thai' => 'Tất cả trạng thái',
            'Sap dien ra' => 'Sắp diễn ra',
            'Can theo doi' => 'Cần theo dõi',
            'Cho xac nhan' => 'Chờ xác nhận',
            'Can phan hoi' => 'Cần phản hồi',
            'Da gui' => 'Đã gửi',
            'Lich phan cong gan nhat' => 'Lịch phân công gần nhất',
            'Cac tran trong tai duoc phan cong.' => 'Các trận trọng tài được phân công.',
            'Tran sap phu trach' => 'Trận sắp phụ trách',
            'Thong tin nhanh de trong tai chuan bi.' => 'Thông tin nhanh để trọng tài chuẩn bị.',
            'Gan nhat' => 'Gần nhất',
            'Chua co phan cong tran dau.' => 'Chưa có phân công trận đấu.',
            'Chua co tran sap dien ra.' => 'Chưa có trận sắp diễn ra.',
            'Xem va phan hoi phan cong.' => 'Xem và phản hồi phân công.',
            'Bat dau, tam dung, tiep tuc, ket thuc tran.' => 'Bắt đầu, tạm dừng, tiếp tục, kết thúc trận.',
            'Gui bao cao su co phat sinh.' => 'Gửi báo cáo sự cố phát sinh.',
            'Tao va theo doi don nghi phep.' => 'Tạo và theo dõi đơn nghỉ phép.',
            'Lich phan cong' => 'Lịch phân công',
            'Giam sat tran dau' => 'Giám sát trận đấu',
            'Bao cao su co' => 'Báo cáo sự cố',
            'Xin nghi phep' => 'Xin nghỉ phép',
            'Doi hinh thi dau' => 'Đội hình thi đấu',
            'Thuoc HLV' => 'Thuộc HLV',
            'Thanh vien' => 'Thành viên',
            'Dang tham gia' => 'Đang tham gia',
            'Da tao' => 'Đã tạo',
            'Lich thi dau cua doi' => 'Lịch thi đấu của đội',
            'Chua co doi bong.' => 'Chưa có đội bóng.',
            'Doi thu' => 'Đối thủ',
            'Chua co lich thi dau cho doi.' => 'Chưa có lịch thi đấu cho đội.',
            'Bang xep hang lien quan' => 'Bảng xếp hạng liên quan',
            'Top doi trong giai dau gan nhat cua doi.' => 'Top đội trong giải đấu gần nhất của đội.',
            'Chua co bang xep hang lien quan.' => 'Chưa có bảng xếp hạng liên quan.',
            'Tao tai khoan van dong vien.' => 'Tạo tài khoản vận động viên.',
            'Them, xoa, chuyen vai tro thanh vien.' => 'Thêm, xóa, chuyển vai trò thành viên.',
            'Tao va cap nhat doi hinh thi dau.' => 'Tạo và cập nhật đội hình thi đấu.',
            'Dang ky doi tham gia giai.' => 'Đăng ký đội tham gia giải.',
            'Doi bong cua toi' => 'Đội bóng của tôi',
            'Doi hinh' => 'Đội hình',
            'Thanh vien doi' => 'Thành viên đội',
            'Dang ky giai' => 'Đăng ký giải',
            'Tran lien quan' => 'Trận liên quan',
            'Trong lich ca nhan' => 'Trong lịch cá nhân',
            'Diem ghi nhan' => 'Điểm ghi nhận',
            'Tong diem' => 'Tổng điểm',
            'Loi moi cho phan hoi' => 'Lời mời chờ phản hồi',
            'Don nghi phep' => 'Đơn nghỉ phép',
            'Chua co doi bong dang tham gia.' => 'Chưa có đội bóng đang tham gia.',
            'Ho so thi dau' => 'Hồ sơ thi đấu',
            'Thong tin tom tat cua van dong vien.' => 'Thông tin tóm tắt của vận động viên.',
            'Chua co doi' => 'Chưa có đội',
            'Chua co' => 'Chưa có',
            'Vi tri' => 'Vị trí',
            'Ma VDV' => 'Mã VĐV',
            'Tran da co thong ke' => 'Trận đã có thống kê',
            'Dong y hoac tu choi loi moi.' => 'Đồng ý hoặc từ chối lời mời.',
            'Xem thong tin doi bong.' => 'Xem thông tin đội bóng.',
            'Xem chi so thi dau.' => 'Xem chỉ số thi đấu.',
            'Gui yeu cau nghi thi dau.' => 'Gửi yêu cầu nghỉ thi đấu.',
            'Lich thi dau ca nhan' => 'Lịch thi đấu cá nhân',
            'Thong ke ca nhan' => 'Thống kê cá nhân',
            'Loi moi doi bong' => 'Lời mời đội bóng',
            'Nghi phep thi dau' => 'Nghỉ phép thi đấu',
            'Bang xep hang' => 'Bảng xếp hạng',
            'Ket qua' => 'Kết quả',
            'Lich thi dau' => 'Lịch thi đấu',
            'Doi bong' => 'Đội bóng',
            'Tran sap dien ra' => 'Trận sắp diễn ra',
            'Thoi gian' => 'Thời gian',
            'San dau' => 'Sân đấu',
            'San' => 'Sân',
            'Trang thai' => 'Trạng thái',
            'Giam sat' => 'Giám sát',
            'Chua dien ra' => 'Chưa diễn ra',
            'Dang dien ra' => 'Đang diễn ra',
            'Tam dung' => 'Tạm dừng',
            'Da ket thuc' => 'Đã kết thúc',
            'Da huy' => 'Đã hủy',
            'Da xac nhan' => 'Đã xác nhận',
            'Tu choi' => 'Từ chối',
            'Lam moi' => 'Làm mới',
            'Tam thoi' => 'Tạm thời',
            'Ket noi' => 'Kết nối',
            'Can kiem tra CSDL' => 'Cần kiểm tra CSDL',
            'Chuc nang' => 'Chức năng',
            'Cho duyet' => 'Chờ duyệt',
            ' thang' => ' thắng',
            ' thua' => ' thua',
            ' tran' => ' trận',
            ' dong' => ' dòng',
            ' muc' => ' mục',
        ];

        return strtr($value, $map);
    }

    private function stat(string $label, int|string $value, string $hint, string $tone, string $icon): array
    {
        return [
            'label' => $label,
            'value' => is_int($value) ? $this->number($value) : $value,
            'hint' => $hint,
            'tone' => $tone,
            'icon' => $icon,
        ];
    }

    private function matchHeroCard(?array $match, string $href = '#'): ?array
    {
        if ($match === null) {
            return [
                'title' => 'Tran sap dien ra',
                'empty' => 'Chua co tran sap dien ra.',
            ];
        }

        return [
            'title' => 'Tran sap dien ra',
            'team1' => (string) ($match['doi1'] ?? '-'),
            'team2' => (string) ($match['doi2'] ?? '-'),
            'time' => $this->dateTime($match['thoigianbatdau'] ?? null),
            'venue' => (string) ($match['tensandau'] ?? '-'),
            'tournament' => (string) ($match['tengiaidau'] ?? ''),
            'round' => (string) ($match['vongdau'] ?? ''),
            'href' => (int) ($match['idtrandau'] ?? 0) > 0 ? $href : '#',
        ];
    }

    private function matchDetailItems(?array $match): array
    {
        if ($match === null) {
            return [];
        }

        return [
            ['label' => 'Tran dau', 'value' => trim((string) $match['doi1'] . ' vs ' . (string) $match['doi2'])],
            ['label' => 'Thoi gian', 'value' => $this->dateTime($match['thoigianbatdau'] ?? null)],
            ['label' => 'San dau', 'value' => (string) $match['tensandau']],
            ['label' => 'Trang thai', 'value' => $this->matchStatusLabel((string) $match['trangthai'])],
        ];
    }

    private function organizerByAccount(int $accountId): ?array
    {
        return $this->one(
            "SELECT btc.*
             FROM Bantochuc btc
             JOIN Nguoidung nd ON nd.idnguoidung = btc.idnguoidung
             WHERE nd.idtaikhoan = :account_id
             LIMIT 1",
            ['account_id' => $accountId]
        );
    }

    private function refereeByAccount(int $accountId): ?array
    {
        return $this->one(
            "SELECT tt.*, nd.hodem, nd.ten
             FROM Trongtai tt
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             WHERE nd.idtaikhoan = :account_id
             LIMIT 1",
            ['account_id' => $accountId]
        );
    }

    private function coachByAccount(int $accountId): ?array
    {
        return $this->one(
            "SELECT hlv.*, nd.hodem, nd.ten
             FROM Huanluyenvien hlv
             JOIN Nguoidung nd ON nd.idnguoidung = hlv.idnguoidung
             WHERE nd.idtaikhoan = :account_id
             LIMIT 1",
            ['account_id' => $accountId]
        );
    }

    private function athleteByAccount(int $accountId): ?array
    {
        return $this->one(
            "SELECT vdv.*, nd.hodem, nd.ten
             FROM Vandongvien vdv
             JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
             WHERE nd.idtaikhoan = :account_id
             LIMIT 1",
            ['account_id' => $accountId]
        );
    }

    private function count(string $table, string $where = '1=1', array $bindings = []): int
    {
        $statement = $this->db->prepare("SELECT COUNT(*) AS total FROM {$table} WHERE {$where}");
        $statement->execute($bindings);
        $row = $statement->fetch();

        return (int) ($row['total'] ?? 0);
    }

    private function pendingOrganizerChanges(): int
    {
        return $this->scalarInt(
            "SELECT COUNT(*) AS total
             FROM Yeucaucapnhathoso yc
             JOIN Nguoidung nd ON nd.idnguoidung = yc.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             JOIN `Role` r ON r.idrole = tk.idrole
             WHERE yc.trangthai = 'CHO_DUYET'
               AND r.namerole = 'BAN_TO_CHUC'"
        );
    }

    private function countOrganizerTournaments(int $organizerId): int
    {
        return $organizerId > 0 ? $this->count('Giaidau', 'idbantochuc = :id', ['id' => $organizerId]) : 0;
    }

    private function countOrganizerTeams(int $organizerId): int
    {
        return $organizerId > 0 ? $this->scalarInt(
            "SELECT COUNT(DISTINCT dk.iddoibong) AS total
             FROM Dangkygiaidau dk
             JOIN Giaidau gd ON gd.idgiaidau = dk.idgiaidau
             WHERE gd.idbantochuc = :id
               AND dk.trangthai = 'DA_DUYET'",
            ['id' => $organizerId]
        ) : 0;
    }

    private function countOrganizerMatches(int $organizerId): int
    {
        return $organizerId > 0 ? $this->scalarInt(
            "SELECT COUNT(*) AS total
             FROM Trandau td
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             WHERE gd.idbantochuc = :id",
            ['id' => $organizerId]
        ) : 0;
    }

    private function countOrganizerComplaints(int $organizerId): int
    {
        return $organizerId > 0 ? $this->scalarInt(
            "SELECT COUNT(*) AS total
             FROM Khieunai kn
             JOIN Giaidau gd ON gd.idgiaidau = kn.idgiaidau
             WHERE gd.idbantochuc = :id
               AND kn.trangthai IN ('CHO_TIEP_NHAN','DANG_XU_LY')",
            ['id' => $organizerId]
        ) : 0;
    }

    private function organizerMatches(int $organizerId): array
    {
        return $this->rows(
            "SELECT td.*, gd.tengiaidau, d1.tendoibong AS doi1, d2.tendoibong AS doi2, sd.tensandau
             FROM Trandau td
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             JOIN Sandau sd ON sd.idsandau = td.idsandau
             WHERE gd.idbantochuc = :id
             ORDER BY td.thoigianbatdau ASC
             LIMIT 6",
            ['id' => $organizerId]
        );
    }

    private function nextOrganizerMatch(int $organizerId): ?array
    {
        return $this->one(
            "SELECT td.*, gd.tengiaidau, d1.tendoibong AS doi1, d2.tendoibong AS doi2, sd.tensandau
             FROM Trandau td
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             JOIN Sandau sd ON sd.idsandau = td.idsandau
             WHERE gd.idbantochuc = :id
               AND td.trangthai IN ('CHUA_DIEN_RA','SAP_DIEN_RA')
               AND td.thoigianbatdau >= CURRENT_TIMESTAMP
             ORDER BY td.thoigianbatdau ASC
             LIMIT 1",
            ['id' => $organizerId]
        );
    }

    private function latestOrganizerRankingRows(int $organizerId): array
    {
        $ranking = $this->one(
            "SELECT bxh.idbangxephang
             FROM Bangxephang bxh
             JOIN Giaidau gd ON gd.idgiaidau = bxh.idgiaidau
             WHERE gd.idbantochuc = :id
               AND bxh.trangthai = 'DA_CONG_BO'
             ORDER BY bxh.ngaycongbo DESC, bxh.idbangxephang DESC
             LIMIT 1",
            ['id' => $organizerId]
        );

        return $ranking ? $this->rankingRows((int) $ranking['idbangxephang']) : [];
    }

    private function latestTournamentRankingRows(int $tournamentId): array
    {
        $ranking = $this->one(
            "SELECT idbangxephang
             FROM Bangxephang
             WHERE idgiaidau = :id
               AND trangthai = 'DA_CONG_BO'
             ORDER BY ngaycongbo DESC, idbangxephang DESC
             LIMIT 1",
            ['id' => $tournamentId]
        );

        return $ranking ? $this->rankingRows((int) $ranking['idbangxephang']) : [];
    }

    private function rankingRows(int $rankingId): array
    {
        return $this->rows(
            "SELECT ct.hang, ct.thang, ct.thua, ct.diem, db.tendoibong
             FROM Chitietbangxephang ct
             JOIN Doibong db ON db.iddoibong = ct.iddoibong
             WHERE ct.idbangxephang = :id
             ORDER BY ct.hang ASC
             LIMIT 5",
            ['id' => $rankingId]
        );
    }

    private function refereeStats(int $refereeId): array
    {
        $row = $this->one(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN pctt.trangthai = 'CHO_XAC_NHAN' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN td.thoigianbatdau >= CURRENT_TIMESTAMP AND td.trangthai IN ('CHUA_DIEN_RA','SAP_DIEN_RA') THEN 1 ELSE 0 END) AS upcoming
             FROM Phancongtrongtai pctt
             JOIN Trandau td ON td.idtrandau = pctt.idtrandau
             WHERE pctt.idtrongtai = :id",
            ['id' => $refereeId]
        ) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'upcoming' => (int) ($row['upcoming'] ?? 0),
            'incidents' => $this->count('Baocaosuco', 'idtrongtai = :id', ['id' => $refereeId]),
        ];
    }

    private function refereeAssignments(int $refereeId): array
    {
        return $this->rows(
            "SELECT
                pctt.idphancong,
                pctt.vaitro,
                pctt.trangthai AS phancong_trangthai,
                td.*,
                gd.tengiaidau,
                d1.tendoibong AS doi1,
                d2.tendoibong AS doi2,
                sd.tensandau
             FROM Phancongtrongtai pctt
             JOIN Trandau td ON td.idtrandau = pctt.idtrandau
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             JOIN Sandau sd ON sd.idsandau = td.idsandau
             WHERE pctt.idtrongtai = :id
             ORDER BY td.thoigianbatdau ASC
             LIMIT 6",
            ['id' => $refereeId]
        );
    }

    private function nextRefereeAssignment(int $refereeId): ?array
    {
        return $this->one(
            "SELECT
                pctt.idphancong,
                pctt.vaitro,
                pctt.trangthai AS phancong_trangthai,
                td.*,
                gd.tengiaidau,
                d1.tendoibong AS doi1,
                d2.tendoibong AS doi2,
                sd.tensandau
             FROM Phancongtrongtai pctt
             JOIN Trandau td ON td.idtrandau = pctt.idtrandau
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             JOIN Sandau sd ON sd.idsandau = td.idsandau
             WHERE pctt.idtrongtai = :id
               AND pctt.trangthai IN ('CHO_XAC_NHAN','DA_XAC_NHAN')
               AND td.trangthai IN ('CHUA_DIEN_RA','SAP_DIEN_RA')
               AND td.thoigianbatdau >= CURRENT_TIMESTAMP
             ORDER BY td.thoigianbatdau ASC
             LIMIT 1",
            ['id' => $refereeId]
        );
    }

    private function coachPrimaryTeam(int $coachId): ?array
    {
        return $this->one(
            "SELECT *
             FROM Doibong
             WHERE idhuanluyenvien = :id
             ORDER BY CASE WHEN trangthai = 'HOAT_DONG' THEN 0 ELSE 1 END, ngaytao DESC
             LIMIT 1",
            ['id' => $coachId]
        );
    }

    private function countCoachTeams(int $coachId): int
    {
        return $coachId > 0 ? $this->count('Doibong', 'idhuanluyenvien = :id', ['id' => $coachId]) : 0;
    }

    private function countTeamMembers(int $teamId): int
    {
        return $teamId > 0 ? $this->count('Thanhviendoibong', "iddoibong = :id AND trangthai = 'DANG_THAM_GIA'", ['id' => $teamId]) : 0;
    }

    private function countTeamLineups(int $teamId): int
    {
        return $teamId > 0 ? $this->count('Doihinh', 'iddoibong = :id', ['id' => $teamId]) : 0;
    }

    private function countCoachAthleteRequests(int $coachId): int
    {
        return $coachId > 0 ? $this->scalarInt(
            "SELECT COUNT(DISTINCT yc.idyeucaucapnhat) AS total
             FROM Yeucaucapnhathoso yc
             JOIN Vandongvien vdv ON vdv.idnguoidung = yc.idnguoidung
             JOIN Thanhviendoibong tv ON tv.idvandongvien = vdv.idvandongvien
             JOIN Doibong db ON db.iddoibong = tv.iddoibong
             WHERE db.idhuanluyenvien = :id
               AND yc.trangthai = 'CHO_DUYET'",
            ['id' => $coachId]
        ) : 0;
    }

    private function nextTeamMatch(int $teamId): ?array
    {
        return $this->one(
            "SELECT td.*, gd.tengiaidau, d1.tendoibong AS doi1, d2.tendoibong AS doi2, sd.tensandau
             FROM Trandau td
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             JOIN Sandau sd ON sd.idsandau = td.idsandau
             WHERE (td.iddoibong1 = :id_one OR td.iddoibong2 = :id_two)
               AND td.trangthai IN ('CHUA_DIEN_RA','SAP_DIEN_RA')
               AND td.thoigianbatdau >= CURRENT_TIMESTAMP
             ORDER BY td.thoigianbatdau ASC
             LIMIT 1",
            ['id_one' => $teamId, 'id_two' => $teamId]
        );
    }

    private function teamScheduleRows(int $teamId): array
    {
        return $this->rows(
            "SELECT td.*, gd.tengiaidau, d1.tendoibong AS doi1, d2.tendoibong AS doi2, sd.tensandau
             FROM Trandau td
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             JOIN Sandau sd ON sd.idsandau = td.idsandau
             WHERE (td.iddoibong1 = :id_one OR td.iddoibong2 = :id_two)
             ORDER BY td.thoigianbatdau ASC
             LIMIT 6",
            ['id_one' => $teamId, 'id_two' => $teamId]
        );
    }

    private function athleteTeam(int $athleteId): ?array
    {
        return $this->one(
            "SELECT db.*
             FROM Thanhviendoibong tv
             JOIN Doibong db ON db.iddoibong = tv.iddoibong
             WHERE tv.idvandongvien = :id
               AND tv.trangthai = 'DANG_THAM_GIA'
             ORDER BY tv.ngaythamgia DESC
             LIMIT 1",
            ['id' => $athleteId]
        );
    }

    private function athleteScheduleRows(int $athleteId): array
    {
        return $this->rows(
            "SELECT DISTINCT td.*, gd.tengiaidau, d1.tendoibong AS doi1, d2.tendoibong AS doi2, sd.tensandau
             FROM Thanhviendoibong tv
             JOIN Trandau td ON td.iddoibong1 = tv.iddoibong OR td.iddoibong2 = tv.iddoibong
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             JOIN Sandau sd ON sd.idsandau = td.idsandau
             WHERE tv.idvandongvien = :id
               AND tv.trangthai = 'DANG_THAM_GIA'
             ORDER BY td.thoigianbatdau ASC
             LIMIT 6",
            ['id' => $athleteId]
        );
    }

    private function athleteStats(int $athleteId): array
    {
        $row = $this->one(
            "SELECT
                COUNT(DISTINCT idtrandau) AS matches,
                COALESCE(SUM(sodiem), 0) AS points,
                COALESCE(SUM(solanphatbong), 0) AS serves,
                COALESCE(SUM(solanchanbong), 0) AS blocks
             FROM Thongkecanhan
             WHERE idvandongvien = :id",
            ['id' => $athleteId]
        ) ?: [];

        return [
            'matches' => (int) ($row['matches'] ?? 0),
            'points' => (int) ($row['points'] ?? 0),
            'serves' => (int) ($row['serves'] ?? 0),
            'blocks' => (int) ($row['blocks'] ?? 0),
        ];
    }

    private function countAthleteInvitations(int $athleteId): int
    {
        return $athleteId > 0 ? $this->count('Loimoidoibong', "idvandongvien = :id AND trangthai = 'CHO_PHAN_HOI'", ['id' => $athleteId]) : 0;
    }

    private function countAthleteLeaves(int $athleteId): int
    {
        return $athleteId > 0 ? $this->count('Donnghivandongvien', "idvandongvien = :id AND trangthai = 'CHO_DUYET'", ['id' => $athleteId]) : 0;
    }

    private function scalarInt(string $sql, array $bindings = []): int
    {
        $row = $this->one($sql, $bindings);

        return (int) ($row['total'] ?? 0);
    }

    private function rows(string $sql, array $bindings = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    private function one(string $sql, array $bindings = []): ?array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($bindings);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    private function recordDashboardLog(int $accountId, string $role, ?string $ipAddress): void
    {
        if ($accountId <= 0) {
            return;
        }

        try {
            $statement = $this->db->prepare(
                "INSERT INTO Nhatkyhethong (idtaikhoan, hanhdong, bangtacdong, iddoituong, ipaddress, ghichu)
                 VALUES (:account_id, 'Xem trang chu dashboard', 'Dashboard', NULL, :ip_address, :note)"
            );
            $statement->execute([
                'account_id' => $accountId,
                'ip_address' => $ipAddress,
                'note' => 'Tai khoan #' . $accountId . ' role ' . $role . ' xem trang chu.',
            ]);
        } catch (Throwable) {
            // Dashboard logging must not block page rendering.
        }
    }

    private function dateTime(mixed $value): string
    {
        if ($value === null || (string) $value === '') {
            return '-';
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? (string) $value : date('d/m/Y H:i', $timestamp);
    }

    private function number(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    private function roleLabel(string $role): string
    {
        return [
            'ADMIN' => 'Quan tri vien',
            'BAN_TO_CHUC' => 'Ban to chuc',
            'TRONG_TAI' => 'Trong tai',
            'HUAN_LUYEN_VIEN' => 'Huan luyen vien',
            'VAN_DONG_VIEN' => 'Van dong vien',
        ][$role] ?? $role;
    }

    private function matchStatusLabel(string $status): string
    {
        return [
            'CHUA_DIEN_RA' => 'Chua dien ra',
            'SAP_DIEN_RA' => 'Sap dien ra',
            'TRONG_TAI_TRE_GIAM_SAT' => 'Trong tai tre giam sat',
            'DANG_DIEN_RA' => 'Dang dien ra',
            'TAM_DUNG' => 'Tam dung',
            'DA_KET_THUC' => 'Da ket thuc',
            'DA_HUY' => 'Da huy',
            'DA_HUY_KHONG_CO_GIAM_SAT' => 'Da huy do khong co giam sat',
        ][$status] ?? $status;
    }

    private function assignmentRoleLabel(string $role): string
    {
        return [
            'TRONG_TAI_CHINH' => 'Trong tai chinh',
            'TRONG_TAI_PHU' => 'Trong tai phu',
            'GIAM_SAT' => 'Giam sat',
        ][$role] ?? $role;
    }

    private function assignmentStatusLabel(string $status): string
    {
        return [
            'CHO_XAC_NHAN' => 'Cho xac nhan',
            'DA_XAC_NHAN' => 'Da xac nhan',
            'TU_CHOI' => 'Tu choi',
            'DA_HUY' => 'Da huy',
        ][$status] ?? $status;
    }
}

