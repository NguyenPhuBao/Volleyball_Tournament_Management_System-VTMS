<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Services\Shared\VolleyballCompetitionRules;
use RuntimeException;
use Throwable;

final class OrganizerTournamentService
{
    private const TOURNAMENT_STATUSES = ['NHAP', 'CHUA_CONG_BO', 'DA_CONG_BO', 'DANG_DIEN_RA', 'DA_KET_THUC', 'DA_HUY'];
    private const TOURNAMENT_REGISTRATION_STATUSES = ['CHUA_MO', 'DANG_MO', 'DA_DONG'];
    private const REGISTRATION_STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI', 'DA_HUY'];
    private const FORMAT_RULE_TITLE = 'Cấu hình thể thức thi đấu';
    private const SYSTEM_RULE_TITLES = [self::FORMAT_RULE_TITLE];

    public function __construct(private ?Giaidau $tournaments = null)
    {
        $this->tournaments ??= new Giaidau();
    }

    public function all(int $accountId, array $filters = []): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizerId = (int) $organizerResult['idbantochuc'];
        $this->tournaments->syncStartedPublishedTournaments($organizerId);

        $normalizedFilters = $this->tournamentFilters($filters);

        if (!empty($normalizedFilters['errors'])) {
            return $this->failure('Bo loc giai dau khong hop le.', 422, $normalizedFilters['errors']);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach giai dau thanh cong.',
            'tournaments' => $this->tournaments->listForOrganizer($organizerId, $normalizedFilters['filters']),
            'meta' => [
                'filters' => $normalizedFilters['filters'],
            ],
        ];
    }

    public function locations(int $accountId, array $filters = []): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $keyword = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? 'HOAT_DONG'));

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach vi tri thi dau thanh cong.',
            'locations' => $this->tournaments->competitionLocations((int) $organizerResult['idbantochuc'], [
                'q' => $keyword,
                'status' => $status,
            ]),
            'meta' => [
                'filters' => [
                    'q' => $keyword,
                    'status' => $status,
                ],
            ],
        ];
    }

    public function options(int $accountId): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizerId = (int) $organizerResult['idbantochuc'];

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lấy cấu hình tạo giải đấu thành công.',
            'options' => [
                'organizer' => [
                    'idbantochuc' => $organizerId,
                    'idcapbantochuc' => (int) $organizerResult['idcapbantochuc'],
                    'idkhuvucquanly' => (int) $organizerResult['idkhuvucquanly'],
                    'capkhuvucquanly' => (string) $organizerResult['capkhuvucquanly'],
                    'tenkhuvucquanly' => (string) $organizerResult['tenkhuvucquanly'],
                ],
                'levels' => $this->tournaments->allowedTournamentLevelsForOrganizer($organizerId),
                'achievement_levels' => $this->tournaments->tournamentLevels(),
                'regions' => $this->tournaments->manageableRegionsForOrganizer($organizerId),
                'rules' => $this->tournaments->activeCompetitionRules(),
                'tinhchat' => ['CHINH_THUC', 'GIAO_HUU', 'PHONG_TRAO', 'NOI_BO', 'MO_RONG'],
                'cach_xep_cap' => ['RANDOM', 'MANUAL', 'HYBRID'],
                'seed_sources' => ['BANG_XEP_HANG_TRUOC', 'THU_HANG_VONG_TRUOC', 'DIEM_TICH_LUY', 'BTC_NHAP_TAY', 'KHONG_AP_DUNG'],
                'che_do_chon_doi' => ['BTC_CHON_THU_CONG', 'DANG_KY_THU_CONG', 'KET_HOP'],
            ],
        ];
    }

    public function eligibilityPreview(array $payload, int $accountId): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $errors = [];
        $levelId = $this->positiveInt($payload['idcapgiaidau'] ?? null, 'idcapgiaidau', 'Cấp giải đấu', $errors);
        $regionId = $this->positiveInt($payload['idkhuvucphamvi'] ?? null, 'idkhuvucphamvi', 'Khu vực phạm vi', $errors);
        [$level, $region] = $this->validateLevelAndRegion((int) $organizerResult['idbantochuc'], $levelId, $regionId, $errors);
        $conditions = $this->participationConditionsFromPayload($payload, $level, $errors);

        if ($errors !== []) {
            return $this->failure('Điều kiện tham gia chưa hợp lệ.', 422, $errors);
        }

        $activeTeamCount = $this->tournaments->activeTeamCountForScope((int) $levelId, (int) $regionId);
        $eligibleTeamCount = $this->tournaments->eligibleTeamCountForCriteria((int) $levelId, (int) $regionId, $conditions);
        $allowException = $this->conditionsAllowException($conditions);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Tính số đội hợp lệ thành công.',
            'preview' => [
                'participant_team_type' => (string) ($conditions[0]['capdoituongthamgia'] ?? $level['capdoituongthamgia'] ?? ''),
                'active_team_count' => $activeTeamCount,
                'eligible_team_count' => $eligibleTeamCount,
                'max_team_count' => max(64, $activeTeamCount, $eligibleTeamCount),
                'allow_exception' => $allowException,
                'region' => $region,
            ],
        ];
    }

    public function create(array $payload, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizer = $organizerResult;
        [$tournament, $configuration, $errors] = $this->validatePayload($payload, $organizer);

        if ($errors !== []) {
            return $this->failure('Du lieu giai dau khong hop le.', 422, $errors);
        }

        if ($this->tournaments->existsByNameAndStartDate($tournament['tengiaidau'], $tournament['thoigianbatdau'])) {
            return $this->failure('Giai dau da ton tai voi ten va ngay bat dau nay.', 409, [
                'tengiaidau' => 'Ten giai dau va ngay bat dau da ton tai.',
            ]);
        }

        $tournament['idbantochuc'] = (int) $organizer['idbantochuc'];
        $logNote = sprintf(
            'Ban to chuc #%d tao giai dau "%s" theo cap #%d, khu vuc #%d.',
            (int) $organizer['idbantochuc'],
            $tournament['tengiaidau'],
            (int) $tournament['idcapgiaidau'],
            (int) $tournament['idkhuvucphamvi']
        );

        try {
            $tournamentId = $this->tournaments->createTournament(
                $tournament,
                $configuration,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Tao giai dau thanh cong.',
                'tournament' => $this->withRules($tournamentId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the tao giai dau.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function find(int $tournamentId, int $accountId): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $tournament = $this->withRules($tournamentId);

        if ($tournament === null || (int) $tournament['idbantochuc'] !== (int) $organizerResult['idbantochuc']) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin giai dau thanh cong.',
            'tournament' => $tournament,
        ];
    }

    public function update(int $tournamentId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizer = $organizerResult;
        $current = $this->withRules($tournamentId);

        if ($current === null || (int) $current['idbantochuc'] !== (int) $organizer['idbantochuc']) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        if (!$this->canUpdateTournamentBeforeStart($current)) {
            return $this->failure('Chỉ được cập nhật giải đấu khi chưa tới ngày bắt đầu và giải chưa diễn ra/kết thúc/hủy.', 409);
        }

        [$tournament, $configuration, $errors, $changedFields] = $this->validateUpdatePayload($payload, $current, $organizer);

        if ($errors !== []) {
            return $this->failure('Du lieu cap nhat giai dau khong hop le.', 422, $errors);
        }

        $name = $tournament['tengiaidau'] ?? (string) $current['tengiaidau'];
        $startDate = $tournament['thoigianbatdau'] ?? (string) $current['thoigianbatdau'];

        if ($this->tournaments->existsByNameAndStartDate($name, $startDate, $tournamentId)) {
            return $this->failure('Giai dau da ton tai voi ten va ngay bat dau nay.', 409, [
                'tengiaidau' => 'Ten giai dau va ngay bat dau da ton tai.',
            ]);
        }

        $logNote = sprintf(
            'Ban to chuc #%d cap nhat giai dau "%s". Truong thay doi: %s.',
            (int) $organizer['idbantochuc'],
            $name,
            implode(', ', $changedFields)
        );

        try {
            $this->tournaments->updateTournament(
                $tournamentId,
                $tournament,
                $configuration,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cap nhat giai dau thanh cong.',
                'tournament' => $this->withRules($tournamentId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'TOURNAMENT_NOT_UPDATED') {
                return $this->failure('Chỉ được cập nhật giải đấu ở trạng thái nháp/chưa công bố.', 409);
            }

            return $this->failure('Khong the cap nhat giai dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat giai dau.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function delete(int $tournamentId, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizer = $organizerResult;
        $current = $this->withRules($tournamentId);

        if ($current === null || (int) $current['idbantochuc'] !== (int) $organizer['idbantochuc']) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        if (!in_array((string) $current['trangthai'], ['NHAP', 'CHUA_CONG_BO'], true)) {
            return $this->failure('Chỉ được xóa giải đấu ở trạng thái nháp/chưa công bố.', 409);
        }

        $logNote = sprintf(
            'Ban to chuc #%d xoa giai dau "%s" dang o trang thai CHUA_CONG_BO.',
            (int) $organizer['idbantochuc'],
            (string) $current['tengiaidau']
        );

        try {
            $this->tournaments->deleteTournament($tournamentId, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Xoa giai dau thanh cong.',
                'deleted_id' => $tournamentId,
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'TOURNAMENT_NOT_DELETED') {
                return $this->failure('Chỉ được xóa giải đấu ở trạng thái nháp/chưa công bố.', 409);
            }

            return $this->failure('Khong the xoa giai dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xoa giai dau. Co the giai dau dang co du lieu lien quan.', 409, [
                'database' => 'Loi xoa du lieu hoac rang buoc khoa ngoai.',
            ]);
        }
    }

    public function publish(int $tournamentId, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizer = $organizerResult;
        $current = $this->withRules($tournamentId);

        if ($current === null || (int) $current['idbantochuc'] !== (int) $organizer['idbantochuc']) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        if (!in_array((string) $current['trangthai'], ['NHAP', 'CHUA_CONG_BO'], true)) {
            return $this->failure('Chỉ được công bố giải đấu ở trạng thái nháp/chưa công bố.', 409);
        }

        $errors = $this->validatePublishableTournament($current);

        if ($errors !== []) {
            return $this->failure('Giai dau chua du thong tin de cong bo.', 422, $errors);
        }

        $logNote = sprintf(
            'Ban to chuc #%d cong bo giai dau "%s".',
            (int) $organizer['idbantochuc'],
            (string) $current['tengiaidau']
        );

        try {
            $this->tournaments->publishTournament($tournamentId, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cong bo giai dau thanh cong.',
                'tournament' => $this->withRules($tournamentId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'TOURNAMENT_NOT_PUBLISHED') {
                return $this->failure('Chỉ được công bố giải đấu ở trạng thái nháp/chưa công bố.', 409);
            }

            return $this->failure('Khong the cong bo giai dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cong bo giai dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function cancel(int $tournamentId, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizer = $organizerResult;
        $current = $this->withRules($tournamentId);

        if ($current === null || (int) $current['idbantochuc'] !== (int) $organizer['idbantochuc']) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        if ((string) $current['trangthai'] !== 'DA_CONG_BO') {
            return $this->failure('Chỉ được hủy giải đấu đã công bố.', 409);
        }

        $reason = trim((string) ($request?->input('lydo', $request?->input('reason', 'BTC huy giai dau da cong bo')) ?? ''));
        $reason = $reason !== '' ? $reason : 'BTC huy giai dau da cong bo';
        $logNote = sprintf(
            'Ban to chuc #%d huy giai dau da cong bo "%s".',
            (int) $organizer['idbantochuc'],
            (string) $current['tengiaidau']
        );

        try {
            $this->tournaments->cancelPublishedTournament($tournamentId, $accountId, $request?->ip(), $logNote, $reason);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Huy giai dau thanh cong.',
                'tournament' => $this->withRules($tournamentId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'TOURNAMENT_NOT_CANCELED') {
                return $this->failure('Chỉ được hủy giải đấu đã công bố.', 409);
            }

            return $this->failure('Khong the huy giai dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the huy giai dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function registrations(int $tournamentId, int $accountId, array $filters = []): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizer = $organizerResult;
        $current = $this->withRules($tournamentId);

        if ($current === null || (int) $current['idbantochuc'] !== (int) $organizer['idbantochuc']) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        $normalizedFilters = $this->registrationFilters($filters);

        if (!empty($normalizedFilters['errors'])) {
            return $this->failure('Bo loc danh sach dang ky khong hop le.', 422, $normalizedFilters['errors']);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach dang ky giai dau thanh cong.',
            'registrations' => $this->tournaments->registrationsForTournament($tournamentId, $normalizedFilters['filters']),
            'meta' => [
                'tournament' => [
                    'idgiaidau' => (int) $current['idgiaidau'],
                    'tengiaidau' => (string) $current['tengiaidau'],
                    'trangthai' => (string) $current['trangthai'],
                    'trangthaidangky' => (string) $current['trangthaidangky'],
                    'quymo' => (int) $current['quymo'],
                ],
                'stats' => $this->tournaments->registrationStatsForTournament($tournamentId),
            ],
        ];
    }

    public function openRegistrations(int $tournamentId, int $accountId, ?Request $request = null): array
    {
        return $this->changeRegistrationWindow($tournamentId, $accountId, 'DANG_MO', $request);
    }

    public function closeRegistrations(int $tournamentId, int $accountId, ?Request $request = null): array
    {
        return $this->changeRegistrationWindow($tournamentId, $accountId, 'DA_DONG', $request);
    }

    public function approveRegistration(int $tournamentId, int $registrationId, int $accountId, ?Request $request = null): array
    {
        return $this->decideRegistration($tournamentId, $registrationId, $accountId, 'DA_DUYET', null, $request);
    }

    public function rejectRegistration(int $tournamentId, int $registrationId, array $payload, int $accountId, ?Request $request = null): array
    {
        $reason = trim((string) ($payload['lydotuchoi'] ?? $payload['ly_do'] ?? $payload['reason'] ?? $payload['note'] ?? ''));

        if ($reason === '') {
            return $this->failure('Ly do tu choi la bat buoc.', 422, [
                'lydotuchoi' => 'Can nhap ly do tu choi.',
            ]);
        }

        if (strlen($reason) > 1000) {
            return $this->failure('Ly do tu choi khong hop le.', 422, [
                'lydotuchoi' => 'Ly do tu choi khong duoc vuot qua 1000 ky tu.',
            ]);
        }

        return $this->decideRegistration($tournamentId, $registrationId, $accountId, 'TU_CHOI', $reason, $request);
    }

    public function removeRegistration(int $tournamentId, int $registrationId, array $payload, int $accountId, ?Request $request = null): array
    {
        $reason = trim((string) ($payload['lydotuchoi'] ?? $payload['ly_do'] ?? $payload['reason'] ?? $payload['note'] ?? 'BTC loai doi thi dau'));

        if (strlen($reason) > 1000) {
            return $this->failure('Ly do loai doi khong hop le.', 422, [
                'lydotuchoi' => 'Ly do loai doi khong duoc vuot qua 1000 ky tu.',
            ]);
        }

        return $this->decideRegistration($tournamentId, $registrationId, $accountId, 'DA_HUY', $reason, $request, 'DA_DUYET');
    }

    private function validatePayload(array $payload, array $organizer): array
    {
        $errors = [];
        $levelId = $this->positiveInt($payload['idcapgiaidau'] ?? $payload['capgiaidau_id'] ?? null, 'idcapgiaidau', 'Cấp giải đấu', $errors);
        $regionId = $this->positiveInt($payload['idkhuvucphamvi'] ?? $payload['khuvuc_id'] ?? null, 'idkhuvucphamvi', 'Khu vực phạm vi', $errors);
        $lawId = $this->positiveInt($payload['idluat'] ?? $payload['luat_id'] ?? null, 'idluat', 'Luật thi đấu', $errors);
        $regulationSource = $this->arraySource($payload['dieule'] ?? []);
        $scale = $this->positiveInt(
            $regulationSource['so_doi_toi_da'] ?? $payload['so_doi_toi_da'] ?? $payload['quymo'] ?? $payload['quy_mo'] ?? $payload['scale'] ?? null,
            'quymo',
            'Quy mô',
            $errors
        );

        [$level, $region] = $this->validateLevelAndRegion((int) $organizer['idbantochuc'], $levelId, $regionId, $errors);
        $this->validateCompetitionRule($lawId, $errors);
        $activeTeamCount = ($levelId !== null && $regionId !== null)
            ? $this->tournaments->activeTeamCountForScope($levelId, $regionId)
            : 0;
        $conditions = $this->participationConditionsFromPayload($payload, $level, $errors);
        $eligibleTeamCount = ($levelId !== null && $regionId !== null && $conditions !== [])
            ? $this->tournaments->eligibleTeamCountForCriteria($levelId, $regionId, $conditions)
            : 0;

        $tournament = [
            'tengiaidau' => $this->requiredString($payload, ['tengiaidau', 'ten'], 300, 'Tên giải đấu', $errors),
            'mota' => $this->nullableString($payload['mota'] ?? $payload['description'] ?? null, 1000, 'Mô tả', 'mota', $errors),
            'idcapgiaidau' => $levelId,
            'idkhuvucphamvi' => $regionId,
            'idluat' => $lawId,
            'thoigianbatdau' => $this->dateValue($payload['thoigianbatdau'] ?? $payload['ngaybatdau'] ?? $payload['start_date'] ?? null, 'thoigianbatdau', 'Thời gian bắt đầu', $errors),
            'thoigianketthuc' => $this->dateValue($payload['thoigianketthuc'] ?? $payload['ngayketthuc'] ?? $payload['end_date'] ?? null, 'thoigianketthuc', 'Thời gian kết thúc', $errors),
            'quymo' => $scale,
            'hinhanh' => $this->nullableString($payload['hinhanh'] ?? $payload['image'] ?? null, 500, 'Hình ảnh', 'hinhanh', $errors),
            'tinhchat' => $this->enumValue($payload['tinhchat'] ?? $payload['tinh_chat'] ?? 'CHINH_THUC', ['CHINH_THUC', 'GIAO_HUU', 'PHONG_TRAO', 'NOI_BO', 'MO_RONG'], 'tinhchat', $errors),
            'gioitinh' => $this->enumValue($payload['gioitinh'] ?? $payload['gender'] ?? 'NAM', ['NAM', 'NU'], 'gioitinh', $errors),
            'ghichu_diadiem' => $this->nullableString($payload['ghichu_diadiem'] ?? $payload['location_note'] ?? null, 500, 'Ghi chú địa điểm', 'ghichu_diadiem', $errors),
        ];

        if ($tournament['thoigianbatdau'] !== null && $tournament['thoigianketthuc'] !== null && $tournament['thoigianketthuc'] <= $tournament['thoigianbatdau']) {
            $errors['thoigianketthuc'] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        }

        if ($scale !== null && $scale < 2) {
            $errors['quymo'] = 'Quy mô giải phải từ 2 đội trở lên.';
        }

        $configuration = [
            'dieule' => $this->regulationFromPayload($payload, $scale ?? 2, $errors, $level),
            'thethuc' => $this->structuredCompetitionFormatFromPayload($payload, $errors),
            'quytac' => $this->teamSelectionRuleFromPayload($payload, $level, $scale, $conditions, $errors),
            'dieukien' => $conditions,
        ];

        return [$tournament, $configuration, $errors];
    }

    private function validateUpdatePayload(array $payload, array $current, array $organizer): array
    {
        [$tournament, $configuration, $errors] = $this->validatePayload($payload, $organizer);

        $changedFields = [
            'tengiaidau',
            'mota',
            'idcapgiaidau',
            'idkhuvucphamvi',
            'idluat',
            'thoigianbatdau',
            'thoigianketthuc',
            'quymo',
            'hinhanh',
            'tinhchat',
            'gioitinh',
            'ghichu_diadiem',
            'dieule',
            'thethuc',
            'quytac',
            'dieukien',
        ];

        return [$tournament, $configuration, $errors, $changedFields];
    }

    private function canUpdateTournamentBeforeStart(array $tournament): bool
    {
        $status = (string) ($tournament['trangthai'] ?? '');

        if (in_array($status, ['DANG_DIEN_RA', 'DA_KET_THUC', 'DA_HUY'], true)) {
            return false;
        }

        $startDate = trim((string) ($tournament['thoigianbatdau'] ?? ''));

        if ($startDate === '') {
            return false;
        }

        $normalized = str_replace('T', ' ', $startDate);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
            $normalized .= ' 00:00:00';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
            $normalized .= ':00';
        }

        $now = new \DateTimeImmutable('now');
        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $normalized);

        if (!$start instanceof \DateTimeImmutable) {
            return false;
        }

        return $start > $now;
    }

    private function arraySource(mixed $source): array
    {
        if (is_string($source)) {
            $decoded = json_decode($source, true);
            return is_array($decoded) ? $decoded : ['noidung' => $source];
        }

        return is_array($source) ? $source : [];
    }

    private function validateLevelAndRegion(int $organizerId, ?int $levelId, ?int $regionId, array &$errors): array
    {
        $level = null;
        $region = null;

        if ($levelId !== null) {
            foreach ($this->tournaments->allowedTournamentLevelsForOrganizer($organizerId) as $item) {
                if ((int) $item['idcapgiaidau'] === $levelId) {
                    $level = $item;
                    break;
                }
            }

            if ($level === null) {
                $errors['idcapgiaidau'] = 'Cấp giải đấu không thuộc quyền tạo của ban tổ chức hiện tại.';
            }
        }

        if ($regionId !== null && $levelId !== null) {
            foreach ($this->tournaments->manageableRegionsForOrganizer($organizerId, $levelId) as $item) {
                if ((int) $item['idkhuvuc'] === $regionId) {
                    $region = $item;
                    break;
                }
            }

            if ($region === null) {
                $errors['idkhuvucphamvi'] = 'Khu vực phạm vi không khớp cấp giải hoặc không thuộc quyền quản lý của ban tổ chức.';
            }
        }

        return [$level, $region];
    }

    private function validateCompetitionRule(?int $ruleId, array &$errors): void
    {
        if ($ruleId === null) {
            return;
        }

        foreach ($this->tournaments->activeCompetitionRules() as $rule) {
            if ((int) $rule['idluat'] === $ruleId) {
                return;
            }
        }

        $errors['idluat'] = 'Luật thi đấu không tồn tại hoặc không hoạt động.';
    }

    private function regulationFromPayload(array $payload, int $scale, array &$errors, ?array $level = null): array
    {
        $source = $this->arraySource($payload['dieule'] ?? []);

        $minTeams = $this->optionalPositiveInt($source['so_doi_toi_thieu'] ?? $payload['so_doi_toi_thieu'] ?? 2, 'dieule.so_doi_toi_thieu', $errors, 2);
        $maxTeams = $this->optionalPositiveInt($source['so_doi_toi_da'] ?? $payload['so_doi_toi_da'] ?? $scale, 'dieule.so_doi_toi_da', $errors, max(2, $scale));
        $minPlayers = $this->optionalPositiveInt($source['so_vdv_toi_thieu_moi_doi'] ?? $payload['so_vdv_toi_thieu_moi_doi'] ?? 6, 'dieule.so_vdv_toi_thieu_moi_doi', $errors, 6);
        $maxPlayers = $this->optionalPositiveInt($source['so_vdv_toi_da_moi_doi'] ?? $payload['so_vdv_toi_da_moi_doi'] ?? 14, 'dieule.so_vdv_toi_da_moi_doi', $errors, 14);
        $fee = $this->nonNegativeMoneyString($source['le_phi_tham_gia'] ?? $payload['le_phi_tham_gia'] ?? 0, 'dieule.le_phi_tham_gia', $errors);
        $allowedTeamType = $this->nullableString($source['loai_doi_duoc_tham_gia'] ?? $payload['loai_doi_duoc_tham_gia'] ?? null, 300, 'Loại đội bóng được phép tham gia', 'dieule.loai_doi_duoc_tham_gia', $errors);
        $content = $this->nullableString($source['noidung'] ?? $payload['noidung_dieule'] ?? $payload['m_rule_content'] ?? null, 2600, 'Nội dung điều lệ', 'dieule.noidung', $errors);

        if ($minTeams < 2) {
            $errors['dieule.so_doi_toi_thieu'] = 'Số đội tối thiểu phải từ 2 trở lên.';
        }

        if ($maxTeams < $minTeams) {
            $errors['dieule.so_doi_toi_da'] = 'Số đội tối đa phải lớn hơn hoặc bằng số đội tối thiểu.';
        }

        if ($scale > $maxTeams) {
            $errors['quymo'] = 'Quy mô giải không được lớn hơn số đội tối đa trong điều lệ.';
        }

        if ($minPlayers < 6 || $minPlayers > 14) {
            $errors['dieule.so_vdv_toi_thieu_moi_doi'] = 'Số VĐV tối thiểu mỗi đội phải từ 6 đến 14.';
        }

        if ($maxPlayers < 6 || $maxPlayers > 14) {
            $errors['dieule.so_vdv_toi_da_moi_doi'] = 'Số VĐV tối đa mỗi đội phải từ 6 đến 14.';
        }

        if ($maxPlayers < $minPlayers) {
            $errors['dieule.so_vdv_toi_da_moi_doi'] = 'Số VĐV tối đa mỗi đội phải lớn hơn hoặc bằng số tối thiểu.';
        }

        return [
            'tieude' => $this->nullableString($source['tieude'] ?? $payload['tieude_dieule'] ?? null, 300, 'Tiêu đề điều lệ', 'dieule.tieude', $errors) ?? 'Điều lệ giải đấu',
            'noidung' => $this->composeRegulationContent($content, $fee, $allowedTeamType),
            'filedinhkem' => $this->nullableString($source['filedinhkem'] ?? $payload['filedinhkem'] ?? null, 500, 'File đính kèm', 'dieule.filedinhkem', $errors),
            'so_doi_toi_thieu' => $minTeams,
            'so_doi_toi_da' => $maxTeams,
            'so_vdv_toi_thieu_moi_doi' => $minPlayers,
            'so_vdv_toi_da_moi_doi' => $maxPlayers,
            'thoi_gian_mo_dang_ky' => $this->nullableDateTime($source['thoi_gian_mo_dang_ky'] ?? $payload['thoi_gian_mo_dang_ky'] ?? null, 'dieule.thoi_gian_mo_dang_ky', $errors),
            'thoi_gian_dong_dang_ky' => $this->nullableDateTime($source['thoi_gian_dong_dang_ky'] ?? $payload['thoi_gian_dong_dang_ky'] ?? null, 'dieule.thoi_gian_dong_dang_ky', $errors),
            'cho_phep_dang_ky_tu_do' => $this->boolInt($source['cho_phep_dang_ky_tu_do'] ?? $payload['cho_phep_dang_ky_tu_do'] ?? true),
            'yeu_cau_duyet_dang_ky' => $this->isLowestTournamentLevel($level) ? 1 : $this->boolInt($source['yeu_cau_duyet_dang_ky'] ?? $payload['yeu_cau_duyet_dang_ky'] ?? true),
            'quy_dinh_bo_cuoc' => $this->nullableString($source['quy_dinh_bo_cuoc'] ?? $payload['quy_dinh_bo_cuoc'] ?? null, 1000, 'Quy định bỏ cuộc', 'dieule.quy_dinh_bo_cuoc', $errors),
            'quy_dinh_khieu_nai' => $this->nullableString($source['quy_dinh_khieu_nai'] ?? $payload['quy_dinh_khieu_nai'] ?? null, 1000, 'Quy định khiếu nại', 'dieule.quy_dinh_khieu_nai', $errors),
        ];
    }

    private function structuredCompetitionFormatFromPayload(array $payload, array &$errors): array
    {
        $source = $payload['thethuc'] ?? $payload['competition_format'] ?? [];

        if (is_string($source)) {
            $decoded = json_decode($source, true);
            $source = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($source)) {
            $source = [];
        }

        $formatType = $this->enumValue(
            $source['loai_the_thuc'] ?? $payload['loai_the_thuc'] ?? $this->formatTypeFromFlags($source),
            ['VONG_DIEM', 'VONG_LOAI', 'KET_HOP'],
            'thethuc.loai_the_thuc',
            $errors
        );
        $hasPointRound = in_array($formatType, ['VONG_DIEM', 'KET_HOP'], true);
        $hasKnockoutRound = in_array($formatType, ['VONG_LOAI', 'KET_HOP'], true);
        $formatLabel = match ($formatType) {
            'VONG_DIEM' => 'Vòng điểm',
            'VONG_LOAI' => 'Vòng loại trực tiếp',
            default => 'Vòng điểm và vòng loại trực tiếp',
        };

        return [
            'tenthethuc' => $formatLabel,
            'tong_so_vong' => $formatType === 'KET_HOP' ? 2 : 1,
            'co_vong_diem' => $hasPointRound ? 1 : 0,
            'co_vong_loai' => $hasKnockoutRound ? 1 : 0,
            'co_tranh_hang_ba' => $hasKnockoutRound ? $this->boolInt($source['co_tranh_hang_ba'] ?? $payload['co_tranh_hang_ba'] ?? true) : 0,
            'cach_xep_mac_dinh' => $this->enumValue($source['cach_xep_mac_dinh'] ?? $payload['cach_xep_mac_dinh'] ?? 'HYBRID', ['RANDOM', 'MANUAL', 'HYBRID'], 'thethuc.cach_xep_mac_dinh', $errors),
            'seed_source_mac_dinh' => $this->enumValue($source['seed_source_mac_dinh'] ?? $payload['seed_source_mac_dinh'] ?? 'BTC_NHAP_TAY', ['BANG_XEP_HANG_TRUOC', 'THU_HANG_VONG_TRUOC', 'DIEM_TICH_LUY', 'BTC_NHAP_TAY', 'KHONG_AP_DUNG'], 'thethuc.seed_source_mac_dinh', $errors),
            'mota' => $this->nullableString($source['mota'] ?? $payload['mota_thethuc'] ?? null, 2000, 'Mô tả thể thức', 'thethuc.mota', $errors),
            'trangthai' => 'DANG_THIET_LAP',
        ];
    }

    private function teamSelectionRuleFromPayload(array $payload, ?array $level, ?int $scale, array $conditions, array &$errors): array
    {
        $source = $payload['quytac'] ?? $payload['quytacchondoi'] ?? [];

        if (is_string($source)) {
            $decoded = json_decode($source, true);
            $source = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($source)) {
            $source = [];
        }

        $participantLevel = $this->participantLevelFromPayload($source, $level, 'quytac.capdoituongthamgia', $errors);
        $eligibility = $this->representativeEligibilityFromConditions($conditions);

        return [
            'chedochondoi' => $this->enumValue($source['chedochondoi'] ?? $payload['chedochondoi'] ?? 'DANG_KY_THU_CONG', ['DANG_KY_THU_CONG', 'BTC_CHON_THU_CONG', 'KET_HOP'], 'quytac.chedochondoi', $errors),
            'capdoituongthamgia' => $this->enumValue($participantLevel, $this->participantLevelCodes(), 'quytac.capdoituongthamgia', $errors),
            'yeu_cau_thanh_tich' => $eligibility['yeu_cau_thanh_tich'],
            'idcapgiaidau_thanh_tich_nguon' => $eligibility['idcapgiaidau_thanh_tich_nguon'],
            'hang_toi_thieu_duoc_phep' => $eligibility['hang_toi_thieu_duoc_phep'],
            'so_mua_giai_gan_nhat_duoc_tinh' => $eligibility['so_mua_giai_gan_nhat_duoc_tinh'],
            'cho_phep_btc_duyet_ngoai_le' => $eligibility['cho_phep_btc_duyet_ngoai_le'],
            'soluongdoitoida' => $this->optionalNullablePositiveInt($source['soluongdoitoida'] ?? $payload['soluongdoitoida'] ?? $scale, 'quytac.soluongdoitoida', $errors),
            'mota' => $this->nullableString($source['mota'] ?? $payload['mota_quytac'] ?? null, 1000, 'Mô tả quy tắc chọn đội', 'quytac.mota', $errors),
            'trangthai' => 'HOAT_DONG',
        ];
    }

    private function participationConditionsFromPayload(array $payload, ?array $level, array &$errors): array
    {
        $source = $payload['dieukien'] ?? $payload['dieukienthamgiagiai'] ?? [];

        if (is_string($source)) {
            $decoded = json_decode($source, true);
            $source = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($source)) {
            $source = [];
        }

        $requirements = ['KHONG_YEU_CAU'];

        $conditions = [];
        foreach ($requirements as $index => $requirement) {
            $conditionSource = $source;
            $conditionSource['yeu_cau_thanh_tich'] = $requirement;
            $eligibility = $this->eligibilityFromPayload($payload, $conditionSource, $level, $errors);
            $participantLevel = $this->participantLevelFromPayload($source, $level, 'dieukien.capdoituongthamgia', $errors);

            $conditions[] = [
                'ten_dieukien' => $this->conditionName($eligibility['yeu_cau_thanh_tich'], $index),
                'capdoituongthamgia' => $this->enumValue($participantLevel, $this->participantLevelCodes(), 'dieukien.capdoituongthamgia', $errors),
                'yeu_cau_thanh_tich' => $eligibility['yeu_cau_thanh_tich'],
                'idcapgiaidau_thanh_tich_nguon' => $eligibility['idcapgiaidau_thanh_tich_nguon'],
                'hang_toi_thieu_duoc_phep' => $eligibility['hang_toi_thieu_duoc_phep'],
                'so_mua_giai_gan_nhat_duoc_tinh' => $eligibility['so_mua_giai_gan_nhat_duoc_tinh'],
                'chi_tinh_giai_chinh_thuc' => 0,
                'bat_buoc_cung_khuvuc' => $this->boolInt($source['bat_buoc_cung_khuvuc'] ?? $payload['bat_buoc_cung_khuvuc'] ?? true),
                'cho_phep_btc_duyet_ngoai_le' => $eligibility['cho_phep_btc_duyet_ngoai_le'],
                'mota' => $this->nullableString($source['mota'] ?? $payload['mota_dieukien'] ?? null, 1000, 'Mô tả điều kiện tham gia', 'dieukien.mota', $errors),
                'trangthai' => 'HOAT_DONG',
            ];
        }

        return $conditions;
    }

    private function achievementRequirementsFromPayload(mixed $requirements): array
    {
        if (is_string($requirements)) {
            $decoded = json_decode($requirements, true);
            if (is_array($decoded)) {
                $requirements = $decoded;
            } elseif (str_contains($requirements, ',')) {
                $requirements = explode(',', $requirements);
            } else {
                $requirements = [$requirements];
            }
        } elseif (!is_array($requirements)) {
            $requirements = [$requirements];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $value): string => strtoupper(trim((string) $value)),
            $requirements
        ), fn (string $value): bool => $value !== '')));
    }

    private function representativeEligibilityFromConditions(array $conditions): array
    {
        $first = $conditions[0] ?? [
            'yeu_cau_thanh_tich' => 'KHONG_YEU_CAU',
            'idcapgiaidau_thanh_tich_nguon' => null,
            'hang_toi_thieu_duoc_phep' => null,
            'so_mua_giai_gan_nhat_duoc_tinh' => 1,
            'cho_phep_btc_duyet_ngoai_le' => 0,
        ];

        if (count($conditions) <= 1) {
            return $first;
        }

        $rankMap = ['VO_DICH' => 1, 'A_QUAN' => 2, 'HANG_BA' => 3];
        $ranks = [];
        foreach ($conditions as $condition) {
            $requirement = (string) ($condition['yeu_cau_thanh_tich'] ?? '');
            if (!isset($rankMap[$requirement])) {
                return $first;
            }
            $ranks[] = $rankMap[$requirement];
        }

        sort($ranks);
        $expected = range(1, max($ranks));
        if ($ranks !== $expected) {
            return $first;
        }

        return array_merge($first, [
            'yeu_cau_thanh_tich' => 'TOP_N',
            'hang_toi_thieu_duoc_phep' => max($ranks),
        ]);
    }

    private function conditionsAllowException(array $conditions): bool
    {
        foreach ($conditions as $condition) {
            if ((int) ($condition['cho_phep_btc_duyet_ngoai_le'] ?? 0) === 1) {
                return true;
            }
        }

        return false;
    }

    private function participantLevelFromPayload(array $source, ?array $level, string $errorKey, array &$errors): string
    {
        $expected = (string) ($level['capdoituongthamgia'] ?? '');
        $selectedValue = $source['capdoituongthamgia'] ?? ($expected !== '' ? $expected : '');
        $selected = strtoupper(trim((string) $selectedValue));
        $participantLevel = $this->enumValue($selected, $this->participantLevelCodes(), $errorKey, $errors);

        if ($expected !== '' && $participantLevel !== $expected) {
            $errors[$errorKey] = 'Cấp đội tham gia phải khớp cấp đội được phép của cấp giải hiện tại.';
        }

        return $participantLevel;
    }

    private function conditionName(string $requirement, int $index): string
    {
        $label = match ($requirement) {
            'VO_DICH' => 'Vô địch',
            'A_QUAN' => 'Á quân',
            'HANG_BA' => 'Hạng ba',
            'TOP_N' => 'Top N',
            'THEO_XEP_HANG' => 'Theo xếp hạng',
            'BTC_CHON' => 'BTC chọn',
            'DAC_CACH' => 'Đặc cách',
            default => 'Không yêu cầu thành tích',
        };

        return 'Điều kiện tham gia - ' . $label . ' #' . ($index + 1) . ' - ' . uniqid('', false);
    }

    private function formatTypeFromFlags(array $source): string
    {
        $hasPointRound = $this->boolInt($source['co_vong_diem'] ?? true) === 1;
        $hasKnockoutRound = $this->boolInt($source['co_vong_loai'] ?? true) === 1;

        if ($hasPointRound && $hasKnockoutRound) {
            return 'KET_HOP';
        }

        return $hasKnockoutRound ? 'VONG_LOAI' : 'VONG_DIEM';
    }

    private function eligibilityFromPayload(array $payload, array $source, ?array $level, array &$errors): array
    {
        $achievementRequirement = $this->enumValue(
            $source['yeu_cau_thanh_tich'] ?? $payload['yeu_cau_thanh_tich'] ?? 'KHONG_YEU_CAU',
            ['KHONG_YEU_CAU', 'VO_DICH', 'A_QUAN', 'HANG_BA', 'TOP_N', 'THEO_XEP_HANG', 'BTC_CHON', 'DAC_CACH'],
            'dieukien.yeu_cau_thanh_tich',
            $errors
        );

        $achievementLevelId = $this->optionalNullablePositiveInt(
            $source['idcapgiaidau_thanh_tich_nguon'] ?? $payload['idcapgiaidau_thanh_tich_nguon'] ?? null,
            'dieukien.idcapgiaidau_thanh_tich_nguon',
            $errors
        );
        $minimumRank = $this->optionalNullablePositiveInt(
            $source['hang_toi_thieu_duoc_phep'] ?? $payload['hang_toi_thieu_duoc_phep'] ?? null,
            'dieukien.hang_toi_thieu_duoc_phep',
            $errors
        );
        $recentSeasons = $this->optionalPositiveInt(
            $source['so_mua_giai_gan_nhat_duoc_tinh'] ?? $payload['so_mua_giai_gan_nhat_duoc_tinh'] ?? 1,
            'dieukien.so_mua_giai_gan_nhat_duoc_tinh',
            $errors,
            1
        );
        $achievementBased = in_array($achievementRequirement, ['VO_DICH', 'A_QUAN', 'HANG_BA', 'TOP_N', 'THEO_XEP_HANG'], true);

        if ($this->isLowestTournamentLevel($level)) {
            $achievementRequirement = 'KHONG_YEU_CAU';
            $achievementLevelId = null;
            $minimumRank = null;
            $recentSeasons = null;
            $achievementBased = false;
        }

        if (!$achievementBased) {
            $achievementLevelId = null;
            $minimumRank = null;
            $recentSeasons = null;
        }

        if ($achievementBased && $achievementLevelId === null) {
            $errors['dieukien.idcapgiaidau_thanh_tich_nguon'] = 'Cần chọn cấp giải nguồn của thành tích.';
        }

        if (in_array($achievementRequirement, ['TOP_N', 'THEO_XEP_HANG'], true) && $minimumRank === null) {
            $errors['dieukien.hang_toi_thieu_duoc_phep'] = 'Cần nhập hạng tối thiểu được phép.';
        }

        if ($achievementBased && $achievementLevelId !== null) {
            $achievementLevel = $this->tournamentLevelById($achievementLevelId);
            $requiredSourceLevel = (string) ($level['capdoituongthamgia'] ?? '');

            if ($achievementLevel === null) {
                $errors['dieukien.idcapgiaidau_thanh_tich_nguon'] = 'Cấp giải nguồn của thành tích không tồn tại.';
            } elseif ($requiredSourceLevel !== '' && (string) $achievementLevel['macapgiaidau'] !== $requiredSourceLevel) {
                $errors['dieukien.idcapgiaidau_thanh_tich_nguon'] = 'Cấp giải nguồn phải trùng với cấp đội tham gia của giải hiện tại.';
            }
        }

        return [
            'yeu_cau_thanh_tich' => $achievementRequirement,
            'idcapgiaidau_thanh_tich_nguon' => $achievementLevelId,
            'hang_toi_thieu_duoc_phep' => $minimumRank,
            'so_mua_giai_gan_nhat_duoc_tinh' => $recentSeasons,
            'cho_phep_btc_duyet_ngoai_le' => $achievementBased
                ? $this->boolInt($source['cho_phep_btc_duyet_ngoai_le'] ?? $payload['cho_phep_btc_duyet_ngoai_le'] ?? false)
                : 0,
        ];
    }

    private function tournamentLevelById(int $levelId): ?array
    {
        foreach ($this->tournaments->tournamentLevels() as $level) {
            if ((int) ($level['idcapgiaidau'] ?? 0) === $levelId) {
                return $level;
            }
        }

        return null;
    }

    private function isLowestTournamentLevel(?array $level): bool
    {
        if ($level === null) {
            return false;
        }

        if (array_key_exists('la_cap_thap_nhat', $level)) {
            return (int) $level['la_cap_thap_nhat'] === 1;
        }

        $levelId = (int) ($level['idcapgiaidau'] ?? 0);
        if ($levelId <= 0) {
            return false;
        }

        foreach ($this->tournaments->tournamentLevels() as $candidate) {
            if ((int) ($candidate['idcapgiaidau_cha'] ?? 0) === $levelId) {
                return false;
            }
        }

        return true;
    }

    private function participantLevelCodes(): array
    {
        $codes = [];

        foreach ($this->tournaments->tournamentLevels() as $level) {
            foreach (['macapgiaidau', 'capkhuvucphamvi', 'capdoituongthamgia'] as $field) {
                $code = strtoupper(trim((string) ($level[$field] ?? '')));
                if ($code !== '') {
                    $codes[$code] = true;
                }
            }
        }

        foreach ($this->tournaments->regionLevelCodes() as $code) {
            $normalized = strtoupper(trim($code));
            if ($normalized !== '') {
                $codes[$normalized] = true;
            }
        }

        return array_keys($codes);
    }

    private function nonNegativeMoneyString(mixed $value, string $errorKey, array &$errors): string
    {
        $text = trim((string) ($value ?? '0'));

        if ($text === '') {
            return '0';
        }

        $normalized = str_replace(',', '.', $text);

        if (!is_numeric($normalized) || (float) $normalized < 0) {
            $errors[$errorKey] = 'Lệ phí tham gia phải là số không âm.';
            return '0';
        }

        if (strlen($text) > 50) {
            $errors[$errorKey] = 'Lệ phí tham gia không hợp lệ.';
            return '0';
        }

        return $text;
    }

    private function composeRegulationContent(?string $content, string $fee, ?string $allowedTeamType): ?string
    {
        $content = trim((string) ($content ?? ''));
        $meta = [
            'le_phi_tham_gia' => $fee,
            'loai_doi_duoc_tham_gia' => trim((string) ($allowedTeamType ?? '')),
        ];
        $encoded = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($encoded)) {
            $encoded = '{}';
        }

        $result = $content;
        $result .= ($result === '' ? '' : "\n\n") . '---VTMS_DIEU_LE_META---' . "\n" . $encoded;

        return strlen($result) > 3000 ? substr($result, 0, 3000) : $result;
    }

    private function expandRegulationMeta(?array $regulation): ?array
    {
        if ($regulation === null) {
            return null;
        }

        $content = (string) ($regulation['noidung'] ?? '');
        $regulation['noidung_chinh'] = $content;
        $regulation['le_phi_tham_gia'] = '0';
        $regulation['loai_doi_duoc_tham_gia'] = '';

        $marker = '---VTMS_DIEU_LE_META---';
        $markerPos = strpos($content, $marker);

        if ($markerPos === false) {
            return $regulation;
        }

        $mainContent = rtrim(substr($content, 0, $markerPos));
        $metaContent = trim(substr($content, $markerPos + strlen($marker)));
        $meta = json_decode($metaContent, true);

        if (is_array($meta)) {
            $regulation['le_phi_tham_gia'] = (string) ($meta['le_phi_tham_gia'] ?? '0');
            $regulation['loai_doi_duoc_tham_gia'] = (string) ($meta['loai_doi_duoc_tham_gia'] ?? '');
        }

        $regulation['noidung_chinh'] = $mainContent;

        return $regulation;
    }

    private function nullableDateTime(mixed $value, string $errorKey, array &$errors): ?string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $text) === 1) {
            return $text . ' 00:00:00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $text) === 1) {
            return str_replace('T', ' ', $text) . ':00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/', $text) !== 1) {
            $errors[$errorKey] = 'Thời gian phải theo định dạng YYYY-MM-DD HH:MM:SS.';
            return null;
        }

        return strlen($text) === 16 ? str_replace('T', ' ', $text) . ':00' : str_replace('T', ' ', $text);
    }

    private function enumValue(mixed $value, array $allowed, string $errorKey, array &$errors): string
    {
        $text = strtoupper(trim((string) ($value ?? '')));

        if (!in_array($text, $allowed, true)) {
            $errors[$errorKey] = 'Giá trị không hợp lệ.';
            return $allowed[0];
        }

        return $text;
    }

    private function boolInt(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $text = strtolower(trim((string) $value));

        return in_array($text, ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    private function optionalNullablePositiveInt(mixed $value, string $errorKey, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (!ctype_digit((string) $value) || (int) $value <= 0) {
            $errors[$errorKey] = 'Giá trị phải là số nguyên dương.';
            return null;
        }

        return (int) $value;
    }

    private function changeRegistrationWindow(int $tournamentId, int $accountId, string $targetStatus, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizer = $organizerResult;
        $current = $this->withRules($tournamentId);

        if ($current === null || (int) $current['idbantochuc'] !== (int) $organizer['idbantochuc']) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        if ((string) $current['trangthai'] !== 'DA_CONG_BO') {
            return $this->failure('Giai dau phai duoc cong bo truoc khi quan ly dang ky.', 409);
        }

        if (!$this->canUpdateTournamentBeforeStart($current)) {
            return $this->failure('Dang ky giai dau da bi khoa sau khi giai dau bat dau.', 409);
        }

        $oldStatus = (string) $current['trangthaidangky'];

        if ($targetStatus === 'DANG_MO' && $oldStatus === 'DANG_MO') {
            return $this->failure('Dang ky giai dau dang mo.', 409);
        }

        if ($targetStatus === 'DA_DONG' && $oldStatus === 'DA_DONG') {
            return $this->failure('Dang ky giai dau da dong.', 409);
        }

        if ($targetStatus === 'DA_DONG' && $oldStatus === 'CHUA_MO') {
            return $this->failure('Chua the dong dang ky khi giai dau chua mo dang ky.', 409);
        }

        $action = $targetStatus === 'DANG_MO' ? 'mo' : 'dong';
        $logNote = sprintf(
            'Ban to chuc #%d %s dang ky giai dau "%s". Trang thai: %s -> %s.',
            (int) $organizer['idbantochuc'],
            $action,
            (string) $current['tengiaidau'],
            $oldStatus,
            $targetStatus
        );
        $logNote = $this->limitLogNote($logNote);

        try {
            $this->tournaments->updateRegistrationWindow(
                $tournamentId,
                $oldStatus,
                $targetStatus,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => $targetStatus === 'DANG_MO' ? 'Mo dang ky giai dau thanh cong.' : 'Dong dang ky giai dau thanh cong.',
                'tournament' => $this->withRules($tournamentId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'REGISTRATION_WINDOW_NOT_UPDATED') {
                return $this->failure('Khong the cap nhat trang thai dang ky giai dau hien tai.', 409);
            }

            return $this->failure('Khong the cap nhat trang thai dang ky giai dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat trang thai dang ky giai dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function decideRegistration(
        int $tournamentId,
        int $registrationId,
        int $accountId,
        string $targetStatus,
        ?string $rejectionReason,
        ?Request $request = null,
        string $expectedStatus = 'CHO_DUYET'
    ): array {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizer = $organizerResult;
        $current = $this->withRules($tournamentId);

        if ($current === null || (int) $current['idbantochuc'] !== (int) $organizer['idbantochuc']) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        if ((string) $current['trangthai'] !== 'DA_CONG_BO') {
            return $this->failure('Giai dau phai duoc cong bo truoc khi duyet dang ky.', 409);
        }

        $registration = $this->tournaments->findRegistration($tournamentId, $registrationId);

        if ($registration === null) {
            return $this->failure('Khong tim thay dang ky giai dau.', 404);
        }

        if ((string) $registration['trangthai'] !== $expectedStatus) {
            return $expectedStatus === 'DA_DUYET'
                ? $this->failure('Chi duoc loai doi bong da duoc duyet tham gia.', 409)
                : $this->failure('Chi duoc xu ly dang ky dang cho duyet.', 409);
        }

        if ($targetStatus === 'DA_DUYET') {
            if ((string) $registration['doibong_trangthai'] !== 'HOAT_DONG') {
                return $this->failure('Chi duoc duyet doi bong dang hoat dong.', 409);
            }

            $registrationLimit = $this->tournaments->registrationLimitForTournament($tournamentId) ?? (int) $current['quymo'];

            if ($this->tournaments->approvedRegistrationCount($tournamentId) >= $registrationLimit) {
                return $this->failure('So doi da duyet da dat quy mo giai dau.', 409, [
                    'quymo' => 'Khong the duyet them doi bong vi da dat gioi han quy mo.',
                ]);
            }
        }

        $action = match ($targetStatus) {
            'DA_DUYET' => 'duyet',
            'DA_HUY' => 'loai',
            default => 'tu choi',
        };
        $logNote = sprintf(
            'Ban to chuc #%d %s dang ky cua doi "%s" vao giai dau "%s".',
            (int) $organizer['idbantochuc'],
            $action,
            (string) $registration['tendoibong'],
            (string) $current['tengiaidau']
        );

        if ($rejectionReason !== null) {
            $logNote .= ' Ly do: ' . $rejectionReason;
        }

        $logNote = $this->limitLogNote($logNote);

        try {
            $this->tournaments->decideRegistration(
                $tournamentId,
                $registrationId,
                $expectedStatus,
                $targetStatus,
                $rejectionReason,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => match ($targetStatus) {
                    'DA_DUYET' => 'Duyet dang ky thanh cong.',
                    'DA_HUY' => 'Loai doi thi dau thanh cong.',
                    default => 'Tu choi dang ky thanh cong.',
                },
                'registration' => $this->tournaments->findRegistration($tournamentId, $registrationId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'REGISTRATION_NOT_DECIDED') {
                return $this->failure('Chi duoc xu ly dang ky dang cho duyet.', 409);
            }

            return $this->failure('Khong the xu ly dang ky giai dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xu ly dang ky giai dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function registrationFilters(array $filters): array
    {
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $errors = [];

        if ($status !== '' && !in_array($status, self::REGISTRATION_STATUSES, true)) {
            $errors['status'] = 'Trang thai dang ky khong hop le.';
        }

        return [
            'filters' => [
                'status' => $status,
                'q' => $keyword,
            ],
            'errors' => $errors,
        ];
    }

    private function tournamentFilters(array $filters): array
    {
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $registrationStatus = strtoupper(trim((string) ($filters['registration_status'] ?? $filters['reg_status'] ?? $filters['trangthaidangky'] ?? '')));
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));
        $errors = [];

        if ($status !== '' && !in_array($status, self::TOURNAMENT_STATUSES, true)) {
            $errors['status'] = 'Trang thai giai dau khong hop le.';
        }

        if ($registrationStatus !== '' && !in_array($registrationStatus, self::TOURNAMENT_REGISTRATION_STATUSES, true)) {
            $errors['registration_status'] = 'Trang thai dang ky khong hop le.';
        }

        if ($from !== '' && !$this->isValidDate($from)) {
            $errors['from'] = 'Ngay bat dau loc khong hop le.';
        }

        if ($to !== '' && !$this->isValidDate($to)) {
            $errors['to'] = 'Ngay ket thuc loc khong hop le.';
        }

        if ($from !== '' && $to !== '' && $this->isValidDate($from) && $this->isValidDate($to) && $to < $from) {
            $errors['to'] = 'Ngay ket thuc loc phai lon hon hoac bang ngay bat dau loc.';
        }

        return [
            'filters' => [
                'status' => $status,
                'registration_status' => $registrationStatus,
                'q' => $keyword,
                'from' => $from,
                'to' => $to,
            ],
            'errors' => $errors,
        ];
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return checkdate($month, $day, $year);
    }

    private function limitLogNote(string $note): string
    {
        if (strlen($note) <= 1000) {
            return $note;
        }

        return substr($note, 0, 997) . '...';
    }

    private function validatePublishableTournament(array $tournament): array
    {
        $errors = [];
        $requiredTextFields = [
            'tengiaidau' => 'Tên giải đấu là bắt buộc.',
            'thoigianbatdau' => 'Thời gian bắt đầu là bắt buộc.',
            'thoigianketthuc' => 'Thời gian kết thúc là bắt buộc.',
        ];

        foreach ($requiredTextFields as $field => $message) {
            if (trim((string) ($tournament[$field] ?? '')) === '') {
                $errors[$field] = $message;
            }
        }

        if ((int) ($tournament['quymo'] ?? 0) <= 0) {
            $errors['quymo'] = 'Quy mô phải lớn hơn 0.';
        }

        if ((int) ($tournament['idcapgiaidau'] ?? 0) <= 0) {
            $errors['idcapgiaidau'] = 'Cần chọn cấp giải đấu.';
        }

        if ((int) ($tournament['idkhuvucphamvi'] ?? 0) <= 0) {
            $errors['idkhuvucphamvi'] = 'Cần chọn khu vực phạm vi.';
        }

        if ((int) ($tournament['idluat'] ?? 0) <= 0) {
            $errors['idluat'] = 'Cần chọn luật thi đấu.';
        }

        $startDate = trim((string) ($tournament['thoigianbatdau'] ?? ''));
        $endDate = trim((string) ($tournament['thoigianketthuc'] ?? ''));

        if ($startDate !== '' && $endDate !== '' && $endDate <= $startDate) {
            $errors['thoigianketthuc'] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        }

        $regulation = is_array($tournament['dieule'] ?? null) ? $tournament['dieule'] : null;

        if ($regulation === null || trim((string) ($regulation['tieude'] ?? '')) === '') {
            $errors['dieule'] = 'Cần thiết lập điều lệ giải đấu trước khi công bố.';
        } elseif ((int) ($regulation['so_doi_toi_da'] ?? 0) < (int) ($tournament['quymo'] ?? 0)) {
            $errors['dieule.so_doi_toi_da'] = 'Số đội tối đa trong điều lệ phải bao phủ quy mô giải.';
        }

        $format = is_array($tournament['thethuc'] ?? null) ? $tournament['thethuc'] : null;

        if ($format === null) {
            $errors['thethuc'] = 'Cần thiết lập thể thức thi đấu trước khi công bố giải đấu.';
        } elseif ((int) ($format['co_vong_diem'] ?? 0) !== 1 && (int) ($format['co_vong_loai'] ?? 0) !== 1) {
            $errors['thethuc'] = 'Cần chọn ít nhất một loại vòng thi đấu.';
        }

        if (!is_array($tournament['quytac'] ?? null)) {
            $errors['quytac'] = 'Cần thiết lập quy tắc chọn đội trước khi công bố giải đấu.';
        }

        if (!is_array($tournament['dieukien'] ?? null) || $tournament['dieukien'] === []) {
            $errors['dieukien'] = 'Cần thiết lập điều kiện tham gia trước khi công bố giải đấu.';
        }

        return $errors;
    }

    private function locationFromPayload(array $payload, int $organizerId, array &$errors, bool $required): array
    {
        $key = $this->firstExistingKey($payload, ['idvitrithidau', 'id_vi_tri_thi_dau', 'location_id', 'competition_location_id']);
        $raw = $key === null ? '' : trim((string) $payload[$key]);

        if ($raw === '') {
            if ($required) {
                $errors['idvitrithidau'] = 'Vui long chon dia diem thi dau tu danh muc co san.';
            }

            return [];
        }

        if (!ctype_digit($raw) || (int) $raw <= 0) {
            $errors['idvitrithidau'] = 'Dia diem thi dau khong hop le.';
            return [];
        }

        $location = $this->tournaments->competitionLocationById((int) $raw, $organizerId);

        if ($location === null) {
            $errors['idvitrithidau'] = 'Dia diem thi dau khong ton tai hoac khong o trang thai hoat dong.';
            return [];
        }

        return [
            'idvitrithidau' => (int) $location['idvitrithidau'],
            'diadiem' => $this->locationDisplayName($location),
        ];
    }

    private function locationDisplayName(array $location): string
    {
        $name = trim((string) ($location['tenvitrithidau'] ?? ''));
        $address = trim((string) ($location['diachi'] ?? ''));

        if ($name !== '' && $address !== '' && $name !== $address) {
            return $name . ' - ' . $address;
        }

        return $name !== '' ? $name : $address;
    }

    private function requiredString(array $payload, array $keys, int $maxLength, string $label, array &$errors): ?string
    {
        $key = $this->firstExistingKey($payload, $keys);
        $value = trim((string) ($key === null ? '' : $payload[$key]));
        $errorKey = $keys[0];

        if ($value === '') {
            $errors[$errorKey] = $label . ' la bat buoc.';
            return null;
        }

        if (strlen($value) > $maxLength) {
            $errors[$errorKey] = $label . ' khong duoc vuot qua ' . $maxLength . ' ky tu.';
            return null;
        }

        return $value;
    }

    private function nullableString(mixed $value, int $maxLength, string $label, string $errorKey, array &$errors): ?string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        if (strlen($text) > $maxLength) {
            $errors[$errorKey] = $label . ' khong duoc vuot qua ' . $maxLength . ' ky tu.';
            return null;
        }

        return $text;
    }

    private function dateValue(mixed $value, string $errorKey, string $label, array &$errors): ?string
    {
        $date = str_replace('T', ' ', trim((string) ($value ?? '')));

        if ($date === '') {
            $errors[$errorKey] = $label . ' la bat buoc.';
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date .= ' 00:00:00';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $date)) {
            $date .= ':00';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date)) {
            $errors[$errorKey] = $label . ' phai theo dinh dang YYYY-MM-DD HH:MM[:SS].';
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date);
        if (!$parsed || $parsed->format('Y-m-d H:i:s') !== $date) {
            $errors[$errorKey] = $label . ' khong hop le.';
            return null;
        }

        return $parsed->format('Y-m-d H:i:s');
    }

    private function positiveInt(mixed $value, string $errorKey, string $label, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            $errors[$errorKey] = $label . ' la bat buoc.';
            return null;
        }

        if (!ctype_digit((string) $value)) {
            $errors[$errorKey] = $label . ' phai la so nguyen duong.';
            return null;
        }

        $number = (int) $value;

        if ($number <= 0) {
            $errors[$errorKey] = $label . ' phai lon hon 0.';
            return null;
        }

        return $number;
    }

    private function rules(array $payload, array &$errors): array
    {
        $source = $payload['dieule'] ?? $payload['dieu_le'] ?? $payload['rules'] ?? null;

        if (is_string($source)) {
            $content = trim($source);

            if ($content === '') {
                $errors['dieule'] = 'Noi dung dieu le la bat buoc.';
                return [];
            }

            if (strlen($content) > 3000) {
                $errors['dieule'] = 'Noi dung dieu le khong duoc vuot qua 3000 ky tu.';
                return [];
            }

            $title = trim((string) ($payload['tieude_dieule'] ?? ''));

            if ($title === '') {
                $title = 'Dieu le giai dau';
            }

            if (strlen($title) > 300) {
                $errors['tieude_dieule'] = 'Tieu de dieu le khong duoc vuot qua 300 ky tu.';
                return [];
            }

            return [[
                'tieude' => $title,
                'noidung' => $content,
                'filedinhkem' => $this->nullableString($payload['filedinhkem'] ?? null, 500, 'File dinh kem', 'filedinhkem', $errors),
            ]];
        }

        if (!is_array($source)) {
            $errors['dieule'] = 'Can cung cap it nhat mot dieu le giai dau.';
            return [];
        }

        $items = $this->isRuleObject($source) ? [$source] : $source;
        $rules = [];
        $titles = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                $errors['dieule.' . $index] = 'Dieu le khong hop le.';
                continue;
            }

            $title = trim((string) ($item['tieude'] ?? $item['title'] ?? ''));
            $content = trim((string) ($item['noidung'] ?? $item['content'] ?? ''));
            $attachment = $this->nullableString($item['filedinhkem'] ?? $item['attachment'] ?? null, 500, 'File dinh kem', 'dieule.' . $index . '.filedinhkem', $errors);

            if ($title === '') {
                $errors['dieule.' . $index . '.tieude'] = 'Tieu de dieu le la bat buoc.';
                continue;
            }

            if (strlen($title) > 300) {
                $errors['dieule.' . $index . '.tieude'] = 'Tieu de dieu le khong duoc vuot qua 300 ky tu.';
                continue;
            }

            if ($content === '') {
                $errors['dieule.' . $index . '.noidung'] = 'Noi dung dieu le la bat buoc.';
                continue;
            }

            if (strlen($content) > 3000) {
                $errors['dieule.' . $index . '.noidung'] = 'Noi dung dieu le khong duoc vuot qua 3000 ky tu.';
                continue;
            }

            $key = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);

            if (isset($titles[$key])) {
                $errors['dieule.' . $index . '.tieude'] = 'Tieu de dieu le bi trung.';
                continue;
            }

            $titles[$key] = true;
            $rules[] = [
                'tieude' => $title,
                'noidung' => $content,
                'filedinhkem' => $attachment,
            ];
        }

        if ($rules === [] && !isset($errors['dieule'])) {
            $errors['dieule'] = 'Can cung cap it nhat mot dieu le giai dau.';
        }

        return $rules;
    }

    private function isRuleObject(array $value): bool
    {
        return array_key_exists('tieude', $value)
            || array_key_exists('title', $value)
            || array_key_exists('noidung', $value)
            || array_key_exists('content', $value);
    }

    private function hasCompetitionFormatPayload(array $payload): bool
    {
        return $this->hasAnyKey($payload, [
            'competition_format',
            'thethuc',
            'so_doi',
            'team_count',
            'players_per_team',
            'so_vdv_moi_doi',
            'match_rule',
            'luat_thi_dau',
            'scoring_rule',
            'luat_tinh_diem',
            'advance_count',
            'so_doi_di_tiep',
            'region',
            'khu_vuc',
            'khuvuc',
            'level',
            'cap_do',
            'capdo',
            'rounds',
            'vong_dau',
        ]);
    }

    private function competitionFormatFromPayload(array $payload, ?int $scale, array &$errors, ?array $fallback = null): array
    {
        $source = $payload['competition_format'] ?? $payload['thethuc'] ?? [];

        if (is_string($source) && trim($source) !== '') {
            $decoded = json_decode($source, true);
            $source = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($source)) {
            $errors['thethuc'] = 'The thuc thi dau khong hop le.';
            $source = [];
        }

        $default = $fallback ?? $this->defaultCompetitionFormat($scale ?? VolleyballCompetitionRules::REQUIRED_TEAM_COUNT);
        $teamCount = $this->optionalPositiveInt(
            $source['team_count'] ?? $source['so_doi'] ?? $payload['team_count'] ?? $payload['so_doi'] ?? $scale,
            'thethuc.team_count',
            $errors,
            (int) ($default['team_count'] ?? VolleyballCompetitionRules::REQUIRED_TEAM_COUNT)
        );
        $playersPerTeam = $this->optionalPositiveInt(
            $source['players_per_team'] ?? $source['so_vdv_moi_doi'] ?? $payload['players_per_team'] ?? $payload['so_vdv_moi_doi'] ?? null,
            'thethuc.players_per_team',
            $errors,
            (int) ($default['players_per_team'] ?? VolleyballCompetitionRules::REQUIRED_PLAYERS_PER_TEAM)
        );
        $advanceCount = $this->optionalPositiveInt(
            $source['advance_count'] ?? $source['so_doi_di_tiep'] ?? $payload['advance_count'] ?? $payload['so_doi_di_tiep'] ?? null,
            'thethuc.advance_count',
            $errors,
            (int) ($default['advance_count'] ?? min(8, max(1, $teamCount - 2)))
        );

        if ($scale !== null && $teamCount !== $scale) {
            $errors['thethuc.team_count'] = 'So doi trong the thuc phai khop voi quy mo giai dau.';
        }

        if ($teamCount < 4) {
            $errors['thethuc.team_count'] = 'The thuc can toi thieu 4 doi.';
        }

        if ($playersPerTeam !== VolleyballCompetitionRules::REQUIRED_PLAYERS_PER_TEAM) {
            $errors['thethuc.players_per_team'] = 'Moi doi phai co 6 van dong vien theo the thuc chuan.';
        }

        if ($advanceCount <= 0 || $advanceCount >= $teamCount) {
            $errors['thethuc.advance_count'] = 'So doi vao vong sau phai lon hon 0 va nho hon tong so doi.';
        }

        $matchRule = strtoupper(trim((string) (
            $source['match_rule']
            ?? $source['luat_thi_dau']
            ?? $payload['match_rule']
            ?? $payload['luat_thi_dau']
            ?? ($default['match_rule'] ?? 'BO5')
        )));

        if ($matchRule !== 'BO5') {
            $errors['thethuc.match_rule'] = 'The thuc hien tai chi chap nhan luat thi dau BO5.';
        }

        $scoringRule = trim((string) (
            $source['scoring_rule']
            ?? $source['luat_tinh_diem']
            ?? $payload['scoring_rule']
            ?? $payload['luat_tinh_diem']
            ?? ($default['scoring_rule'] ?? $this->defaultScoringRule())
        ));

        if ($scoringRule === '') {
            $errors['thethuc.scoring_rule'] = 'Luat tinh diem la bat buoc.';
            $scoringRule = $this->defaultScoringRule();
        }

        if (strlen($scoringRule) > 1000) {
            $errors['thethuc.scoring_rule'] = 'Luat tinh diem khong duoc vuot qua 1000 ky tu.';
        }

        $region = $this->nullableString(
            $source['region'] ?? $source['khu_vuc'] ?? $source['khuvuc'] ?? $payload['region'] ?? $payload['khu_vuc'] ?? $payload['khuvuc'] ?? null,
            100,
            'Khu vuc',
            'thethuc.region',
            $errors
        ) ?? ($default['region'] ?? null);
        $level = $this->nullableString(
            $source['level'] ?? $source['cap_do'] ?? $source['capdo'] ?? $payload['level'] ?? $payload['cap_do'] ?? $payload['capdo'] ?? null,
            100,
            'Cap do giai dau',
            'thethuc.level',
            $errors
        ) ?? ($default['level'] ?? null);
        $rounds = $this->roundsFromValue($source['rounds'] ?? $source['vong_dau'] ?? $payload['rounds'] ?? $payload['vong_dau'] ?? ($default['rounds'] ?? null), $errors);

        return [
            'team_count' => $teamCount,
            'players_per_team' => $playersPerTeam,
            'match_rule' => $matchRule,
            'scoring_rule' => $scoringRule,
            'advance_count' => $advanceCount,
            'region' => $region,
            'level' => $level,
            'preliminary_round' => VolleyballCompetitionRules::PRELIMINARY_ROUND,
            'rounds' => $rounds,
            'knockout' => VolleyballCompetitionRules::meta()['knockout'],
        ];
    }

    private function mergeCompetitionFormatRule(array $rules, array $format, array &$errors): array
    {
        $rules = $this->nonSystemRules($rules);
        $content = json_encode($format, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($content) || $content === '') {
            $errors['thethuc'] = 'Khong the ma hoa the thuc thi dau.';
            return $rules;
        }

        if (strlen($content) > 3000) {
            $errors['thethuc'] = 'The thuc thi dau vuot qua do dai luu tru cho phep.';
            return $rules;
        }

        $rules[] = [
            'tieude' => self::FORMAT_RULE_TITLE,
            'noidung' => $content,
            'filedinhkem' => null,
        ];

        return $rules;
    }

    private function nonSystemRules(array $rules): array
    {
        $systemTitles = array_fill_keys(array_map([$this, 'ruleTitleKey'], self::SYSTEM_RULE_TITLES), true);
        $result = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $title = trim((string) ($rule['tieude'] ?? $rule['title'] ?? ''));

            if ($title === '' || isset($systemTitles[$this->ruleTitleKey($title)])) {
                continue;
            }

            $result[] = [
                'tieude' => $title,
                'noidung' => trim((string) ($rule['noidung'] ?? $rule['content'] ?? '')),
                'filedinhkem' => $rule['filedinhkem'] ?? $rule['attachment'] ?? null,
            ];
        }

        return $result;
    }

    private function competitionFormatFromRules(array $rules, int $scale): array
    {
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            if ($this->ruleTitleKey((string) ($rule['tieude'] ?? '')) !== $this->ruleTitleKey(self::FORMAT_RULE_TITLE)) {
                continue;
            }

            $decoded = json_decode((string) ($rule['noidung'] ?? ''), true);

            if (is_array($decoded)) {
                $errors = [];
                return $this->competitionFormatFromPayload(['competition_format' => $decoded], $scale, $errors, $this->defaultCompetitionFormat($scale));
            }
        }

        return $this->defaultCompetitionFormat($scale);
    }

    private function defaultCompetitionFormat(int $teamCount): array
    {
        $teamCount = max(4, $teamCount);

        return [
            'team_count' => $teamCount,
            'players_per_team' => VolleyballCompetitionRules::REQUIRED_PLAYERS_PER_TEAM,
            'match_rule' => 'BO5',
            'scoring_rule' => $this->defaultScoringRule(),
            'advance_count' => min(8, $teamCount - 2),
            'region' => null,
            'level' => null,
            'preliminary_round' => VolleyballCompetitionRules::PRELIMINARY_ROUND,
            'rounds' => $this->defaultRounds(),
            'knockout' => VolleyballCompetitionRules::meta()['knockout'],
        ];
    }

    private function defaultScoringRule(): string
    {
        return 'Bo5. Thắng 3-0 hoặc 3-1: đội thắng 3 điểm, đội thua 0 điểm. Thắng 3-2: đội thắng 2 điểm, đội thua 1 điểm.';
    }

    private function defaultRounds(): array
    {
        return [
            ['order' => 1, 'name' => VolleyballCompetitionRules::PRELIMINARY_ROUND, 'type' => 'ROUND_ROBIN', 'scope' => 'ALL_TEAMS'],
            ['order' => 2, 'name' => 'Tứ kết', 'type' => 'KNOCKOUT', 'scope' => 'TOP_8'],
            ['order' => 3, 'name' => 'Bán kết', 'type' => 'KNOCKOUT', 'scope' => 'WINNERS_OF_QUARTERFINALS'],
            ['order' => 4, 'name' => 'Chung kết', 'type' => 'KNOCKOUT', 'scope' => 'WINNERS_OF_SEMIFINALS'],
            ['order' => 5, 'name' => 'Tranh hạng 3', 'type' => 'KNOCKOUT', 'scope' => 'LOSERS_OF_SEMIFINALS'],
        ];
    }

    private function roundsFromValue(mixed $value, array &$errors): array
    {
        if ($value === null || $value === '') {
            return $this->defaultRounds();
        }

        if (is_string($value)) {
            $value = preg_split('/[\r\n,;]+/', $value) ?: [];
            $value = array_values(array_filter(array_map('trim', $value), static fn (string $item): bool => $item !== ''));
        }

        if (!is_array($value)) {
            $errors['thethuc.rounds'] = 'Danh sach vong dau khong hop le.';
            return $this->defaultRounds();
        }

        $rounds = [];

        foreach (array_values($value) as $index => $round) {
            if (is_string($round)) {
                $name = trim($round);
                $type = $index === 0 ? 'ROUND_ROBIN' : 'KNOCKOUT';
                $scope = $index === 0 ? 'ALL_TEAMS' : 'AUTO';
            } elseif (is_array($round)) {
                $name = trim((string) ($round['name'] ?? $round['tenvong'] ?? $round['label'] ?? ''));
                $type = strtoupper(trim((string) ($round['type'] ?? $round['loai'] ?? ($index === 0 ? 'ROUND_ROBIN' : 'KNOCKOUT'))));
                $scope = strtoupper(trim((string) ($round['scope'] ?? $round['phamvi'] ?? 'AUTO')));
            } else {
                $errors['thethuc.rounds.' . $index] = 'Vong dau khong hop le.';
                continue;
            }

            if ($name === '') {
                $errors['thethuc.rounds.' . $index] = 'Ten vong dau la bat buoc.';
                continue;
            }

            if (strlen($name) > 100) {
                $errors['thethuc.rounds.' . $index] = 'Ten vong dau khong duoc vuot qua 100 ky tu.';
                continue;
            }

            if (!in_array($type, ['ROUND_ROBIN', 'GROUP', 'KNOCKOUT'], true)) {
                $errors['thethuc.rounds.' . $index . '.type'] = 'Loai vong dau khong hop le.';
                $type = $index === 0 ? 'ROUND_ROBIN' : 'KNOCKOUT';
            }

            $rounds[] = [
                'order' => $index + 1,
                'name' => $name,
                'type' => $type,
                'scope' => $scope === '' ? 'AUTO' : $scope,
            ];
        }

        if (count($rounds) < 4) {
            $errors['thethuc.rounds'] = 'Can khai bao toi thieu vong so bo, tu ket, ban ket va chung ket.';
            return $rounds === [] ? $this->defaultRounds() : $rounds;
        }

        return $rounds;
    }

    private function optionalPositiveInt(mixed $value, string $errorKey, array &$errors, int $default): int
    {
        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        if (!ctype_digit((string) $value) || (int) $value <= 0) {
            $errors[$errorKey] = 'Gia tri phai la so nguyen duong.';
            return $default;
        }

        return (int) $value;
    }

    private function ruleTitleKey(string $title): string
    {
        $title = trim($title);

        return function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);
    }

    private function firstExistingKey(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $key;
            }
        }

        return null;
    }

    private function hasAnyKey(array $payload, array $keys): bool
    {
        return $this->firstExistingKey($payload, $keys) !== null;
    }

    private function activeOrganizer(int $accountId): array
    {
        $organizer = $this->tournaments->findOrganizerByAccountId($accountId);

        if ($organizer === null) {
            return $this->failure('Tai khoan khong co ho so ban to chuc.', 403);
        }

        if ((string) $organizer['trangthai'] !== 'HOAT_DONG') {
            return $this->failure('Ban to chuc khong o trang thai hoat dong.', 403);
        }

        return $organizer;
    }

    private function withRules(int $tournamentId): ?array
    {
        $this->tournaments->syncStartedPublishedTournaments(null, $tournamentId);

        $tournament = $this->tournaments->findById($tournamentId);

        if ($tournament === null) {
            return null;
        }

        $tournament['dieule'] = $this->expandRegulationMeta($this->tournaments->regulationForTournament($tournamentId));
        $tournament['thethuc'] = $this->tournaments->competitionFormatForTournament($tournamentId);
        $tournament['quytac'] = $this->tournaments->teamSelectionRuleForTournament($tournamentId);
        $tournament['dieukien'] = $this->tournaments->participationConditionsForTournament($tournamentId);
        $tournament['thanh_tich_duoc_phep'] = $this->allowedAchievementRequirements($tournament['dieukien']);

        return $tournament;
    }

    private function allowedAchievementRequirements(array $conditions): array
    {
        $requirements = [];

        foreach ($conditions as $condition) {
            $requirement = strtoupper(trim((string) ($condition['yeu_cau_thanh_tich'] ?? '')));

            if ($requirement === '') {
                continue;
            }

            $requirements[] = $requirement;
        }

        return array_values(array_unique($requirements));
    }

    private function failure(string $message, int $status, array $errors = []): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
        ];
    }
}

