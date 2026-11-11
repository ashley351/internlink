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
    /** @var list<string> */
    private const HIDDEN_DEMO_INTERNSHIP_TITLES = [
        'Product Design Intern',
        'Finance Operations Intern',
        'Software Engineering Intern',
    ];

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'field' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'organization' => ['nullable', 'string', 'max:120'],
            'duration_weeks' => ['nullable', 'integer', 'min:1', 'max:52'],
        ]);
        $filters = [
            'q' => trim($validated['q'] ?? ''),
            'field' => trim($validated['field'] ?? ''),
            'location' => trim($validated['location'] ?? ''),
            'organization' => trim($validated['organization'] ?? ''),
            'duration_weeks' => $validated['duration_weeks'] ?? '',
        ];
        $hasFilters = collect($filters)->contains(fn (mixed $value): bool => $value !== '');

        $internships = Internship::query()
            ->open()
            ->where('location', 'like', '%'.config('internlink.city').'%')
            ->where(function (Builder $query): void {
                $query->whereNotIn('title', self::HIDDEN_DEMO_INTERNSHIP_TITLES)
                    ->orWhereHas('organisation', function (Builder $query): void {
                        $query->where('email', '<>', 'employer@internlink.test');
                    });
            })
            ->whereRaw('slots_total > (select count(*) from internship_applications where internship_applications.internship_id = internships.id and internship_applications.status in (?, ?))', ['offered', 'placed'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $term = '%'.$filters['q'].'%';

                    $query->where('title', 'like', $term)
                        ->orWhere('field', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('organisation', function (Builder $query) use ($term): void {
                            $query->where('name', 'like', $term);
                        });
                });
            })
            ->when($filters['field'] !== '', fn (Builder $query) => $query->where('field', 'like', '%'.$filters['field'].'%'))
            ->when($filters['location'] !== '', fn (Builder $query) => $query->where('location', 'like', '%'.$filters['location'].'%'))
            ->when($filters['organization'] !== '', function (Builder $query) use ($filters): void {
                $query->whereHas('organisation', function (Builder $query) use ($filters): void {
                    $query->where('name', 'like', '%'.$filters['organization'].'%');
                });
            })
            ->when($filters['duration_weeks'] !== '', fn (Builder $query) => $query->where('duration_weeks', (int) $filters['duration_weeks']))
            ->with('organisation')
            ->withCount(['applications as filled_slots' => fn ($query) => $query->whereIn('status', ['offered', 'placed'])])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('internships.index', compact('internships', 'filters', 'hasFilters'));
    }

    public function show(Internship $internship): View
    {
        abort_unless(
            $internship->status === 'open'
                && $internship->closes_at->greaterThanOrEqualTo(today())
                && str_contains(mb_strtolower($internship->location), mb_strtolower(config('internlink.city'))),
            404,
        );

        return view('internships.show', [
            'internship' => $internship->load('organisation')->loadCount([
                'applications as filled_slots' => fn ($query) => $query->whereIn('status', ['offered', 'placed']),
            ]),
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
