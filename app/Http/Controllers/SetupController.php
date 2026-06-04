<?php

namespace App\Http\Controllers;

use App\Services\AdminBootstrapService;
use App\Http\Requests\SetupStoreRequest;
use Illuminate\Http\RedirectResponse;
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

    public function store(SetupStoreRequest $request): RedirectResponse
    {
        $this->adminBootstrap->createAdmin($request->validated());

        return redirect()->route('login')->with('success', 'Administrator created. Sign in to continue.');
    }
}
