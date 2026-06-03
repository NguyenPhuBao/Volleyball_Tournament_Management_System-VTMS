<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Coach;

use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;

final class CoachPageController extends Controller
{
    public function register(Request $request): Response
    {
        return $this->page('huanluyenvien.register', 'VTMS - Dang ky tai khoan HLV', 'coach-register.js');
    }

    public function players(Request $request): Response
    {
        return $this->page('huanluyenvien.players', 'VTMS - Tai khoan Van dong vien', 'coach-players.js');
    }

    public function tournaments(Request $request): Response
    {
        return $this->page('huanluyenvien.tournaments', 'VTMS - Dang ky giai dau', 'coach-tournaments.js');
    }

    public function teamProfile(Request $request): Response
    {
        return $this->page('huanluyenvien.team-profile', 'VTMS - Doi bong cua toi', 'coach-team-profile.js');
    }

    public function members(Request $request): Response
    {
        return $this->page('huanluyenvien.members', 'VTMS - Thanh vien doi bong', 'coach-members.js');
    }

    public function lineup(Request $request): Response
    {
        return $this->page('huanluyenvien.lineup', 'VTMS - Doi hinh thi dau', 'coach-lineup.js');
    }

    public function lineupEditor(Request $request): Response
    {
        return $this->page('huanluyenvien.lineup-editor', 'VTMS - Tao cap nhat doi hinh', 'coach-lineup-editor.js');
    }

    public function teamSchedule(Request $request): Response
    {
        return $this->page('huanluyenvien.team-schedule', 'VTMS - Lich thi dau doi bong', 'coach-team-schedule.js');
    }

    public function results(Request $request): Response
    {
        return $this->page('huanluyenvien.results', 'VTMS - Ket qua thi dau', 'coach-results.js');
    }

    public function athleteRequests(Request $request): Response
    {
        return $this->page('huanluyenvien.requests', 'VTMS - Yeu cau xac nhan VDV', 'coach-requests.js');
    }

    private function page(string $view, string $title, string $script): Response
    {
        return $this->view($view, [
            'pageTitle' => $title,
            'styles' => ['css/coach-pages.css'],
            'scripts' => ['js/coach-common.js', 'js/' . $script],
        ]);
    }
}

