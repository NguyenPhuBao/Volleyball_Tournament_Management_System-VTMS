<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Athlete;

use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;

final class AthletePageController extends Controller
{
    public function notifications(Request $request): Response
    {
        return $this->page('vandongvien.notifications', 'VTMS - Thong bao VDV', 'athlete-notifications.js');
    }

    public function invitations(Request $request): Response
    {
        return $this->page('vandongvien.invitations', 'VTMS - Loi moi doi bong', 'athlete-invitations.js');
    }

    public function invitationDetail(Request $request): Response
    {
        return $this->page('vandongvien.invitation-detail', 'VTMS - Chi tiet loi moi', 'athlete-invitations.js');
    }

    public function teamDetail(Request $request): Response
    {
        return $this->page('vandongvien.team-detail', 'VTMS - Doi bong cua toi', 'athlete-team-detail.js');
    }

    public function lineupView(Request $request): Response
    {
        return $this->page('vandongvien.lineup-view', 'VTMS - Doi hinh doi bong', 'athlete-lineup-view.js');
    }

    public function personalSchedule(Request $request): Response
    {
        return $this->page('vandongvien.personal-schedule', 'VTMS - Lich thi dau ca nhan', 'athlete-personal-schedule.js');
    }

    public function profile(Request $request): Response
    {
        return $this->page('vandongvien.profile', 'VTMS - Ho so ca nhan', 'athlete-profile.js');
    }

    public function leave(Request $request): Response
    {
        return $this->page('vandongvien.leave', 'VTMS - Xin nghi phep thi dau', 'athlete-leave.js');
    }

    private function page(string $view, string $title, string $script): Response
    {
        return $this->view($view, [
            'pageTitle' => $title,
            'styles' => ['css/athlete-pages.css'],
            'scripts' => ['js/athlete-common.js', 'js/' . $script],
        ]);
    }
}

