<?php

use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\LogbookEntry;
use App\Models\User;
use App\Models\VisitRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('kampala placement moves through application, supervision and logbook review', function () {
    $organisation = User::factory()->create(['role' => User::ROLE_ORGANISATION, 'name' => 'Kampala Tech Ltd']);
    $student = User::factory()->create([
        'role' => User::ROLE_STUDENT,
        'course' => 'Computer Science',
        'university' => 'Makerere University',
    ]);
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($organisation)->post(route('organisation.internships.store'), [
        'title' => 'Software Engineering Intern',
        'field' => 'Technology',
        'description' => str_repeat('Build and test useful software with a Kampala product team. ', 2),
        'eligible_courses' => ['Computer Science'],
        'slots_total' => 2,
        'duration_weeks' => 12,
        'closes_at' => today()->addDays(14)->format('Y-m-d'),
    ])->assertRedirect();

    $internship = Internship::firstOrFail();
    expect($internship->status)->toBe('pending');

    $this->actingAs($admin)->patch(route('admin.internships.moderate', $internship), [
        'status' => 'open',
    ])->assertRedirect();

    $this->actingAs($student)->get(route('internships.index'))
        ->assertOk()->assertSee('Software Engineering Intern');
    $this->actingAs($student)->post(route('internships.apply', $internship), [
        'cover_letter' => str_repeat('I am a motivated computer science student with relevant project experience. ', 2),
    ])->assertRedirect(route('dashboard'));

    $application = InternshipApplication::firstOrFail();
    expect($application->status)->toBe('submitted');

    $this->actingAs($organisation)->patch(route('organisation.applications.update', $application), [
        'status' => 'placed',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.supervisors.assign'), [
        'student_id' => $student->id,
        'supervisor_id' => $supervisor->id,
    ])->assertRedirect();
    $this->assertDatabaseHas('supervisor_assignments', [
        'student_id' => $student->id,
        'supervisor_id' => $supervisor->id,
    ]);

    $this->actingAs($student)->post(route('student.logbooks.store'), [
        'week_number' => 1,
        'work_summary' => 'Completed onboarding and implemented a small feature for the team.',
        'learnings' => 'Practised code review, testing and team communication with a mentor.',
        'hours_worked' => 35,
    ])->assertRedirect();

    $entry = LogbookEntry::firstOrFail();
    $this->actingAs($supervisor)->patch(route('supervisor.logbooks.review', $entry), [
        'status' => 'approved',
        'supervisor_feedback' => 'Good progress. Continue documenting your work each week.',
    ])->assertRedirect();
    expect($entry->fresh()->status)->toBe('approved');

    $this->actingAs($student)->post(route('student.messages.store'), [
        'body' => 'I have submitted my first weekly logbook.',
    ])->assertRedirect();
    $this->assertDatabaseHas('placement_messages', [
        'student_id' => $student->id,
        'supervisor_id' => $supervisor->id,
    ]);

    $this->actingAs($student)->post(route('student.visits.store'), [
        'requested_for' => now()->addDays(3)->format('Y-m-d H:i:s'),
        'location' => 'Makerere University, Kampala',
        'notes' => 'Please review my placement progress.',
    ])->assertRedirect();
    $visit = VisitRequest::firstOrFail();
    $this->actingAs($supervisor)->patch(route('supervisor.visits.respond', $visit), [
        'status' => 'approved',
    ])->assertRedirect();
    expect($visit->fresh()->status)->toBe('approved');

    $this->actingAs($student)->get(route('dashboard.student'))
        ->assertOk()
        ->assertSee('Your applications')
        ->assertSee('Applications made')
        ->assertSee('Placed: 1')
        ->assertDontSee('Interview invitations')
        ->assertDontSee('Offers / placements')
        ->assertDontSee('Profile basics');
    $this->actingAs($student)->get(route('dashboard.supervision'))
        ->assertOk()->assertSee('Request supervision');
    $this->actingAs($supervisor)->get(route('dashboard.university'))
        ->assertOk()->assertSee('Review submissions');
    $this->actingAs($organisation)->get(route('dashboard.organisation'))
        ->assertOk()->assertSee('Applications per week');
    $this->actingAs($admin)->get(route('dashboard.admin'))
        ->assertOk()
        ->assertSee('Listing review')
        ->assertSee('All internship applications')
        ->assertSee('All supervision assignments')
        ->assertSee('All student logbooks')
        ->assertSee('All supervision visits')
        ->assertSee('All supervision messages')
        ->assertSee('Completed onboarding and implemented a small feature for the team.')
        ->assertSee('I have submitted my first weekly logbook.')
        ->assertSee('Makerere University, Kampala')
        ->assertSee($supervisor->name);
});

