<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('alias_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => $request->boolean('is_active'),
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'employee_name' => 'nullable|string|max:255',
            'alias_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'contact' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:255',
            'role' => 'required|in:admin,manager,agent,ticketing,changes,mis,hr,accounts',
            'agent_language' => 'nullable|in:english,spanish,both',
            'joining_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $user = User::create($validated);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $validated['role']]);
            $user->syncRoles([$validated['role']]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => $request->boolean('is_active'),
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'employee_name' => 'nullable|string|max:255',
            'alias_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'contact' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:255',
            'role' => 'required|in:admin,manager,agent,ticketing,changes,mis,hr,accounts',
            'agent_language' => 'nullable|in:english,spanish,both',
            'joining_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;

        $user->update($validated);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $validated['role']]);
            $user->syncRoles([$validated['role']]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "User {$user->alias_name} has been {$status}.");
    }

    /**
     * Delete a user permanently from CRM.
     */
    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) {
            return redirect()->back()->with('error', 'You cannot delete your own admin account.');
        }

        $alias = $user->alias_name ?: $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$alias}' has been permanently deleted from CRM.");
    }
}
