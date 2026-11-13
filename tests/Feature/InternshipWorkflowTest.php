<?php

use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\LogbookEntry;
use App\Models\User;
use App\Models\VisitRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

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
        'responsibilities' => 'Implement and test assigned software features.',
        'required_qualifications' => 'Current computer science student with basic programming skills.',
        'payment_information' => 'Payment details will be discussed during interviews.',
        'application_requirements' => 'Include an introductory letter addressed to the employer.',
        'eligible_courses' => ['Computer Science'],
        'slots_total' => 2,
        'duration_weeks' => 12,
        'closes_at' => today()->addDays(14)->format('Y-m-d'),
    ])->assertRedirect();

    $internship = Internship::firstOrFail();
    expect($internship->status)->toBe('open')
        ->and($internship->responsibilities)->toBe('Implement and test assigned software features.')
        ->and($internship->application_requirements)->toBe('Include an introductory letter addressed to the employer.');

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
        ->assertOk()->assertSee('Your organisation workspace');
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

test('approved organisations can manage only their profile and their own published opportunities', function () {
    $organisation = User::factory()->create([
        'role' => User::ROLE_ORGANISATION,
        'name' => 'Northstar Digital',
    ]);
    $otherOrganisation = User::factory()->create(['role' => User::ROLE_ORGANISATION]);
    $internship = Internship::create([
        'organisation_id' => $organisation->id,
        'title' => 'Data Intern',
        'field' => 'Data Science',
        'description' => 'Work with a data team on useful analysis projects.',
        'location' => 'Kampala',
        'slots_total' => 2,
        'duration_weeks' => 10,
        'closes_at' => today()->addDays(20),
        'status' => 'open',
    ]);

    $this->actingAs($organisation)->patch(route('organisation.profile.update'), [
        'name' => 'Northstar Analytics',
        'email' => $organisation->email,
        'organisation_phone' => '+256700000000',
        'organisation_website' => 'https://northstar.example.test',
        'organisation_description' => 'A local data organisation.',
    ])->assertRedirect();
    expect($organisation->fresh()->name)->toBe('Northstar Analytics')
        ->and($organisation->fresh()->organisation_phone)->toBe('+256700000000');

    $this->actingAs($organisation)->post(route('organisation.internships.store'), [
        'title' => 'Junior Data Analyst',
        'field' => 'Data Science',
        'description' => 'Support the team with reporting and data quality reviews.',
        'slots_total' => 2,
        'duration_weeks' => 8,
        'closes_at' => today()->addDays(18)->format('Y-m-d'),
    ])->assertRedirect();
    $this->assertDatabaseHas('internships', [
        'organisation_id' => $organisation->id,
        'title' => 'Junior Data Analyst',
        'status' => 'open',
    ]);

    $this->actingAs($organisation)->patch(route('organisation.internships.update', $internship), [
        'title' => 'Graduate Data Analyst Intern',
        'field' => 'Data Science',
        'description' => 'Work with the team to prepare, analyze and explain datasets.',
        'slots_total' => 3,
        'duration_weeks' => 12,
        'closes_at' => today()->addDays(25)->format('Y-m-d'),
    ])->assertRedirect();
    expect($internship->fresh()->title)->toBe('Graduate Data Analyst Intern')
        ->and($internship->fresh()->status)->toBe('open');

    $this->actingAs($otherOrganisation)
        ->patch(route('organisation.internships.update', $internship), [
            'title' => 'Unauthorized change',
        ])->assertNotFound();
    $this->actingAs($otherOrganisation)
        ->patch(route('organisation.internships.status', $internship), ['status' => 'closed'])
        ->assertNotFound();

    $this->actingAs($organisation)
        ->patch(route('organisation.internships.status', $internship), ['status' => 'closed'])
        ->assertRedirect();
    expect($internship->fresh()->status)->toBe('closed');

    $this->actingAs($organisation)
        ->patch(route('organisation.internships.status', $internship), ['status' => 'open'])
        ->assertRedirect();
    expect($internship->fresh()->status)->toBe('open');
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

test('public internship browse hides the sample cards without deleting listing records', function () {
    $organisation = User::factory()->create([
        'role' => User::ROLE_ORGANISATION,
        'email' => 'employer@internlink.test',
        'name' => 'BrightLabs Kampala',
    ]);
    $realOrganisation = User::factory()->create([
        'role' => User::ROLE_ORGANISATION,
        'name' => 'Independent Kampala Employer',
    ]);

    foreach ([
        'Product Design Intern',
        'Finance Operations Intern',
        'Software Engineering Intern',
        'Community Outreach Intern',
    ] as $title) {
        Internship::create([
            'organisation_id' => $organisation->id,
            'title' => $title,
            'field' => 'Technology',
            'description' => 'An open Kampala internship opportunity.',
            'location' => 'Kampala',
            'slots_total' => 2,
            'duration_weeks' => 8,
            'closes_at' => today()->addDays(14),
            'status' => 'open',
        ]);
    }

    Internship::create([
        'organisation_id' => $realOrganisation->id,
        'title' => 'Software Engineering Intern',
        'field' => 'Technology',
        'description' => 'A real Kampala internship opportunity.',
        'location' => 'Kampala',
        'slots_total' => 2,
        'duration_weeks' => 8,
        'closes_at' => today()->addDays(14),
        'status' => 'open',
    ]);

    $this->get(route('internships.index'))
        ->assertOk()
        ->assertDontSee('Product Design Intern')
        ->assertDontSee('Finance Operations Intern')
        ->assertSee('Software Engineering Intern')
        ->assertSee('Community Outreach Intern');

    expect(Internship::query()->count())->toBe(5);
});

test('students can search and filter open Kampala opportunities by organisation, field, location and duration', function () {
    $targetOrganisation = User::factory()->create([
        'role' => User::ROLE_ORGANISATION,
        'name' => 'Northstar Digital',
    ]);
    $otherOrganisation = User::factory()->create([
        'role' => User::ROLE_ORGANISATION,
        'name' => 'Other Kampala Employer',
    ]);

    Internship::create([
        'organisation_id' => $targetOrganisation->id,
        'title' => 'Junior Data Analyst',
        'field' => 'Data Science',
        'description' => 'Analyse business data and prepare reports for Kampala teams.',
        'location' => 'Kampala Central',
        'slots_total' => 3,
        'duration_weeks' => 12,
        'closes_at' => today()->addDays(18),
        'status' => 'open',
    ]);
    Internship::create([
        'organisation_id' => $otherOrganisation->id,
        'title' => 'Junior Data Analyst',
        'field' => 'Data Science',
        'description' => 'Analyse business data and prepare reports for Kampala teams.',
        'location' => 'Kampala',
        'slots_total' => 3,
        'duration_weeks' => 8,
        'closes_at' => today()->addDays(18),
        'status' => 'open',
    ]);

    $this->get(route('internships.index', [
        'q' => 'Analyst',
        'field' => 'Data',
        'location' => 'Central',
        'organization' => 'Northstar',
        'duration_weeks' => 12,
    ]))->assertOk()
        ->assertSee('Junior Data Analyst')
        ->assertSee('Northstar Digital')
        ->assertSee('value="Northstar"', false)
        ->assertDontSee('Other Kampala Employer');

    $this->get(route('internships.index'))
        ->assertOk()
        ->assertSee('id="organization-filter"', false)
        ->assertSee('name="organization"', false)
        ->assertDontSee('<select id="organization-filter"', false);

    $this->get(route('internships.index', ['organization' => 'Other Kampala']))
        ->assertOk()
        ->assertSee('Other Kampala Employer')
        ->assertDontSee('Northstar Digital');
});

test('public internship search excludes expired, unpublished and filled opportunities', function () {
    $organisation = User::factory()->create(['role' => User::ROLE_ORGANISATION, 'name' => 'Current Employer']);
    $baseListing = [
        'organisation_id' => $organisation->id,
        'field' => 'Technology',
        'description' => 'A current software placement in Kampala.',
        'location' => 'Kampala',
        'duration_weeks' => 12,
        'closes_at' => today()->addDays(10),
    ];

    $open = Internship::create([...$baseListing, 'title' => 'Open Role', 'slots_total' => 1, 'status' => 'open']);
    Internship::create([...$baseListing, 'title' => 'Expired Role', 'slots_total' => 1, 'status' => 'open', 'closes_at' => today()->subDay()]);
    Internship::create([...$baseListing, 'title' => 'Pending Role', 'slots_total' => 1, 'status' => 'pending']);
    Internship::create([...$baseListing, 'title' => 'Filled Role', 'slots_total' => 1, 'status' => 'open']);
    InternshipApplication::create([
        'internship_id' => Internship::query()->where('title', 'Filled Role')->value('id'),
        'student_id' => User::factory()->create(['role' => User::ROLE_STUDENT])->id,
        'cover_letter' => str_repeat('I have relevant skills and I am interested in this placement. ', 2),
        'status' => 'placed',
    ]);

    $this->get(route('internships.index'))
        ->assertOk()
        ->assertSee('Open Role')
        ->assertDontSee('Expired Role')
        ->assertDontSee('Pending Role')
        ->assertDontSee('Filled Role');

    $this->get(route('internships.show', $open))->assertOk();
});

test('internship detail page displays employer-provided role information and contact', function () {
    $organisation = User::factory()->create([
        'role' => User::ROLE_ORGANISATION,
        'name' => 'Northstar Digital',
        'email' => 'careers@northstar.example.test',
    ]);
    $internship = Internship::create([
        'organisation_id' => $organisation->id,
        'title' => 'Data Intern',
        'field' => 'Data Science',
        'description' => 'Work with the team on analysis projects.',
        'responsibilities' => 'Prepare datasets and summarize findings.',
        'required_qualifications' => 'Basic statistics and spreadsheet skills.',
        'payment_information' => 'Payment details will be discussed by the employer.',
        'application_requirements' => 'Submit an introductory letter addressed to Northstar Digital.',
        'eligible_courses' => ['Statistics', 'Computer Science'],
        'location' => 'Kampala',
        'slots_total' => 2,
        'duration_weeks' => 10,
        'closes_at' => today()->addDays(20),
        'status' => 'open',
    ]);

    $this->get(route('internships.show', $internship))
        ->assertOk()
        ->assertSee('Prepare datasets and summarize findings.')
        ->assertSee('Basic statistics and spreadsheet skills.')
        ->assertSee('Payment details will be discussed by the employer.')
        ->assertSee('Submit an introductory letter addressed to Northstar Digital.')
        ->assertSee('careers@northstar.example.test')
        ->assertSee('Data Intern');
});

test('students can access or manage their application letter for an organisation-specific opportunity', function () {
    $organisation = User::factory()->create([
        'role' => User::ROLE_ORGANISATION,
        'name' => 'Northstar Digital',
    ]);
    $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $student->studentProfile()->create([
        'application_letter_path' => 'student-application-letters/northstar.pdf',
    ]);
    $internship = Internship::create([
        'organisation_id' => $organisation->id,
        'title' => 'Data Intern',
        'field' => 'Data Science',
        'description' => 'Work with the team on analysis projects.',
        'location' => 'Kampala',
        'slots_total' => 2,
        'duration_weeks' => 10,
        'closes_at' => today()->addDays(20),
        'status' => 'open',
    ]);

    $this->actingAs($student)->get(route('internships.show', $internship))
        ->assertOk()
        ->assertSee('Application letter for Northstar Digital')
        ->assertSee('Download your application letter')
        ->assertSee(route('student.profile.application-letter'), false)
        ->assertSee('Introductory letter to Northstar Digital');

    $this->actingAs(User::factory()->create(['role' => User::ROLE_STUDENT]))
        ->get(route('internships.show', $internship))
        ->assertOk()
        ->assertSee('Manage application letter')
        ->assertSee(route('student.profile'), false);
});

test('internship detail migration safely adds missing fields without affecting listings', function () {
    $organisation = User::factory()->create(['role' => User::ROLE_ORGANISATION]);
    $internship = Internship::create([
        'organisation_id' => $organisation->id,
        'title' => 'Existing listing',
        'field' => 'Technology',
        'description' => 'Existing description that must remain unchanged.',
        'location' => 'Kampala',
        'slots_total' => 2,
        'duration_weeks' => 8,
        'closes_at' => today()->addDays(10),
        'status' => 'open',
    ]);

    Schema::table('internships', function (Blueprint $table): void {
        $table->dropColumn(['responsibilities', 'required_qualifications', 'payment_information', 'application_requirements']);
    });

    $migration = require database_path('migrations/2026_11_11_192918_add_internship_public_detail_fields.php');
    $migration->up();

    expect(Schema::hasColumn('internships', 'responsibilities'))->toBeTrue()
        ->and(Schema::hasColumn('internships', 'required_qualifications'))->toBeTrue()
        ->and(Schema::hasColumn('internships', 'payment_information'))->toBeTrue()
        ->and(Schema::hasColumn('internships', 'application_requirements'))->toBeTrue()
        ->and($internship->fresh()->description)->toBe('Existing description that must remain unchanged.');
});

test('internship detail migration can safely rerun after a partial application', function () {
    $organisation = User::factory()->create(['role' => User::ROLE_ORGANISATION]);
    $internship = Internship::create([
        'organisation_id' => $organisation->id,
        'title' => 'Retry-safe listing',
        'field' => 'Technology',
        'description' => 'Keep this current internship record unchanged.',
        'location' => 'Kampala',
        'slots_total' => 1,
        'duration_weeks' => 8,
        'closes_at' => today()->addDays(10),
        'status' => 'open',
    ]);
    $internship->update(['responsibilities' => 'Existing saved responsibilities.']);

    $migration = require database_path('migrations/2026_11_11_192918_add_internship_public_detail_fields.php');
    $migration->up();

    expect($internship->fresh()->responsibilities)->toBe('Existing saved responsibilities.')
        ->and($internship->fresh()->description)->toBe('Keep this current internship record unchanged.')
        ->and(Internship::query()->count())->toBe(1);
});
