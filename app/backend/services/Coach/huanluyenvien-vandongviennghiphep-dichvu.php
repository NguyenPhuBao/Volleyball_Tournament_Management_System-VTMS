<?php

declare(strict_types=1);

namespace App\Backend\Services\Coach;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Doibong;
use RuntimeException;
use Throwable;

final class CoachAthleteLeaveService
{
    private const STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI', 'DA_HUY'];

    public function __construct(private ?Doibong $teams = null)
    {
        $this->teams ??= new Doibong();
    }

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        [$normalized, $errors] = $this->filters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc don xin nghi phep VDV khong hop le.', 422, $errors);
        }

        try {
            $leaves = $this->teams->athleteLeaveRequestsForCoach((int) $coach['idhuanluyenvien'], $normalized);
            $stats = $this->teams->athleteLeaveRequestStatsForCoach((int) $coach['idhuanluyenvien'], $normalized);
            $this->teams->recordCoachSystemLog(
                $accountId,
                'HLV xem danh sach don nghi phep VDV',
                'Donnghivandongvien',
                null,
                $request?->ip(),
                $this->limitLogNote(sprintf('HLV #%d xem %d don nghi phep VDV.', (int) $coach['idhuanluyenvien'], count($leaves)))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach don xin nghi phep VDV thanh cong.',
                'leave_requests' => $leaves,
                'meta' => [
                    'coach' => $coach,
                    'filters' => $normalized,
                    'statuses' => self::STATUSES,
                    'stats' => $stats,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay danh sach don xin nghi phep VDV.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function show(int $leaveId, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        try {
            $leave = $this->teams->athleteLeaveRequestForCoach((int) $coach['idhuanluyenvien'], $leaveId);

            if ($leave === null) {
                return $this->failure('Khong tim thay don xin nghi phep VDV trong doi dang quan ly.', 404);
            }

            $this->teams->recordCoachSystemLog(
                $accountId,
                'HLV xem chi tiet don nghi phep VDV',
                'Donnghivandongvien',
                $leaveId,
                $request?->ip(),
                $this->limitLogNote(sprintf('HLV #%d xem don nghi phep VDV #%d.', (int) $coach['idhuanluyenvien'], $leaveId))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet don xin nghi phep VDV thanh cong.',
                'leave_request' => $leave,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet don xin nghi phep VDV.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function approve(int $leaveId, array $payload, int $accountId, ?Request $request = null): array
    {
        return $this->changeStatus($leaveId, $payload, $accountId, $request, 'DA_DUYET', 'Da duyet don xin nghi phep VDV.');
    }

    public function reject(int $leaveId, array $payload, int $accountId, ?Request $request = null): array
    {
        $note = trim((string) ($payload['note'] ?? $payload['lydo'] ?? $payload['reason'] ?? ''));

        if ($note === '') {
            return $this->failure('Vui long nhap ghi chu khi tu choi don xin nghi phep VDV.', 422, [
                'note' => 'Ghi chu tu choi la bat buoc.',
            ]);
        }

        return $this->changeStatus($leaveId, ['note' => $note], $accountId, $request, 'TU_CHOI', 'Da tu choi don xin nghi phep VDV.');
    }

    private function changeStatus(
        int $leaveId,
        array $payload,
        int $accountId,
        ?Request $request,
        string $newStatus,
        string $successMessage
    ): array {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $note = trim((string) ($payload['note'] ?? $payload['lydo'] ?? $payload['reason'] ?? $successMessage));

        if ($note === '') {
            $note = $successMessage;
        }

        if (strlen($note) > 1000) {
            return $this->failure('Ghi chu xu ly don xin nghi phep VDV khong hop le.', 422, [
                'note' => 'Ghi chu khong duoc vuot qua 1000 ky tu.',
            ]);
        }

        $logNote = $this->limitLogNote(sprintf(
            'HLV #%d xu ly don nghi phep VDV #%d: %s. Ghi chu: %s',
            (int) $coach['idhuanluyenvien'],
            $leaveId,
            $newStatus,
            $note
        ));

        try {
            $updated = $this->teams->updateAthleteLeaveRequestForCoach(
                (int) $coach['idhuanluyenvien'],
                $leaveId,
                $newStatus,
                $accountId,
                $request?->ip(),
                $logNote
            );

            if ($updated === null) {
                return $this->failure('Khong tim thay don xin nghi phep VDV trong doi dang quan ly.', 404);
            }

            return [
                'ok' => true,
                'status' => 200,
                'message' => $successMessage,
                'leave_request' => $updated,
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'LEAVE_NOT_PENDING') {
                return $this->failure('Chi duoc xu ly don xin nghi phep dang cho duyet.', 409);
            }

            return $this->failure('Khong the xu ly don xin nghi phep VDV.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xu ly don xin nghi phep VDV.', 500, [
                'database' => 'Loi cap nhat co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    private function activeCoach(int $accountId): array
    {
        $coach = $this->teams->coachByAccountId($accountId);

        if ($coach === null) {
            return $this->failure('Tai khoan khong co ho so huan luyen vien.', 403);
        }

        if ((string) $coach['trangthai'] !== 'DA_XAC_NHAN') {
            return $this->failure('Huan luyen vien chua duoc xac nhan tu cach.', 403);
        }

        return $coach;
    }

    private function filters(array $filters): array
    {
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? $filters['tungay'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? $filters['denngay'] ?? ''));
        $teamId = trim((string) ($filters['team_id'] ?? $filters['iddoibong'] ?? ''));
        $athleteId = trim((string) ($filters['athlete_id'] ?? $filters['idvandongvien'] ?? ''));
        $matchId = trim((string) ($filters['match_id'] ?? $filters['idtrandau'] ?? ''));
        $errors = [];

        if ($status !== '' && !in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Trang thai don nghi phep khong hop le.';
        }

        if ($from !== '' && !$this->isDate($from)) {
            $errors['from'] = 'Tu ngay khong hop le.';
        }

        if ($to !== '' && !$this->isDate($to)) {
            $errors['to'] = 'Den ngay khong hop le.';
        }

        if ($from !== '' && $to !== '' && empty($errors['from']) && empty($errors['to']) && $from > $to) {
            $errors['date_range'] = 'Tu ngay phai nho hon hoac bang den ngay.';
        }

        foreach (['team_id' => $teamId, 'athlete_id' => $athleteId, 'match_id' => $matchId] as $key => $value) {
            if ($value !== '' && (!ctype_digit($value) || (int) $value <= 0)) {
                $errors[$key] = 'Gia tri dinh danh khong hop le.';
            }
        }

        return [[
            'q' => $keyword,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'team_id' => $teamId !== '' ? (int) $teamId : '',
            'athlete_id' => $athleteId !== '' ? (int) $athleteId : '',
            'match_id' => $matchId !== '' ? (int) $matchId : '',
        ], $errors];
    }

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
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
        $result = [
            'ok' => false,
            'status' => $status,
            'message' => $message,
        ];

        if ($errors !== []) {
            $result['errors'] = $errors;
        }

        return $result;
    }
}
