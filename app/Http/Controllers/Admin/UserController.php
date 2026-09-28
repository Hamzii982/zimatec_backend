<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::latest()->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::orderBy('label')->get();

        return view('admin.users.form', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|string|in:admin,user',
            'role_id' => 'nullable|exists:roles,id',
            'company' => 'required|string|in:ZF,ZT',
        ]);

        $roleId = $request->filled('role_id') ? $request->role_id : null;

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'role_id' => $roleId,
            'company' => $request->company,
        ]);

        return redirect()->route('admin.users')->with('success', 'User created successfully!');
    }

    /**
     * Show edit user form.
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('label')->get();

        return view('admin.users.form', compact('user', 'roles'));
    }

    /**
     * Update user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|string|in:admin,user',
            'role_id' => 'nullable|exists:roles,id',
            'company' => 'required|string|in:ZF,ZT',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'role_id' => $request->filled('role_id') ? $request->role_id : null,
            'company' => $request->company,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Delete user.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('admin.users')
            ->with('success', 'User deleted successfully!');
    }

    /**
     * Toggle active/inactive.
     */
    public function toggleMachineUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->machine_user = ! $user->machine_user;
        $user->save();

        return response()->json(['success' => true, 'active' => $user->machine_user]);
    }

    /**
     * Display the authenticated user's profile.
     */
    public function profile()
    {
        $user = Auth::user();

        return view('admin.profile.index', compact('user'));
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'company' => 'nullable|string|in:ZF,ZT',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $data = $request->only('name', 'email', 'company');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()
            ->route('admin.profile')
            ->with('success', 'Profile updated successfully.');
    }
}
