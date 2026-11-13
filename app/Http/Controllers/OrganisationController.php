<?php

namespace App\Http\Controllers;

use App\Models\InternshipApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $data = $this->validatedInternship($request);

        $request->user()->internships()->create([
            ...$data,
            'eligible_courses' => $data['eligible_courses'] ?? null,
            'location' => config('internlink.city'),
            'status' => 'open',
        ]);

        return back()->with('status', 'Internship opportunity published.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'organisation_phone' => ['nullable', 'string', 'max:30'],
            'organisation_website' => ['nullable', 'url', 'max:255'],
            'organisation_description' => ['nullable', 'string', 'max:5000'],
        ]);

        $request->user()->update($data);

        return back()->with('status', 'Organisation profile updated.');
    }

    public function updateInternship(Request $request, int $internship): RedirectResponse
    {
        $listing = $request->user()->internships()->findOrFail($internship);
        $data = $this->validatedInternship($request);

        $listing->update([
            ...$data,
            'eligible_courses' => $data['eligible_courses'] ?? null,
            'location' => config('internlink.city'),
        ]);

        return back()->with('status', 'Internship opportunity updated.');
    }

    public function updateInternshipStatus(Request $request, int $internship): RedirectResponse
    {
        $listing = $request->user()->internships()->findOrFail($internship);
        abort_if($listing->status === 'rejected', 422, 'This opportunity was rejected by an administrator and cannot be reopened.');
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'closed'])],
        ]);

        if ($data['status'] === 'open') {
            abort_if($listing->closes_at->isPast(), 422, 'The application deadline has passed. Update the listing deadline before reopening it.');
            abort_if($listing->slots_left < 1, 422, 'This opportunity has no positions remaining.');
        }

        $listing->update(['status' => $data['status']]);

        return back()->with('status', 'Opportunity status updated.');
    }

    private function validatedInternship(Request $request): array
    {
        return $request->validate([
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
