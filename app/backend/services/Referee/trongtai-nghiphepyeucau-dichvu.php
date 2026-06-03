<?php

declare(strict_types=1);

namespace App\Backend\Services\Referee;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Trongtai;
use RuntimeException;
use Throwable;

final class RefereeLeaveRequestService
{
    private const STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI', 'DA_HUY'];

    public function __construct(private ?Trongtai $referees = null)
    {
        $this->referees ??= new Trongtai();
    }

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId, false);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        [$normalized, $errors] = $this->filters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc don nghi phep khong hop le.', 422, $errors);
        }

        try {
            $leaves = $this->referees->leaveRequestsForReferee((int) $referee['idtrongtai'], $normalized);
            $stats = $this->referees->leaveRequestStatsForReferee((int) $referee['idtrongtai'], $normalized);
            $this->referees->recordRefereeLeaveRequestListView(
                (int) $referee['idtrongtai'],
                $accountId,
                $request?->ip(),
                $this->listLogNote((int) $referee['idtrongtai'], $normalized, count($leaves))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach don nghi phep thanh cong.',
                'leave_requests' => $leaves,
                'meta' => [
                    'referee' => $referee,
                    'filters' => $normalized,
                    'statuses' => self::STATUSES,
                    'stats' => $stats,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay danh sach don nghi phep.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function show(int $leaveId, int $accountId, ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId, false);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        try {
            $leave = $this->referees->findLeaveRequestForReferee((int) $referee['idtrongtai'], $leaveId);

            if ($leave === null) {
                return $this->failure('Khong tim thay don nghi phep.', 404);
            }

            $this->referees->recordRefereeLeaveRequestDetailView(
                $leaveId,
                $accountId,
                $request?->ip(),
                $this->detailLogNote((int) $referee['idtrongtai'], $leave)
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet don nghi phep thanh cong.',
                'leave_request' => $leave,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet don nghi phep.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function create(array $payload, int $accountId, ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId, true);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        [$leave, $errors] = $this->leaveFromPayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu xin nghi phep khong hop le.', 422, $errors);
        }

        if ($this->referees->hasOverlappingLeaveRequest((int) $referee['idtrongtai'], $leave['tungay'], $leave['denngay'])) {
            return $this->failure('Khoang thoi gian nghi bi trung voi don nghi dang cho duyet hoac da duyet.', 409, [
                'date_range' => 'Vui long chon khoang thoi gian khac.',
            ]);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Trong tai #%d xin nghi phep tu %s den %s. Ly do: %s',
            (int) $referee['idtrongtai'],
            $leave['tungay'],
            $leave['denngay'],
            $leave['lydo']
        ));

        try {
            $leaveId = $this->referees->createSelfLeaveRequest(
                (int) $referee['idtrongtai'],
                $leave['tungay'],
                $leave['denngay'],
                $leave['lydo'],
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Xin nghi phep thanh cong.',
                'leave_request' => $this->referees->findLeaveRequestForReferee((int) $referee['idtrongtai'], $leaveId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the gui don xin nghi phep.', 500, [
                'database' => 'Loi ghi co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function cancel(int $leaveId, array $payload, int $accountId, ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId, false);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        $reason = trim((string) ($payload['lydo'] ?? $payload['reason'] ?? $payload['note'] ?? 'Trong tai huy don nghi phep'));

        if ($reason === '') {
            $reason = 'Trong tai huy don nghi phep';
        }

        if (strlen($reason) > 1000) {
            return $this->failure('Du lieu huy don nghi phep khong hop le.', 422, [
                'lydo' => 'Ly do huy khong duoc vuot qua 1000 ky tu.',
            ]);
        }

        $leave = $this->referees->findLeaveRequestForReferee((int) $referee['idtrongtai'], $leaveId);

        if ($leave === null) {
            return $this->failure('Khong tim thay don nghi phep.', 404);
        }

        if ((string) $leave['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chi duoc huy don nghi phep dang cho duyet.', 409);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Trong tai #%d huy don nghi phep #%d tu %s den %s. Ly do: %s',
            (int) $referee['idtrongtai'],
            $leaveId,
            (string) $leave['tungay'],
            (string) $leave['denngay'],
            $reason
        ));

        try {
            $updated = $this->referees->cancelSelfLeaveRequest(
                (int) $referee['idtrongtai'],
                $leaveId,
                $reason,
                $accountId,
                $request?->ip(),
                $logNote
            );

            if ($updated === null) {
                return $this->failure('Khong tim thay don nghi phep.', 404);
            }

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Huy nghi phep thanh cong.',
                'leave_request' => $updated,
            ];
        } catch (RuntimeException) {
            return $this->failure('Chi duoc huy don nghi phep dang cho duyet.', 409);
        } catch (Throwable) {
            return $this->failure('Khong the huy don nghi phep.', 500, [
                'database' => 'Loi ghi co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    private function activeReferee(int $accountId, bool $requireAvailable): array
    {
        $referee = $this->referees->findByAccountId($accountId);

        if ($referee === null) {
            return $this->failure('Tai khoan khong co ho so trong tai.', 403);
        }

        if ((string) $referee['trangthai_taikhoan'] !== 'HOAT_DONG') {
            return $this->failure('Tai khoan trong tai khong o trang thai hoat dong.', 403);
        }

        if ($requireAvailable && (string) $referee['trangthai'] !== 'HOAT_DONG') {
            return $this->failure('Chi trong tai dang hoat dong moi duoc xin nghi phep.', 409);
        }

        return $referee;
    }

    private function filters(array $filters): array
    {
        $errors = [];
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? $filters['tungay'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? $filters['denngay'] ?? ''));

        if ($status !== '' && !in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Trang thai don nghi phep khong hop le.';
        }

        if ($from !== '' && !$this->isDate($from)) {
            $errors['from'] = 'Tu ngay khong hop le.';
        }

        if ($to !== '' && !$this->isDate($to)) {
            $errors['to'] = 'Den ngay khong hop le.';
        }

        if ($from !== '' && $to !== '' && $this->isDate($from) && $this->isDate($to) && $to < $from) {
            $errors['to'] = 'Den ngay phai lon hon hoac bang tu ngay.';
        }

        return [[
            'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
            'status' => $status,
            'from' => $from,
            'to' => $to,
        ], $errors];
    }

    private function leaveFromPayload(array $payload): array
    {
        $errors = [];
        $today = date('Y-m-d');
        $from = $this->requiredDate($payload['tungay'] ?? $payload['from_date'] ?? $payload['from'] ?? null, 'tungay', 'Tu ngay', $errors);
        $to = $this->requiredDate($payload['denngay'] ?? $payload['to_date'] ?? $payload['to'] ?? null, 'denngay', 'Den ngay', $errors);
        $reason = trim((string) ($payload['lydo'] ?? $payload['reason'] ?? $payload['note'] ?? ''));

        if ($reason === '') {
            $errors['lydo'] = 'Ly do xin nghi phep la bat buoc.';
        } elseif (strlen($reason) > 1000) {
            $errors['lydo'] = 'Ly do xin nghi phep khong duoc vuot qua 1000 ky tu.';
        }

        if ($from !== null && $from < $today) {
            $errors['tungay'] = 'Tu ngay khong duoc nho hon ngay hien tai.';
        }

        if ($from !== null && $to !== null && $to < $from) {
            $errors['denngay'] = 'Den ngay phai lon hon hoac bang tu ngay.';
        }

        return [[
            'tungay' => $from ?? $today,
            'denngay' => $to ?? ($from ?? $today),
            'lydo' => $reason,
        ], $errors];
    }

    private function requiredDate(mixed $value, string $errorKey, string $label, array &$errors): ?string
    {
        $date = trim((string) ($value ?? ''));

        if ($date === '') {
            $errors[$errorKey] = $label . ' la bat buoc.';
            return null;
        }

        if (!$this->isDate($date)) {
            $errors[$errorKey] = $label . ' phai theo dinh dang YYYY-MM-DD.';
            return null;
        }

        return $date;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function listLogNote(int $refereeId, array $filters, int $total): string
    {
        $parts = [
            'Trong tai #' . $refereeId . ' xem danh sach don nghi phep',
            'So dong: ' . $total,
        ];

        foreach (['q', 'status', 'from', 'to'] as $key) {
            if (($filters[$key] ?? '') !== '') {
                $parts[] = $key . '=' . (string) $filters[$key];
            }
        }

        return $this->limitLogNote(implode('. ', $parts));
    }

    private function detailLogNote(int $refereeId, array $leave): string
    {
        return $this->limitLogNote(sprintf(
            'Trong tai #%d xem don nghi phep #%d, trang thai %s, tu %s den %s.',
            $refereeId,
            (int) $leave['iddonnghi'],
            (string) $leave['trangthai'],
            (string) $leave['tungay'],
            (string) $leave['denngay']
        ));
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

