<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Doibong;
use App\Backend\Models\Giaidau;
use RuntimeException;
use Throwable;

final class OrganizerTeamProfileService
{
    private const REGISTRATION_STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI', 'DA_HUY'];
    private const TEAM_STATUSES = ['HOAT_DONG', 'CHO_DUYET', 'TAM_KHOA', 'GIAI_THE'];

    public function __construct(
        private ?Doibong $teams = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->teams ??= new Doibong();
        $this->tournaments ??= new Giaidau();
    }

    public function list(int $tournamentId, int $accountId, array $filters = []): array
    {
        $context = $this->ownedTournament($tournamentId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $normalizedFilters = $this->filters($filters);

        if (!empty($normalizedFilters['errors'])) {
            return $this->failure('Bo loc ho so doi bong khong hop le.', 422, $normalizedFilters['errors']);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach ho so doi bong tham gia thanh cong.',
            'teams' => $this->teams->teamsForTournament($tournamentId, $normalizedFilters['filters']),
            'meta' => [
                'tournament' => $this->tournamentSummary($context['tournament']),
                'filters' => $normalizedFilters['filters'],
            ],
        ];
    }

    public function listAll(int $accountId, array $filters = []): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $normalizedFilters = $this->filters($filters);

        if (!empty($normalizedFilters['errors'])) {
            return $this->failure('Bo loc ho so doi bong khong hop le.', 422, $normalizedFilters['errors']);
        }

        $tournamentId = $normalizedFilters['filters']['tournament_id'];

        if ($tournamentId !== '') {
            $tournament = $this->tournaments->findById((int) $tournamentId);

            if ($tournament === null || (int) $tournament['idbantochuc'] !== (int) $organizer['idbantochuc']) {
                return $this->failure('Khong tim thay giai dau.', 404);
            }
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach ho so doi bong tham gia thanh cong.',
            'teams' => $this->teams->teamsForOrganizer((int) $organizer['idbantochuc'], $normalizedFilters['filters']),
            'meta' => [
                'filters' => $normalizedFilters['filters'],
            ],
        ];
    }

