<?php

namespace App\Http\Controllers;

use App\Models\InternshipApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganisationController extends Controller
{
    public function dashboard(Request $request): View
    {
        $internships = $request->user()->internships()
            ->with(['applications.student'])
            ->withCount(['applications as placed_count' => fn ($query) => $query->whereIn('status', ['offered', 'placed'])])
            ->latest()->get();
        $applications = InternshipApplication::whereIn('internship_id', $internships->pluck('id'))
            ->with(['student', 'internship'])->latest()->get();
        $applicationsByUniversity = $applications->groupBy(fn ($application) => $application->student->university)
            ->map(fn ($group) => $group->count())->sortDesc();
        $applicationsPerWeek = collect(range(5, 0))->mapWithKeys(function (int $weeksAgo) use ($applications) {
            $week = now()->startOfWeek()->subWeeks($weeksAgo);
            $label = $week->format('M d');

            return [$label => $applications->filter(fn ($application) => $application->created_at->between($week, (clone $week)->endOfWeek()))->count()];
        });

        return view('workspaces.organisation', [
            'user' => $request->user(),
            'internships' => $internships,
            'applications' => $applications,
            'applicationsByUniversity' => $applicationsByUniversity,
            'applicationsPerWeek' => $applicationsPerWeek,
            'maxApplicationsPerWeek' => max(1, (int) $applicationsPerWeek->max()),
            'totalApplicants' => $applications->count(),
            'interviews' => $applications->where('status', 'interview')->count(),
            'placements' => $applications->whereIn('status', ['offered', 'placed'])->count(),
        ]);
    }

    public function storeInternship(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'field' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'min:30', 'max:10000'],
            'responsibilities' => ['nullable', 'string', 'max:10000'],
            'required_qualifications' => ['nullable', 'string', 'max:10000'],
            'payment_information' => ['nullable', 'string', 'max:3000'],
            'application_requirements' => ['nullable', 'string', 'max:3000'],
            'eligible_courses' => ['nullable', 'array', 'max:20'],
            'eligible_courses.*' => ['string', 'max:120'],
            'slots_total' => ['required', 'integer', 'min:1', 'max:500'],
            'duration_weeks' => ['required', 'integer', 'min:1', 'max:52'],
            'closes_at' => ['required', 'date', 'after:today'],
        ]);

        $request->user()->internships()->create([
            ...$data,
            'eligible_courses' => $data['eligible_courses'] ?? null,
            'location' => config('internlink.city'),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Internship listing published.');
    }

    public function updateApplication(Request $request, InternshipApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:shortlisted,interview,offered,placed,rejected'],
            'interview_at' => ['nullable', 'required_if:status,interview', 'date', 'after:now'],
        ]);
        abort_unless($request->user()->internships()->whereKey($application->internship_id)->exists(), 403);

        if (in_array($data['status'], ['offered', 'placed'], true)) {
            $occupied = $application->internship->applications()
                ->whereIn('status', ['offered', 'placed'])
                ->whereKeyNot($application->id)
                ->count();
            abort_if($occupied >= $application->internship->slots_total, 422, 'All placement slots for this listing are already allocated.');
        }

        $application->update([
            'status' => $data['status'],
            'interview_at' => $data['status'] === 'interview' ? ($data['interview_at'] ?? null) : null,
        ]);

        return back()->with('status', 'Applicant status updated.');
    }
}
