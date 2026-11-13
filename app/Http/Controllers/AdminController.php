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
            'pendingOrganisations' => User::query()
                ->where('role', User::ROLE_ORGANISATION)
                ->where('approval_status', 'pending')
                ->latest()
                ->paginate(10, ['*'], 'pending_organisations_page'),
            'pendingSupervisors' => User::query()
                ->where('role', User::ROLE_SUPERVISOR)
                ->where('approval_status', 'pending')
                ->latest()
                ->paginate(10, ['*'], 'pending_supervisors_page'),
            'registrationHistory' => User::query()
                ->whereIn('role', [User::ROLE_ORGANISATION, User::ROLE_SUPERVISOR])
                ->whereIn('approval_status', ['approved', 'rejected'])
                ->with('approvalDecider')
                ->latest('approval_decided_at')
                ->paginate(20, ['*'], 'registration_history_page'),
            'internships' => Internship::query()->with('organisation')->latest()->paginate(20, ['*'], 'listings_page'),
            'applications' => InternshipApplication::query()->with(['student', 'internship.organisation'])->latest()->paginate(25, ['*'], 'applications_page'),
            'assignments' => SupervisorAssignment::query()->with(['student', 'supervisor'])->latest()->paginate(25, ['*'], 'assignments_page'),
            'logbooks' => LogbookEntry::query()->with(['student', 'reviewer'])->latest()->paginate(25, ['*'], 'logbooks_page'),
            'visits' => VisitRequest::query()->with(['student', 'supervisor'])->latest('requested_for')->paginate(25, ['*'], 'visits_page'),
            'messages' => PlacementMessage::query()->with(['student', 'supervisor', 'sender'])->latest()->paginate(25, ['*'], 'messages_page'),
            'audits' => ActivityAudit::with('actor')->latest()->take(20)->get(),
            'students' => User::where('role', User::ROLE_STUDENT)->orderBy('name')->get(),
            'supervisors' => User::query()
                ->where('role', User::ROLE_SUPERVISOR)
                ->where('approval_status', 'approved')
                ->where('is_approved', true)
                ->orderBy('name')
                ->get(),
            'counts' => [
                'students' => User::where('role', User::ROLE_STUDENT)->count(),
                'supervisors' => User::where('role', User::ROLE_SUPERVISOR)->count(),
                'organisations' => User::where('role', User::ROLE_ORGANISATION)->count(),
                'pending_organisations' => User::query()->where('role', User::ROLE_ORGANISATION)->where('approval_status', 'pending')->count(),
                'pending_supervisors' => User::query()->where('role', User::ROLE_SUPERVISOR)->where('approval_status', 'pending')->count(),
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
        $assignableRoles = array_values(array_diff(User::ROLES, [User::ROLE_ADMIN]));
        $data = $request->validate(['role' => ['required', 'in:'.implode(',', $assignableRoles)]]);
        abort_if($user->is($request->user()) || $user->role === User::ROLE_ADMIN, 422, 'Administrator roles are managed through the protected setup process.');

        $previousRole = $user->role;
        if ($data['role'] !== $previousRole) {
            $isAwaitingApproval = in_array($data['role'], [User::ROLE_SUPERVISOR, User::ROLE_ORGANISATION], true);
            $user->update([
                'role' => $data['role'],
                'is_approved' => ! $isAwaitingApproval,
                'approval_status' => $isAwaitingApproval ? 'pending' : 'approved',
                'approval_decided_by' => null,
                'approval_decided_at' => null,
            ]);
        }
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
        return $this->decideAccount($request, $user, 'approved');
    }

    public function rejectAccount(Request $request, User $user): RedirectResponse
    {
        return $this->decideAccount($request, $user, 'rejected');
    }

    private function decideAccount(Request $request, User $user, string $status): RedirectResponse
    {
        abort_unless(in_array($user->role, [User::ROLE_SUPERVISOR, User::ROLE_ORGANISATION], true), 404);
        abort_unless(in_array($status, ['approved', 'rejected'], true), 422);

        $user->update([
            'is_approved' => $status === 'approved',
            'approval_status' => $status,
            'approval_decided_by' => $request->user()->id,
            'approval_decided_at' => now(),
        ]);
        ActivityAudit::create([
            'actor_id' => $request->user()->id,
            'action' => 'account_'.$status,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'details' => ['role' => $user->role, 'status' => $status],
        ]);

        return back()->with('status', 'Account registration '.$status.'.');
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