test('organisations cannot update applications for another organisation', function () {
    $owner = User::factory()->create(['role' => User::ROLE_ORGANISATION]);
    $outsider = User::factory()->create(['role' => User::ROLE_ORGANISATION]);
    $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $internship = Internship::create([
        'organisation_id' => $owner->id,
        'title' => 'Design Intern',
        'field' => 'Design',
        'description' => 'A Kampala role with enough details for this test.',
        'location' => 'Kampala',
        'slots_total' => 1,
        'duration_weeks' => 8,
        'closes_at' => today()->addDays(10),
        'status' => 'open',
    ]);
    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'cover_letter' => str_repeat('Relevant experience and a strong interest in this role. ', 2),
    ]);

    $this->actingAs($outsider)->patch(route('organisation.applications.update', $application), [
        'status' => 'shortlisted',
    ])->assertForbidden();
});

test('lecturers only see visits assigned to them for their current students', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $otherSupervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $supervisor->supervisedStudents()->create(['student_id' => $student->id]);

    VisitRequest::create([
        'student_id' => $student->id,
        'supervisor_id' => $supervisor->id,
        'requested_for' => now()->addDays(2),
        'location' => 'Assigned lecturer visit',
        'notes' => 'This request belongs to the assigned lecturer.',
    ]);
    VisitRequest::create([
        'student_id' => $student->id,
        'supervisor_id' => $otherSupervisor->id,
        'requested_for' => now()->addDays(3),
        'location' => 'Other lecturer visit',
        'notes' => 'This request belongs to a different lecturer.',
    ]);

    $this->actingAs($supervisor)->get(route('dashboard.university'))
        ->assertOk()
        ->assertSee('Assigned lecturer visit')
        ->assertDontSee('Other lecturer visit');
});

test('students can search internships by organisation and role', function () {
    $organisation = User::factory()->create([
        'role' => User::ROLE_ORGANISATION,
        'name' => 'Kampala Tech Ltd',
    ]);
    $otherOrganisation = User::factory()->create([
        'role' => User::ROLE_ORGANISATION,
        'name' => 'Creative Studio',
    ]);

    Internship::create([
        'organisation_id' => $organisation->id,
        'title' => 'Software Engineering Intern',
        'field' => 'Technology',
        'description' => 'Build software with a Kampala product team.',
        'location' => 'Kampala',
        'slots_total' => 2,
        'duration_weeks' => 12,
        'closes_at' => today()->addDays(14),
        'status' => 'open',
    ]);
    Internship::create([
        'organisation_id' => $otherOrganisation->id,
        'title' => 'Design Intern',
        'field' => 'Design',
        'description' => 'Create visual materials for a creative team.',
        'location' => 'Kampala',
        'slots_total' => 1,
        'duration_weeks' => 8,
        'closes_at' => today()->addDays(14),
        'status' => 'open',
    ]);

    $this->actingAs(User::factory()->create(['role' => User::ROLE_STUDENT]))
        ->get(route('internships.index', ['q' => 'Kampala Tech']))
        ->assertOk()
        ->assertSee('Software Engineering Intern')
        ->assertSee('value="Kampala Tech"', false)
        ->assertDontSee('Design Intern');

    $this->get(route('internships.index', ['q' => 'Software']))
        ->assertOk()
        ->assertSee('Software Engineering Intern')
        ->assertDontSee('Design Intern');
});
