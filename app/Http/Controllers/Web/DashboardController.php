<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\Signal;
use App\Models\Tier;
use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard', [
            'admin' => $request->user(),
            'clientsCount' => User::where('role', 'client')->count(),
            'activeClientsCount' => User::where('role', 'client')->where('is_active', true)->count(),
            'signalsCount' => Signal::count(),
            'tiersCount' => Tier::count(),
            'templatesCount' => MessageTemplate::count(),
        ]);
    }
}
