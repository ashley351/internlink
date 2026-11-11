<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\PlacementMessage;
use App\Models\VisitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    public function showProfile(Request $request): View
    {
        $user = $request->user();
        $profile = $user->studentProfile;
        $completionFields = [
            $user->name,
            $user->email,
            $user->university,
            $user->course,
            $profile?->phone,
            $profile?->campus,
            $profile?->faculty,
            $profile?->year_of_study,
            $profile?->student_registration_number,
            $profile?->academic_interests,
            $profile?->application_letter_path,
        ];
        $profileCompletion = (int) round(collect($completionFields)->filter(fn ($value) => filled($value))->count() / count($completionFields) * 100);
        $institutions = Institution::query()
            ->where('is_active', true)
            ->when($profile?->institution_id, fn ($query) => $query->orWhere('id', $profile->institution_id))
            ->orderBy('name')
            ->get();

        return view('student.profile', compact('user', 'profile', 'profileCompletion', 'institutions'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->studentProfile;
        $letterIsRequired = blank($profile?->application_letter_path);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30'],
            'university' => [
                'required',
                'string',
                Rule::exists('institutions', 'name')->where(function ($query) use ($profile): void {
                    $query->where('is_active', true);

                    if ($profile?->institution_id !== null) {
                        $query->orWhere('id', $profile->institution_id);
                    }
                }),
            ],
            'campus' => ['required', 'string', 'max:120'],
            'faculty' => ['required', 'string', 'max:160'],
            'course' => ['required', 'string', 'max:120'],
            'year_of_study' => ['required', 'integer', 'min:1', 'max:12'],
            'student_registration_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('student_profiles', 'student_registration_number')->ignore($profile?->id),
            ],
            'academic_interests' => ['required', 'string', 'max:2000'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'application_letter' => [$letterIsRequired ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx,odt', 'max:5120'],
        ]);

        $newLetterPath = $request->file('application_letter')?->store('student-application-letters', 'local');
        $oldLetterPath = $profile?->application_letter_path;

        try {
            DB::transaction(function () use ($user, $profile, $validated, $newLetterPath): void {
                $user->fill([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'university' => $validated['university'],
                    'course' => $validated['course'],
                ]);

                if (filled($validated['password'] ?? null)) {
                    $user->password = $validated['password'];
                }

                $user->save();

                $user->studentProfile()->updateOrCreate([], [
                    'phone' => $validated['phone'],
                    'campus' => $validated['campus'],
                    'faculty' => $validated['faculty'],
                    'year_of_study' => $validated['year_of_study'],
                    'student_registration_number' => $validated['student_registration_number'],
                    'academic_interests' => $validated['academic_interests'],
                    'institution_id' => Institution::query()->where('name', $validated['university'])->value('id'),
                    'application_letter_path' => $newLetterPath ?? $profile?->application_letter_path,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($newLetterPath !== null) {
                Storage::disk('local')->delete($newLetterPath);
            }

            throw $exception;
        }

        if ($newLetterPath !== null && $oldLetterPath !== null) {
            Storage::disk('local')->delete($oldLetterPath);
        }

        return redirect()->route('student.profile')->with('status', 'Your student profile has been updated.');
    }

    public function downloadApplicationLetter(Request $request): BinaryFileResponse
    {
        $path = $request->user()->studentProfile?->application_letter_path;

        abort_unless($path !== null && Storage::disk('local')->exists($path), 404);

        return response()->download(
            Storage::disk('local')->path($path),
            'application-letter.'.pathinfo($path, PATHINFO_EXTENSION),
        );
    }

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
