<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Agency\Models\Agency;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\UserType;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'page', 'limit', 'sort', 'order', 'search',
            'user_type', 'status', 'agency_id',
        ]);

        $query = User::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('email', 'ILIKE', "%{$s}%")
                    ->orWhere('first_name', 'ILIKE', "%{$s}%")
                    ->orWhere('last_name', 'ILIKE', "%{$s}%");
            }))
            ->when($filters['user_type'] ?? null, fn ($q, $t) => $q->where('user_type', $t))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['agency_id'] ?? null, fn ($q, $id) => $q->where('agency_id', $id))
            ->with(['agency', 'roles']);

        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        $users = $query->paginate(
            (int) ($filters['limit'] ?? 20),
            ['*'],
            'page',
            (int) ($filters['page'] ?? 1),
        );

        return Inertia::render('Users/Index', [
            'users' => $this->paginateToArray($users),
            'filters' => $filters,
            'userTypes' => fn () => array_map(
                fn (UserType $t) => ['value' => $t->value, 'label' => ucwords(str_replace('_', ' ', $t->value))],
                UserType::cases(),
            ),
            'statuses' => fn () => array_map(
                fn (AccountStatus $s) => ['value' => $s->value, 'label' => ucfirst($s->value)],
                AccountStatus::cases(),
            ),
            'roles' => fn () => Role::orderBy('name')->get(['id', 'name']),
            'agencies' => fn () => Agency::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load(['agency', 'roles']);

        return Inertia::render('Users/Show', [
            'user' => $this->userToArray($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'user_type' => 'required|string|in:' . implode(',', array_column(UserType::cases(), 'value')),
            'agency_id' => 'nullable|integer|exists:agencies,id',
            'preferred_language' => 'sometimes|string|in:en,fr,de,it',
            'status' => 'sometimes|string|in:' . implode(',', array_column(AccountStatus::cases(), 'value')),
            'role' => 'sometimes|string|exists:roles,name',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'user_type' => $validated['user_type'],
            'agency_id' => $validated['agency_id'] ?? null,
            'preferred_language' => $validated['preferred_language'] ?? 'en',
            'status' => $validated['status'] ?? AccountStatus::ACTIVE->value,
        ]);

        $roleName = $validated['role'] ?? $validated['user_type'];
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $user->assignRole($role);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('users.user_created'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:50',
            'user_type' => 'sometimes|string|in:' . implode(',', array_column(UserType::cases(), 'value')),
            'agency_id' => 'nullable|integer|exists:agencies,id',
            'preferred_language' => 'sometimes|string|in:en,fr,de,it',
            'status' => 'sometimes|string|in:' . implode(',', array_column(AccountStatus::cases(), 'value')),
            'role' => 'sometimes|string|exists:roles,name',
        ]);

        $roleToSync = null;
        if (isset($validated['role'])) {
            $roleToSync = $validated['role'];
            unset($validated['role']);
        }

        $user->update($validated);

        if ($roleToSync) {
            $user->syncRoles([$roleToSync]);
        }

        return redirect()->back()->with('success', __('users.user_updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('users.user_deleted'));
    }

    public function activate(User $user): RedirectResponse
    {
        $user->update(['status' => AccountStatus::ACTIVE->value]);

        return redirect()->back()->with('success', __('users.user_activated'));
    }

    public function suspend(User $user): RedirectResponse
    {
        $user->update(['status' => AccountStatus::SUSPENDED->value]);

        return redirect()->back()->with('success', __('users.user_suspended'));
    }

    // ── Helpers ──

    private function paginateToArray($paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn ($u) => $this->userToArray($u)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }

    private function userToArray(User $u): array
    {
        return [
            'id' => $u->id,
            'email' => $u->email,
            'first_name' => $u->first_name,
            'last_name' => $u->last_name,
            'full_name' => $u->first_name . ' ' . $u->last_name,
            'phone' => $u->phone,
            'avatar_url' => $u->avatar_url,
            'user_type' => $u->user_type instanceof UserType ? $u->user_type->value : $u->user_type,
            'agency_id' => $u->agency_id,
            'preferred_language' => $u->preferred_language,
            'status' => $u->status instanceof AccountStatus ? $u->status->value : $u->status,
            'email_verified_at' => $u->email_verified_at?->toISOString(),
            'last_login_at' => $u->last_login_at?->toISOString(),
            'created_at' => $u->created_at?->toISOString(),
            'updated_at' => $u->updated_at?->toISOString(),
            'roles' => $u->relationLoaded('roles') ? $u->roles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
            ])->toArray() : [],
            'agency' => $u->relationLoaded('agency') && $u->agency ? [
                'id' => $u->agency->id,
                'name' => $u->agency->name,
            ] : null,
        ];
    }
}
