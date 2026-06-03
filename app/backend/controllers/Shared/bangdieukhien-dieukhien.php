<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Shared;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Shared\DashboardSummaryService;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $dashboard = $user !== null ? (new DashboardSummaryService())->forUser($user, $request->ip()) : [];

        return $this->view('dashboard.index', [
            'user' => $user,
            'dashboardData' => $dashboard,
            'moduleTitle' => $dashboard['top_title'] ?? 'Tổng quan hệ thống',
            'moduleDescription' => $dashboard['top_subtitle'] ?? 'Màn hình điều hướng ban đầu theo vai trò người dùng.',
        ]);
    }

    public function admin(Request $request): Response
    {
        return $this->module('Quản trị hệ thống', 'Quản lý tài khoản, vai trò, cấu hình và nhật ký.', $request);
    }

    public function organizer(Request $request): Response
    {
        return $this->module('Ban tổ chức', 'Quản lý giải đấu, điều lệ, đội bóng, lịch thi đấu và kết quả.', $request);
    }

    public function referee(Request $request): Response
    {
        return $this->module('Trọng tài', 'Xem phân công, ghi nhận sự kiện trận đấu và báo cáo sự cố.', $request);
    }

    public function coach(Request $request): Response
    {
        return $this->module('Huấn luyện viên', 'Quản lý đội bóng, thành viên, đăng ký giải và đội hình.', $request);
    }

    public function athlete(Request $request): Response
    {
        return $this->module('Vận động viên', 'Theo dõi hồ sơ, lịch thi đấu, lời mời và đơn nghỉ phép.', $request);
    }

    private function module(string $title, string $description, ?Request $request = null): Response
    {
        $user = Auth::user();
        $dashboard = $user !== null ? (new DashboardSummaryService())->forUser($user, $request?->ip()) : [];

        return $this->view('dashboard.index', [
            'user' => $user,
            'dashboardData' => $dashboard,
            'moduleTitle' => $dashboard['top_title'] ?? $title,
            'moduleDescription' => $dashboard['top_subtitle'] ?? $description,
        ]);
    }
}

