<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserAnalyticsService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserAnalyticsService $analytics,
    ) {}

    /**
     * List all non-admin users (paginated).
     */
    public function index(Request $request)
    {
        $query = User::where('role', UserRole::User->value)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show full analytics profile for a single user.
     * All data is computed inside UserAnalyticsService.
     */
    public function show(int $id)
    {
        $profile = $this->analytics->getUserProfile($id);

        return view('admin.users.show', compact('profile'));
    }
}
