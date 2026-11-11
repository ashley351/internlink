<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Models\InternshipApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InternshipController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['q'] ?? '');

        $internships = Internship::query()
            ->open()
            ->where('location', config('internlink.city'))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $term = '%'.$search.'%';

                    $query->where('title', 'like', $term)
                        ->orWhere('field', 'like', $term)
                        ->orWhere('location', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('organisation', function (Builder $query) use ($term): void {
                            $query->where('name', 'like', $term);
                        });
                });
            })
            ->with('organisation')
            ->withCount(['applications as filled_slots' => fn ($query) => $query->whereIn('status', ['offered', 'placed'])])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('internships.index', compact('internships', 'search'));
    }

    public function show(Internship $internship): View
    {
        abort_unless($internship->status === 'open' && $internship->location === config('internlink.city'), 404);

        return view('internships.show', [
            'internship' => $internship->load('organisation'),
            'alreadyApplied' => Auth::check() && $internship->applications()->where('student_id', Auth::id())->exists(),
        ]);
    }

    public function apply(Request $request, Internship $internship): RedirectResponse
    {
        abort_unless($request->user()->hasRole('student'), 403);

        $validated = $request->validate([
            'cover_letter' => ['required', 'string', 'min:40', 'max:5000'],
        ]);

        abort_if($request->user()->internshipApplications()->whereNotIn('status', ['rejected', 'withdrawn'])->count() >= 6, 422, 'You have reached the six-application limit.');

        $application = DB::transaction(function () use ($request, $internship, $validated) {
            $lockedInternship = Internship::query()->lockForUpdate()->findOrFail($internship->id);
            abort_unless($lockedInternship->status === 'open'
                && $lockedInternship->location === config('internlink.city')
                && $lockedInternship->closes_at->greaterThanOrEqualTo(today()), 422, 'This internship is no longer accepting applications.');

            abort_if($lockedInternship->applications()->where('student_id', $request->user()->id)->exists(), 422, 'You have already applied to this internship.');

            $placedCount = $lockedInternship->applications()->whereIn('status', ['offered', 'placed'])->count();
            abort_if($placedCount >= $lockedInternship->slots_total, 422, 'This internship has filled all its slots.');

            return InternshipApplication::create([
                'internship_id' => $lockedInternship->id,
                'student_id' => $request->user()->id,
                'cover_letter' => $validated['cover_letter'],
            ]);
        });

        return redirect()->route('dashboard')->with('status', 'Your application was submitted.');
    }
}
