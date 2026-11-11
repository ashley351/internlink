<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'role' => ['required', Rule::in(User::ROLES)],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those sign-in details did not match our records.'])
                ->onlyInput('email', 'role');
        }

        if (! $request->user()->is_approved) {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account is awaiting administrator approval.'])
                ->onlyInput('email', 'role');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register', [
            'universities' => config('internlink.universities'),
            'adminSignupEnabled' => filled(config('internlink.admin_invitation_code')),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in([
                User::ROLE_STUDENT,
                User::ROLE_SUPERVISOR,
                User::ROLE_ORGANISATION,
                User::ROLE_ADMIN,
            ])],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'course' => ['required_if:role,student', 'nullable', 'string', 'max:120'],
            'university' => [
                'required_if:role,student,supervisor',
                'nullable',
                'string',
                Rule::in(config('internlink.universities')),
            ],
            'admin_invitation_code' => [
                'required_if:role,admin',
                'nullable',
                'string',
                Rule::in([config('internlink.admin_invitation_code')]),
            ],
        ]);

        $role = $validated['role'];
        $requiresApproval = in_array($role, [User::ROLE_SUPERVISOR, User::ROLE_ORGANISATION], true);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $role,
            'is_approved' => ! $requiresApproval,
            'course' => $validated['course'] ?? null,
            'university' => $validated['university'] ?? null,
        ]);

        if ($requiresApproval) {
            return redirect()->route('login')->with('status', 'Your account request was received. You can sign in once an administrator approves it.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
