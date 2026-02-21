<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoginThemePageController extends Controller
{
    public function index(): View
    {
        return view('admin.login-theme', [
            'activeTheme' => AppSetting::getValue('login_theme', 'modern'),
            'themes' => [
                'modern' => 'Modern Blue',
                'premium' => 'Premium Elegant',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login_theme' => ['required', Rule::in(['modern', 'premium'])],
        ]);

        AppSetting::setValue('login_theme', $data['login_theme']);

        return redirect()->route('login-theme.page')->with('status', 'Tema login berhasil diupdate.');
    }
}

