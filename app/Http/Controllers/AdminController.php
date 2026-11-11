<?php

namespace App\Http\Controllers;

use App\Models\ActivityAudit;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\LogbookEntry;
use App\Models\PlacementMessage;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\VisitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        return view('workspaces.admin', [
            'users' => User::query()->orderBy('is_approved')->latest()->paginate(25, ['*'], 'users_page'),
            'internships' => Internship::query()->with('organisation')->latest()->paginate(20, ['*'], 'listings_page'),
            'applications' => InternshipApplication::query()->with(['student', 'internship.organisation'])->latest()->paginate(25, ['*'], 'applications_page'),
            'assignments' => SupervisorAssignment::query()->with(['student', 'supervisor'])->latest()->paginate(25, ['*'], 'assignments_page'),
            'logbooks' => LogbookEntry::query()->with(['student', 'reviewer'])->latest()->paginate(25, ['*'], 'logbooks_page'),
            'visits' => VisitRequest::query()->with(['student', 'supervisor'])->latest('requested_for')->paginate(25, ['*'], 'visits_page'),
            'messages' => PlacementMessage::query()->with(['student', 'supervisor', 'sender'])->latest()->paginate(25, ['*'], 'messages_page'),
            'audits' => ActivityAudit::with('actor')->latest()->take(20)->get(),
            'students' => User::where('role', User::ROLE_STUDENT)->orderBy('name')->get(),
            'supervisors' => User::where('role', User::ROLE_SUPERVISOR)->orderBy('name')->get(),
            'counts' => [
                'students' => User::where('role', User::ROLE_STUDENT)->count(),
                'supervisors' => User::where('role', User::ROLE_SUPERVISOR)->count(),
                'organisations' => User::where('role', User::ROLE_ORGANISATION)->count(),
                'pending_listings' => Internship::where('status', 'pending')->count(),
            ],
        ]);
    }

    public function assignSupervisor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'supervisor_id' => ['required', 'exists:users,id'],
        ]);
        $student = User::whereKey($data['student_id'])->where('role', User::ROLE_STUDENT)->firstOrFail();
        $supervisor = User::whereKey($data['supervisor_id'])->where('role', User::ROLE_SUPERVISOR)->firstOrFail();

        $assignment = SupervisorAssignment::updateOrCreate(
            ['student_id' => $student->id],
            ['supervisor_id' => $supervisor->id],
        );

        ActivityAudit::create([
            'actor_id' => $request->user()->id,
            'action' => 'supervisor_assigned',
            'subject_type' => SupervisorAssignment::class,
            'subject_id' => $assignment->id,
            'details' => ['student_id' => $student->id, 'supervisor_id' => $supervisor->id],
        ]);

        return back()->with('status', 'Supervisor assignment saved.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', 'in:'.implode(',', User::ROLES)]]);
        abort_if($user->is($request->user()), 422, 'You cannot change your own administrator role.');

        $previousRole = $user->role;
        $user->update(['role' => $data['role']]);
        ActivityAudit::create([
            'actor_id' => $request->user()->id,
            'action' => 'user_role_updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'details' => ['from' => $previousRole, 'to' => $data['role']],
        ]);

        return back()->with('status', 'Account role updated and recorded.');
    }

    public function approveAccount(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, [User::ROLE_SUPERVISOR, User::ROLE_ORGANISATION], true), 404);
        abort_if($user->is_approved, 422, 'This account has already been approved.');

        $user->update(['is_approved' => true]);
        ActivityAudit::create([
            'actor_id' => $request->user()->id,
            'action' => 'account_approved',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'details' => ['role' => $user->role],
        ]);

        return back()->with('status', 'Account approved.');
    }

    public function moderateInternship(Request $request, Internship $internship): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:open,closed,rejected']]);
        $previousStatus = $internship->status;
        $internship->update(['status' => $data['status']]);
        ActivityAudit::create([
            'actor_id' => $request->user()->id,
            'action' => 'internship_moderated',
            'subject_type' => Internship::class,
            'subject_id' => $internship->id,
            'details' => ['from' => $previousStatus, 'to' => $data['status']],
        ]);

        return back()->with('status', 'Internship listing moderation saved.');
    }
}
