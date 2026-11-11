<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\PlacementMessage;
use App\Models\VisitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $applications = $user->internshipApplications()->with('internship.organisation')->latest()->get();
        $appliedIds = $applications->pluck('internship_id');
        $internships = Internship::query()->open()
            ->where('location', config('internlink.city'))
            ->where(function ($query) use ($user) {
                $query->whereNull('eligible_courses')->orWhereJsonContains('eligible_courses', $user->course);
            })
            ->whereNotIn('id', $appliedIds)
            ->with('organisation')
            ->withCount(['applications as filled_slots' => fn ($query) => $query->whereIn('status', ['offered', 'placed'])])
            ->latest()->take(6)->get();

        return view('workspaces.student', [
            'user' => $user,
            'applications' => $applications,
            'internships' => $internships,
            'supervisorAssignment' => $user->supervisorAssignment()->with('supervisor')->first(),
            'logbooks' => $user->logbookEntries()->latest('week_number')->take(5)->get(),
            'nextWeek' => ((int) $user->logbookEntries()->max('week_number')) + 1,
        ]);
    }

    public function supervision(Request $request): View
    {
        $user = $request->user();
        $assignment = $user->supervisorAssignment()->with('supervisor')->first();

        return view('workspaces.student-supervision', [
            'user' => $user,
            'assignment' => $assignment,
            'messages' => $assignment
                ? PlacementMessage::where('student_id', $user->id)->where('supervisor_id', $assignment->supervisor_id)->with('sender')->oldest()->get()
                : collect(),
            'visits' => $user->visitRequests()->latest('requested_for')->get(),
        ]);
    }

    public function submitLogbook(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'week_number' => ['required', 'integer', 'min:1', 'max:52'],
            'work_summary' => ['required', 'string', 'min:20', 'max:5000'],
            'learnings' => ['required', 'string', 'min:20', 'max:5000'],
            'hours_worked' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        abort_unless($request->user()->internshipApplications()->where('status', 'placed')->exists(), 403, 'A confirmed placement is required to submit a logbook.');

        LogbookEntry::updateOrCreate(
            ['student_id' => $request->user()->id, 'week_number' => $validated['week_number']],
            [...$validated, 'status' => 'pending', 'supervisor_feedback' => null, 'reviewed_by' => null, 'reviewed_at' => null],
        );

        return back()->with('status', 'Your weekly logbook entry was submitted.');
    }

    public function sendMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:3000'],
        ]);
        $assignment = $request->user()->supervisorAssignment()->firstOrFail();

        PlacementMessage::create([
            'student_id' => $request->user()->id,
            'supervisor_id' => $assignment->supervisor_id,
            'sender_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return back()->with('status', 'Message sent to your supervisor.');
    }

    public function requestVisit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requested_for' => ['required', 'date', 'after:now'],
            'location' => ['required', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $assignment = $request->user()->supervisorAssignment()->firstOrFail();

        VisitRequest::create([
            ...$validated,
            'student_id' => $request->user()->id,
            'supervisor_id' => $assignment->supervisor_id,
        ]);

        return back()->with('status', 'Visit request sent to your supervisor.');
    }
}
