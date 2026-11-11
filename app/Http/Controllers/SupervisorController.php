<?php

namespace App\Http\Controllers;

use App\Models\LogbookEntry;
use App\Models\PlacementMessage;
use App\Models\VisitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorController extends Controller
{
    public function dashboard(Request $request): View
    {
        $students = $request->user()->supervisedStudents()->with('student')->get()->pluck('student');
        $studentIds = $students->pluck('id');
        $supervisor = $request->user();

        return view('workspaces.supervisor', [
            'user' => $supervisor,
            'students' => $students,
            'logbooks' => LogbookEntry::whereIn('student_id', $studentIds)->with('student')->latest()->get(),
            'visits' => VisitRequest::whereIn('student_id', $studentIds)
                ->where('supervisor_id', $supervisor->id)
                ->with('student')->latest('requested_for')->get(),
            'messages' => PlacementMessage::whereIn('student_id', $studentIds)->where('supervisor_id', $supervisor->id)->with('sender')->oldest()->get(),
        ]);
    }

    public function sendMessage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'min:1', 'max:3000'],
        ]);
        abort_unless($request->user()->supervisedStudents()->where('student_id', $data['student_id'])->exists(), 403);

        PlacementMessage::create([
            'student_id' => $data['student_id'],
            'supervisor_id' => $request->user()->id,
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Message sent to the student.');
    }

    public function reviewLogbook(Request $request, LogbookEntry $entry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,changes_requested'],
            'supervisor_feedback' => ['required', 'string', 'min:3', 'max:3000'],
        ]);
        abort_unless($request->user()->supervisedStudents()->where('student_id', $entry->student_id)->exists(), 403);

        $entry->update([
            ...$data,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Logbook review saved.');
    }

    public function respondToVisit(Request $request, VisitRequest $visit): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:approved,declined']]);
        abort_unless($visit->supervisor_id === $request->user()->id, 403);
        $visit->update($data);

        return back()->with('status', 'Visit request updated.');
    }
}