    public function show(int $tournamentId, int $teamId, int $accountId): array
    {
        $context = $this->ownedTournament($tournamentId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $profile = $this->teams->teamProfileForTournament($tournamentId, $teamId);

        if ($profile === null) {
            return $this->failure('Khong tim thay ho so doi bong trong giai dau.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay ho so doi bong tham gia thanh cong.',
            'profile' => $this->profilePayload($tournamentId, $teamId, $profile),
        ];
    }

    public function updateProfile(int $teamId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $rawTournamentId = $payload['idgiaidau'] ?? $payload['tournament_id'] ?? null;
        $tournamentId = $this->optionalPositiveInt($rawTournamentId);

        if (trim((string) ($rawTournamentId ?? '')) !== '' && $tournamentId === null) {
            return $this->failure('Giai dau khong hop le.', 422, [
                'idgiaidau' => 'Giai dau khong hop le.',
            ]);
        }

        $context = $this->teams->teamContextForOrganizer((int) $organizer['idbantochuc'], $teamId, $tournamentId);

        if ($context === null) {
            return $this->failure('Khong tim thay ho so doi bong trong cac giai dau cua ban to chuc.', 404);
        }

        [$changes, $errors, $changedFields] = $this->validateUpdatePayload($payload, $context);

        if ($errors !== []) {
            return $this->failure('Du lieu cap nhat ho so doi bong khong hop le.', 422, $errors);
        }

        if ($changes === []) {
            return $this->failure('Can gui it nhat mot truong thay doi.', 422, [
                'payload' => 'Khong co du lieu thay doi.',
            ]);
        }

        $newStatus = array_key_exists('trangthai', $changes) ? (string) $changes['trangthai'] : null;
        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d cap nhat ho so doi "%s". Truong thay doi: %s.',
            (int) $organizer['idbantochuc'],
            (string) $context['tendoibong'],
            implode(', ', $changedFields)
        ));

        try {
            $this->teams->updateTeamProfile(
                $teamId,
                $changes,
                (string) $context['trangthaidoibong'],
                $newStatus,
                $accountId,
                $request?->ip(),
                $logNote
            );

            $profileTournamentId = (int) $context['idgiaidau'];

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cap nhat ho so doi bong thanh cong.',
                'profile' => $this->profilePayload(
                    $profileTournamentId,
                    $teamId,
                    $this->teams->teamProfileForTournament($profileTournamentId, $teamId)
                ),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'TEAM_PROFILE_NOT_UPDATED') {
                return $this->failure('Khong the cap nhat ho so doi bong.', 409);
            }

            return $this->failure('Khong the cap nhat ho so doi bong.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat ho so doi bong.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function cancelParticipation(int $tournamentId, int $teamId, array $payload, int $accountId, ?Request $request = null): array
    {
        $context = $this->ownedTournament($tournamentId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $profile = $this->teams->teamProfileForTournament($tournamentId, $teamId);

        if ($profile === null) {
            return $this->failure('Khong tim thay ho so doi bong trong giai dau.', 404);
        }

        if ((string) $profile['trangthaidangky'] !== 'DA_DUYET') {
            return $this->failure('Chi duoc huy doi bong da duoc duyet tham gia.', 409);
        }

        if ($this->teams->hasActiveMatches($tournamentId, $teamId)) {
            return $this->failure('Khong the huy doi bong da co tran dau trong giai.', 409);
        }

        $reason = trim((string) ($payload['lydo'] ?? $payload['reason'] ?? $payload['note'] ?? ''));

        if ($reason === '') {
            return $this->failure('Ly do huy tham gia la bat buoc.', 422, [
                'lydo' => 'Can nhap ly do huy tham gia.',
            ]);
        }

        if (strlen($reason) > 1000) {
            return $this->failure('Ly do huy tham gia khong hop le.', 422, [
                'lydo' => 'Ly do huy tham gia khong duoc vuot qua 1000 ky tu.',
            ]);
        }

        $organizer = $context['organizer'];
        $tournament = $context['tournament'];
        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d huy doi "%s" tham gia giai dau "%s". Ly do: %s',
            (int) $organizer['idbantochuc'],
            (string) $profile['tendoibong'],
            (string) $tournament['tengiaidau'],
            $reason
        ));

        try {
            $this->teams->cancelTournamentParticipation(
                (int) $profile['iddangky'],
                $reason,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Huy doi bong tham gia giai dau thanh cong.',
                'profile' => $this->profilePayload(
                    $tournamentId,
                    $teamId,
                    $this->teams->teamProfileForTournament($tournamentId, $teamId)
                ),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'PARTICIPATION_NOT_CANCELLED') {
                return $this->failure('Chi duoc huy doi bong da duoc duyet tham gia.', 409);
            }

            return $this->failure('Khong the huy doi bong tham gia giai dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the huy doi bong tham gia giai dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function profilePayload(int $tournamentId, int $teamId, ?array $profile): array
    {
        if ($profile === null) {
            return [];
        }

        $lineups = $this->teams->lineupsForTournamentTeam($tournamentId, $teamId);
        $details = $this->teams->lineupDetailsForTournamentTeam($tournamentId, $teamId);
        $detailsByLineup = [];

        foreach ($details as $detail) {
            $detailsByLineup[(int) $detail['iddoihinh']][] = $detail;
        }

        foreach ($lineups as &$lineup) {
            $lineup['chitiet'] = $detailsByLineup[(int) $lineup['iddoihinh']] ?? [];
        }

        unset($lineup);

        $profile['members'] = $this->teams->membersForTeam($teamId, true);
        $profile['lineups'] = $lineups;
        $profile['stats'] = $this->teams->statsForTournamentTeam($tournamentId, $teamId);
        $profile['matches'] = $this->teams->matchesForTournamentTeam($tournamentId, $teamId);

        return $profile;
    }

    private function ownedTournament(int $tournamentId, int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $tournament = $this->tournaments->findById($tournamentId);

        if ($tournament === null || (int) $tournament['idbantochuc'] !== (int) $organizer['idbantochuc']) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        return [
            'organizer' => $organizer,
            'tournament' => $tournament,
        ];
    }

    private function filters(array $filters): array
    {
        $registrationStatus = strtoupper(trim((string) ($filters['registration_status'] ?? $filters['status'] ?? $filters['trangthaidangky'] ?? '')));
        $teamStatus = strtoupper(trim((string) ($filters['team_status'] ?? $filters['trangthaidoibong'] ?? '')));
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $tournamentId = trim((string) ($filters['tournament_id'] ?? $filters['idgiaidau'] ?? ''));
        $errors = [];

        if ($registrationStatus !== '' && !in_array($registrationStatus, self::REGISTRATION_STATUSES, true)) {
            $errors['registration_status'] = 'Trang thai dang ky khong hop le.';
        }

        if ($teamStatus !== '' && !in_array($teamStatus, self::TEAM_STATUSES, true)) {
            $errors['team_status'] = 'Trang thai doi bong khong hop le.';
        }

        if ($tournamentId !== '' && (!ctype_digit($tournamentId) || (int) $tournamentId <= 0)) {
            $errors['tournament_id'] = 'Giai dau khong hop le.';
        }

        return [
            'filters' => [
                'tournament_id' => $tournamentId,
                'registration_status' => $registrationStatus,
                'team_status' => $teamStatus,
                'q' => $keyword,
            ],
            'errors' => $errors,
        ];
    }

    private function validateUpdatePayload(array $payload, array $current): array
    {
        $changes = [];
        $errors = [];
        $changedFields = [];

        if (array_key_exists('diaphuong', $payload) || array_key_exists('local', $payload)) {
            $local = $this->nullableString($payload['diaphuong'] ?? $payload['local'] ?? null, 300, 'Dia phuong', 'diaphuong', $errors);

            if ($local !== ($current['diaphuong'] ?? null)) {
                $changes['diaphuong'] = $local;
                $changedFields[] = 'diaphuong';
            }
        }

        if (array_key_exists('logo', $payload)) {
            $logo = $this->nullableString($payload['logo'] ?? null, 500, 'Logo', 'logo', $errors);

            if ($logo !== ($current['logo'] ?? null)) {
                $changes['logo'] = $logo;
                $changedFields[] = 'logo';
            }
        }

        if (array_key_exists('mota', $payload) || array_key_exists('desc', $payload)) {
            $description = $this->nullableString($payload['mota'] ?? $payload['desc'] ?? null, 1000, 'Mo ta', 'mota', $errors);

            if ($description !== ($current['mota'] ?? null)) {
                $changes['mota'] = $description;
                $changedFields[] = 'mota';
            }
        }

        if (array_key_exists('trangthai', $payload) || array_key_exists('status', $payload)) {
            $status = strtoupper(trim((string) ($payload['trangthai'] ?? $payload['status'] ?? '')));

            if (!in_array($status, self::TEAM_STATUSES, true)) {
                $errors['trangthai'] = 'Trang thai doi bong khong hop le.';
            } elseif ($status !== (string) $current['trangthaidoibong']) {
                $changes['trangthai'] = $status;
                $changedFields[] = 'trangthai';
            }
        }

        return [$changes, $errors, $changedFields];
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

    private function optionalPositiveInt(mixed $value): ?int
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        if (!ctype_digit($text)) {
            return null;
        }

        $number = (int) $text;

        return $number > 0 ? $number : null;
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

    private function tournamentSummary(array $tournament): array
    {
        return [
            'idgiaidau' => (int) $tournament['idgiaidau'],
            'tengiaidau' => (string) $tournament['tengiaidau'],
            'trangthai' => (string) $tournament['trangthai'],
            'trangthaidangky' => (string) $tournament['trangthaidangky'],
            'quymo' => (int) $tournament['quymo'],
        ];
    }

    private function limitLogNote(string $note): string
    {
        if (strlen($note) <= 1000) {
            return $note;
        }

        return substr($note, 0, 997) . '...';
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

