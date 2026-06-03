<?php

namespace App\Http\Controllers;

use App\Services\AdminBootstrapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function __construct(
        private readonly AdminBootstrapService $adminBootstrap,
    ) {}

    public function create(): View
    {
        return view('setup.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => AdminBootstrapService::passwordRules(),
        ]);

        $this->adminBootstrap->createAdmin($validated);

        return redirect()->route('login')->with('success', 'Administrator created. Sign in to continue.');
    }
}
