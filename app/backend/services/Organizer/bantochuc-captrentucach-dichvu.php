<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Models\Giaidau;
use App\Backend\Models\Doibong;
use App\Backend\Models\Tucachthamgia;
use Throwable;

final class OrganizerHigherEligibilityService
{
    public function __construct(
        private ?Giaidau $tournaments = null,
        private ?Tucachthamgia $eligibility = null,
        private ?Doibong $teams = null
    ) {
        $this->tournaments ??= new Giaidau();
        $this->eligibility ??= new Tucachthamgia();
        $this->teams ??= new Doibong();
    }

    public function overview(int $accountId, array $filters = []): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $normalized = $this->filters($filters);
        $organizerId = (int) $organizer['idbantochuc'];

        $this->tournaments->syncStartedPublishedTournaments($organizerId);
        $this->eligibility->syncChampionAchievementsForOrganizer($organizerId);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach de cu tu cach tham gia thanh cong.',
            'data' => [
                'candidates' => $this->canNominate($organizer)
                    ? $this->eligibility->candidatesForOrganizer($organizerId, $normalized)
                    : [],
                'incoming' => $this->eligibility->incomingForOrganizer($organizerId, $normalized),
                'source_tournaments' => $this->eligibility->sourceTournamentsForOrganizer($organizerId),
            ],
            'meta' => [
                'filters' => $normalized,
                'organizer' => [
                    'idbantochuc' => (int) $organizer['idbantochuc'],
                    'donvi' => (string) $organizer['donvi'],
                    'capkhuvucquanly' => (string) $organizer['capkhuvucquanly'],
                ],
            ],
        ];
    }

    public function markEligible(array $payload, int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        if (!$this->canNominate($organizer)) {
            return $this->failure('Cap quoc gia chi duoc duyet de cu gui len, khong duoc tao de cu len cap cao hon.', 403);
        }

        [$achievementId, $targetTournamentId, $note, $errors] = $this->candidatePayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu danh dau tu cach khong hop le.', 422, $errors);
        }

        $candidate = $this->eligibility->candidate($achievementId, $targetTournamentId, (int) $organizer['idbantochuc']);

        if ($candidate === null) {
            return $this->failure('Khong tim thay doi bong phu hop de xet tu cach.', 404);
        }

        $reviewErrors = $this->reviewErrors($payload, (int) $candidate['iddoibong']);

        if ($reviewErrors !== []) {
            return $this->failure('Can xem xet day du HLV va VDV truoc khi danh dau du dieu kien.', 422, $reviewErrors);
        }

        try {
            $proposalId = $this->eligibility->markEligible($candidate, $accountId, $note);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Da danh dau doi bong du dieu kien de cu len cap cao hon.',
                'proposal_id' => $proposalId,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the danh dau tu cach tham gia.', 500);
        }
    }

    public function reviewProfile(array $payload, int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        if (!$this->canNominate($organizer)) {
            return $this->failure('Cap quoc gia chi duoc duyet de cu gui len, khong duoc xem xet tao de cu moi.', 403);
        }

        [$achievementId, $targetTournamentId, , $errors] = $this->candidatePayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu xem xet doi bong khong hop le.', 422, $errors);
        }

        $candidate = $this->eligibility->candidate($achievementId, $targetTournamentId, (int) $organizer['idbantochuc']);

        if ($candidate === null) {
            return $this->failure('Khong tim thay doi bong phu hop de xet tu cach.', 404);
        }

        $profile = $this->teams->teamProfileForHigherEligibility((int) $candidate['iddoibong']);

        if ($profile === null) {
            return $this->failure('Khong tim thay ho so doi bong.', 404);
        }

        $profile['idgiaidau'] = (int) $candidate['idgiaidau_nguon'];
        $profile['tengiaidau'] = (string) $candidate['tengiaidau_nguon'];
        $profile['trangthaidangky'] = 'THANH_TICH';
        $profile['members'] = $this->teams->membersForTeam((int) $candidate['iddoibong']);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay ho so doi bong de xem xet thanh cong.',
            'data' => [
                'candidate' => $candidate,
                'profile' => $profile,
            ],
        ];
    }

    public function nominate(int $proposalId, array $payload, int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        if (!$this->canNominate($organizer)) {
            return $this->failure('Cap quoc gia chi duoc duyet de cu gui len, khong duoc gui de cu len cap cao hon.', 403);
        }

        if ($proposalId <= 0) {
            return $this->failure('De cu khong hop le.', 422);
        }

        try {
            $updated = $this->eligibility->nominate(
                $proposalId,
                (int) $organizer['idbantochuc'],
                $accountId,
                $this->note($payload)
            );

            if (!$updated) {
                return $this->failure('Chi duoc de cu doi da duoc danh dau du dieu kien len dung 1 cap cao hon.', 409);
            }

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Da gui de cu len ban to chuc cap cao hon.',
            ];
        } catch (Throwable) {
            return $this->failure('Khong the gui de cu.', 500);
        }
    }

    public function approve(int $proposalId, array $payload, int $accountId): array
    {
        return $this->decide($proposalId, $payload, $accountId, true);
    }

    public function reject(int $proposalId, array $payload, int $accountId): array
    {
        return $this->decide($proposalId, $payload, $accountId, false);
    }

    public function authorize(int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Ban to chuc duoc phep xet tu cach cap tren.',
        ];
    }

    private function decide(int $proposalId, array $payload, int $accountId, bool $approved): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        if ($proposalId <= 0) {
            return $this->failure('De cu khong hop le.', 422);
        }

        $note = $this->note($payload);

        if (!$approved && $note === null) {
            return $this->failure('Can nhap ly do tu choi de cu.', 422, [
                'lydo' => 'Ly do tu choi la bat buoc.',
            ]);
        }

        try {
            $updated = $this->eligibility->decide(
                $proposalId,
                (int) $organizer['idbantochuc'],
                $accountId,
                $approved,
                $note
            );

            if (!$updated) {
                return $this->failure('Chi duoc xu ly de cu dang cho xac nhan, thuoc quyen BTC hien tai va len dung 1 cap cao hon.', 409);
            }

            return [
                'ok' => true,
                'status' => 200,
                'message' => $approved
                    ? 'Da xac nhan de cu, doi bong co tu cach tham gia giai cap cao hon.'
                    : 'Da tu choi de cu tu cach tham gia.',
            ];
        } catch (Throwable) {
            return $this->failure('Khong the xu ly de cu tu cach tham gia.', 500);
        }
    }

    private function activeOrganizer(int $accountId): array
    {
        $organizer = $this->tournaments->findOrganizerByAccountId($accountId);

        if ($organizer === null) {
            return $this->failure('Tai khoan khong co ho so ban to chuc.', 403);
        }

        if ((string) ($organizer['role'] ?? '') !== 'BAN_TO_CHUC') {
            return $this->failure('Chi tai khoan ban to chuc moi duoc xu ly suat dai dien.', 403);
        }

        if ((string) $organizer['trangthai'] !== 'HOAT_DONG') {
            return $this->failure('Ban to chuc khong o trang thai hoat dong.', 403);
        }

        if (
            (string) ($organizer['trangthai_donvi'] ?? '') !== 'HOAT_DONG'
            || (string) ($organizer['trangthai_loaidonvi'] ?? '') !== 'HOAT_DONG'
            || (int) ($organizer['duoc_to_chuc_giai'] ?? 0) !== 1
        ) {
            return $this->failure('Don vi cua ban to chuc khong co tham quyen xet doi thang de cu len cap tren.', 403);
        }

        return $organizer;
    }

    private function canNominate(array $organizer): bool
    {
        $levelId = (int) ($organizer['idcapgiaidau_quanly'] ?? 0);

        return $levelId > 0
            && array_key_exists('idcapgiaidau_cha_quanly', $organizer)
            && $organizer['idcapgiaidau_cha_quanly'] !== null;
    }

    private function filters(array $filters): array
    {
        $sourceTournamentId = trim((string) ($filters['source_tournament_id'] ?? ''));
        $achievement = strtoupper(trim((string) ($filters['achievement'] ?? '')));

        $allowedAchievements = ['VO_DICH', 'A_QUAN', 'HANG_BA', 'TOP_4', 'TOP_8', 'THAM_DU', 'KHAC'];

        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'source_tournament_id' => ctype_digit($sourceTournamentId) && (int) $sourceTournamentId > 0
                ? (int) $sourceTournamentId
                : '',
            'achievement' => in_array($achievement, $allowedAchievements, true) ? $achievement : '',
        ];
    }

    private function reviewErrors(array $payload, int $teamId): array
    {
        $errors = [];
        $coachReviewed = filter_var(
            $payload['reviewed_coach'] ?? $payload['hlv_da_xet'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$coachReviewed) {
            $errors['reviewed_coach'] = 'Can xac nhan HLV du dieu kien.';
        }

        $reviewedMemberIds = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => ctype_digit((string) $value) ? (int) $value : 0,
            is_array($payload['reviewed_member_ids'] ?? null) ? $payload['reviewed_member_ids'] : []
        ))));

        $requiredMemberIds = array_values(array_map(
            static fn (array $member): int => (int) $member['idthanhvien'],
            array_filter(
                $this->teams->membersForTeam($teamId),
                static fn (array $member): bool => (string) ($member['trangthaithanhvien'] ?? '') === 'DANG_THAM_GIA'
            )
        ));

        if ($requiredMemberIds === []) {
            $errors['reviewed_member_ids'] = 'Doi bong chua co VDV dang tham gia de xet.';
            return $errors;
        }

        $missingMemberIds = array_diff($requiredMemberIds, $reviewedMemberIds);

        if ($missingMemberIds !== []) {
            $errors['reviewed_member_ids'] = 'Can xac nhan tat ca VDV dang tham gia cua doi.';
        }

        return $errors;
    }

    private function candidatePayload(array $payload): array
    {
        $achievementRaw = $payload['idthanhtich'] ?? $payload['achievement_id'] ?? null;
        $targetRaw = $payload['idgiaidau_dich'] ?? $payload['target_tournament_id'] ?? null;
        $errors = [];

        if ($achievementRaw === null || !ctype_digit((string) $achievementRaw) || (int) $achievementRaw <= 0) {
            $errors['idthanhtich'] = 'Thanh tich khong hop le.';
        }

        if ($targetRaw === null || !ctype_digit((string) $targetRaw) || (int) $targetRaw <= 0) {
            $errors['idgiaidau_dich'] = 'Giai dau cap cao hon khong hop le.';
        }

        return [
            (int) $achievementRaw,
            (int) $targetRaw,
            $this->note($payload),
            $errors,
        ];
    }

    private function note(array $payload): ?string
    {
        $note = trim((string) ($payload['lydo'] ?? $payload['note'] ?? $payload['ghichu'] ?? ''));

        if ($note === '') {
            return null;
        }

        return strlen($note) > 1000 ? substr($note, 0, 1000) : $note;
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
