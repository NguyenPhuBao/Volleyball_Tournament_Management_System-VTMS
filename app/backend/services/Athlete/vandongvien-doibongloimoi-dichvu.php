<?php

declare(strict_types=1);

namespace App\Backend\Services\Athlete;

use App\Backend\Core\Http\Request;
use RuntimeException;
use Throwable;

final class AthleteTeamInvitationService extends AthleteServiceSupport
{
    private const STATUSES = ['CHO_PHAN_HOI', 'DONG_Y', 'TU_CHOI', 'HET_HAN'];

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, true);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        [$normalized, $errors] = $this->commonFilters($filters, self::STATUSES);

        if ($errors !== []) {
            return $this->failure('Bo loc loi moi doi bong khong hop le.', 422, $errors);
        }

        try {
            $items = $this->athletes->teamInvitationsForAthlete((int) $athlete['idvandongvien'], $normalized);
            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem danh sach loi moi doi bong',
                'Loimoidoibong',
                null,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem %d loi moi doi bong.', (int) $athlete['idvandongvien'], count($items)))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach loi moi doi bong thanh cong.',
                'invitations' => $items,
                'meta' => [
                    'athlete' => $athlete,
                    'filters' => $normalized,
                    'statuses' => self::STATUSES,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay danh sach loi moi doi bong.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function show(int $invitationId, int $accountId, ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, true);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        try {
            $item = $this->athletes->teamInvitationForAthlete((int) $athlete['idvandongvien'], $invitationId);

            if ($item === null) {
                return $this->failure('Khong tim thay loi moi doi bong.', 404);
            }

            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem chi tiet loi moi doi bong',
                'Loimoidoibong',
                $invitationId,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem loi moi doi bong #%d.', (int) $athlete['idvandongvien'], $invitationId))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet loi moi doi bong thanh cong.',
                'invitation' => $item,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet loi moi doi bong.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function accept(int $invitationId, int $accountId, ?Request $request = null): array
    {
        return $this->respond($invitationId, 'DONG_Y', $accountId, $request);
    }

    public function reject(int $invitationId, int $accountId, ?Request $request = null): array
    {
        return $this->respond($invitationId, 'TU_CHOI', $accountId, $request);
    }

    public function confirmMembership(int $memberId, int $accountId, ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, true);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        $logNote = $this->limitLogNote(sprintf(
            'VDV #%d xac nhan thanh vien doi bong #%d.',
            (int) $athlete['idvandongvien'],
            $memberId
        ));

        try {
            $member = $this->athletes->confirmTeamMembership(
                (int) $athlete['idvandongvien'],
                $memberId,
                $accountId,
                $request?->ip(),
                $logNote
            );

            if ($member === null) {
                return $this->failure('Khong tim thay thanh vien doi bong can xac nhan.', 404);
            }

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Xac nhan tham gia doi bong thanh cong.',
                'member' => $member,
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'MEMBERSHIP_NOT_PENDING') {
                return $this->failure('Chi duoc xac nhan thanh vien dang cho xac nhan.', 409);
            }

            return $this->failure('Khong the xac nhan tham gia doi bong.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xac nhan tham gia doi bong.', 500, [
                'database' => 'Loi cap nhat co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    private function respond(int $invitationId, string $status, int $accountId, ?Request $request): array
    {
        $athlete = $this->activeAthlete($accountId, true);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        $logNote = $this->limitLogNote(sprintf(
            'VDV #%d %s loi moi doi bong #%d.',
            (int) $athlete['idvandongvien'],
            $status === 'DONG_Y' ? 'dong y' : 'tu choi',
            $invitationId
        ));

        try {
            $item = $this->athletes->respondTeamInvitation(
                (int) $athlete['idvandongvien'],
                $invitationId,
                $status,
                $accountId,
                $request?->ip(),
                $logNote
            );

            if ($item === null) {
                return $this->failure('Khong tim thay loi moi doi bong.', 404);
            }

            return [
                'ok' => true,
                'status' => 200,
                'message' => $status === 'DONG_Y' ? 'Xac nhan tham gia doi bong thanh cong.' : 'Tu choi tham gia doi bong thanh cong.',
                'invitation' => $item,
            ];
        } catch (RuntimeException $exception) {
            return match ($exception->getMessage()) {
                'INVITATION_NOT_PENDING' => $this->failure('Chi duoc phan hoi loi moi dang cho phan hoi.', 409),
                'INVITATION_EXPIRED' => $this->failure('Loi moi doi bong da het han.', 409),
                'ATHLETE_ALREADY_IN_TEAM' => $this->failure('Van dong vien dang tham gia doi bong khac.', 409),
                default => $this->failure('Khong the phan hoi loi moi doi bong.', 500),
            };
        } catch (Throwable) {
            return $this->failure('Khong the phan hoi loi moi doi bong.', 500, [
                'database' => 'Loi cap nhat co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }
}

