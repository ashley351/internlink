<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function index(): View
    {
        return view('admin.institutions', [
            'institutions' => Institution::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedInstitution($request);
        Institution::create($validated + ['district' => 'Kampala', 'is_active' => true]);

        return redirect()->route('admin.institutions.index')->with('status', 'Institution added to the student selection list.');
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $institution->update($this->validatedInstitution($request, $institution));

        return redirect()->route('admin.institutions.index')->with('status', 'Institution details updated.');
    }

    public function toggleActive(Institution $institution): RedirectResponse
    {
        $institution->update(['is_active' => ! $institution->is_active]);

        return redirect()->route('admin.institutions.index')->with(
            'status',
            $institution->is_active ? 'Institution activated.' : 'Institution deactivated. Existing student profiles are unchanged.',
        );
    }

    /**
     * @return array{name: string, accreditation_status: string, source_url: string}
     */
    private function validatedInstitution(Request $request, ?Institution $institution = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:180',
                function (string $attribute, mixed $value, \Closure $fail) use ($institution): void {
                    $duplicateExists = Institution::query()
                        ->where('normalized_name', Str::lower(trim((string) $value)))
                        ->when($institution, fn (Builder $query) => $query->where('id', '<>', $institution->id))
                        ->exists();

                    if ($duplicateExists) {
                        $fail('An institution with this name already exists.');
                    }
                },
            ],
            'accreditation_status' => ['required', 'string', 'max:180'],
            'source_url' => ['required', 'url', 'max:500'],
        ]);

        return [
            'name' => trim($validated['name']),
            'accreditation_status' => $validated['accreditation_status'],
            'source_url' => $validated['source_url'],
        ];
    }
}
