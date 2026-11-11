<?php

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a student can register with their profile and a private application letter', function () {
    Storage::fake('local');

    $this->post(route('register.store'), [
        'role' => User::ROLE_STUDENT,
        'name' => 'Aisha Namuli',
        'email' => 'aisha@example.test',
        'phone' => '+256700000000',
        'password' => 'secure-pass-123',
        'password_confirmation' => 'secure-pass-123',
        'university' => 'Makerere University',
        'campus' => 'Main Campus',
        'faculty' => 'Computing and Informatics',
        'course' => 'Computer Science',
        'year_of_study' => '3',
        'student_registration_number' => '24/U/1234',
        'academic_interests' => 'Software engineering and accessible technology.',
        'application_letter' => UploadedFile::fake()->create('application-letter.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('dashboard.student'));

    $this->assertAuthenticated();
    $user = User::where('email', 'aisha@example.test')->firstOrFail();
    expect($user->role)->toBe(User::ROLE_STUDENT)
        ->and($user->is_approved)->toBeTrue()
        ->and(Hash::check('secure-pass-123', $user->password))->toBeTrue();

    $profile = $user->studentProfile()->firstOrFail();
    expect($profile->phone)->toBe('+256700000000')
        ->and($profile->year_of_study)->toBe(3)
        ->and($profile->institution?->name)->toBe('Makerere University');
    Storage::disk('local')->assertExists($profile->application_letter_path);

    $this->get(route('student.profile'))->assertOk()
        ->assertSee('Profile completion')
        ->assertSee('Aisha Namuli')
        ->assertSee('Download your current letter');
});

test('student registration requires the academic details and application letter', function () {
    $this->from(route('register'))->post(route('register.store'), [
        'role' => User::ROLE_STUDENT,
        'name' => 'Aisha Namuli',
        'email' => 'missing-letter@example.test',
        'phone' => '+256700000000',
        'password' => 'secure-pass-123',
        'password_confirmation' => 'secure-pass-123',
        'university' => 'Makerere University',
        'campus' => 'Main Campus',
        'faculty' => 'Computing and Informatics',
        'course' => 'Computer Science',
        'year_of_study' => '3',
        'student_registration_number' => '24/U/1235',
        'academic_interests' => 'Software engineering and accessible technology.',
    ])->assertRedirect(route('register'))
        ->assertSessionHasErrors('application_letter');

    $this->assertDatabaseMissing('users', ['email' => 'missing-letter@example.test']);
});

test('a student can update their profile and replace their application letter', function () {
    Storage::fake('local');
    $user = User::factory()->create([
        'email' => 'student@example.test',
        'university' => 'Makerere University',
        'course' => 'Computer Science',
    ]);
    Storage::disk('local')->put('student-application-letters/old.pdf', 'old letter');
    $profile = $user->studentProfile()->create([
        'phone' => '+256700000000',
        'campus' => 'Old Campus',
        'faculty' => 'Old Faculty',
        'year_of_study' => 2,
        'student_registration_number' => 'OLD-123',
        'academic_interests' => 'Old interests',
        'application_letter_path' => 'student-application-letters/old.pdf',
    ]);

    $this->actingAs($user)->patch(route('student.profile.update'), [
        'name' => 'Aisha Updated',
        'email' => 'student@example.test',
        'phone' => '+256711111111',
        'university' => 'Makerere University',
        'campus' => 'New Campus',
        'faculty' => 'Engineering',
        'course' => 'Software Engineering',
        'year_of_study' => '3',
        'student_registration_number' => 'NEW-123',
        'academic_interests' => 'Human centered software design.',
        'application_letter' => UploadedFile::fake()->create('updated-letter.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('student.profile'))
        ->assertSessionHas('status');

    $user->refresh();
    $profile->refresh();
    expect($user->name)->toBe('Aisha Updated')
        ->and($user->course)->toBe('Software Engineering')
        ->and($profile->campus)->toBe('New Campus')
        ->and($profile->student_registration_number)->toBe('NEW-123')
        ->and($profile->application_letter_path)->not->toBe('student-application-letters/old.pdf');
    Storage::disk('local')->assertMissing('student-application-letters/old.pdf');
    Storage::disk('local')->assertExists($profile->application_letter_path);

    $this->actingAs($user)->get(route('student.profile.application-letter'))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=application-letter.pdf');
});

test('only authenticated students can view or download their own profile documents', function () {
    $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);
    Storage::fake('local');
    Storage::disk('local')->put('student-application-letters/private.pdf', 'private letter');
    $student->studentProfile()->create(['application_letter_path' => 'student-application-letters/private.pdf']);

    $this->get(route('student.profile'))->assertRedirect(route('login'));
    $this->actingAs($otherStudent)->get(route('student.profile.application-letter'))->assertNotFound();
    $this->actingAs(User::factory()->create(['role' => User::ROLE_ORGANISATION]))
        ->get(route('student.profile'))->assertForbidden();
    $this->actingAs($student)->get(route('student.profile.application-letter'))->assertOk();
});

test('application letter uploads reject non-document files', function () {
    Storage::fake('local');

    $this->from(route('register'))->post(route('register.store'), [
        'role' => User::ROLE_STUDENT,
        'name' => 'Aisha Namuli',
        'email' => 'invalid-letter@example.test',
        'phone' => '+256700000000',
        'password' => 'secure-pass-123',
        'password_confirmation' => 'secure-pass-123',
        'university' => 'Makerere University',
        'campus' => 'Main Campus',
        'faculty' => 'Computing and Informatics',
        'course' => 'Computer Science',
        'year_of_study' => '3',
        'student_registration_number' => '24/U/1236',
        'academic_interests' => 'Software engineering and accessible technology.',
        'application_letter' => UploadedFile::fake()->create('not-a-letter.jpg', 100, 'image/jpeg'),
    ])->assertRedirect(route('register'))
        ->assertSessionHasErrors('application_letter');

    $this->assertDatabaseMissing('users', ['email' => 'invalid-letter@example.test']);
});

test('student registration shows one alphabetized searchable list of active Kampala institutions', function () {
    $response = $this->get(route('register'))->assertOk()
        ->assertSee('list="institution-options"', false)
        ->assertSee('<datalist id="institution-options">', false)
        ->assertSee('Islamic University in Uganda, Kampala Campus')
        ->assertDontSee('Muteesa I Royal University')
        ->assertDontSee('St. Peter’s University');

    $optionNames = Institution::query()->where('is_active', true)->orderBy('name')->pluck('name')->all();
    $sortedNames = $optionNames;
    sort($sortedNames, SORT_STRING | SORT_FLAG_CASE);

    expect($optionNames)->toBe($sortedNames)
        ->and($optionNames)->toHaveCount(22);
});

test('institutions migration can safely rerun when its table already exists', function () {
    $institution = Institution::query()->where('name', 'Makerere University')->firstOrFail();
    $institution->update([
        'is_active' => false,
        'accreditation_status' => 'Administrator-verified status',
    ]);
    $student = User::factory()->create([
        'role' => User::ROLE_STUDENT,
        'university' => 'Makerere University',
    ]);
    $profile = $student->studentProfile()->create();

    $migration = require database_path('migrations/2026_11_11_144419_create_institutions_table.php');
    $migration->up();

    expect(Institution::query()->count())->toBe(22)
        ->and($institution->fresh()->is_active)->toBeFalse()
        ->and($institution->fresh()->accreditation_status)->toBe('Administrator-verified status')
        ->and($profile->fresh()->institution_id)->toBe($institution->id);
});

test('only administrators can add, edit and deactivate institutions without duplicates', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $newInstitution = [
        'name' => 'Example Kampala Institution',
        'accreditation_status' => 'NCHE registered institution',
        'source_url' => 'https://unche.or.ug/universities/',
    ];

    $this->actingAs($student)->get(route('admin.institutions.index'))->assertForbidden();
    $this->actingAs($student)->post(route('admin.institutions.store'), $newInstitution)->assertForbidden();

    $this->actingAs($admin)->get(route('admin.institutions.index'))->assertOk()
        ->assertSee('Kampala institutions')
        ->assertSee('Aga Khan University');
    $this->actingAs($admin)->post(route('admin.institutions.store'), $newInstitution)
        ->assertRedirect(route('admin.institutions.index'));

    $institution = Institution::query()->where('name', $newInstitution['name'])->firstOrFail();
    expect($institution->district)->toBe('Kampala')
        ->and($institution->is_active)->toBeTrue();

    $this->actingAs($admin)->post(route('admin.institutions.store'), [
        ...$newInstitution,
        'name' => '  example kampala institution  ',
    ])->assertSessionHasErrors('name');

    $this->actingAs($admin)->patch(route('admin.institutions.update', $institution), [
        ...$newInstitution,
        'name' => 'Updated Kampala Institution',
    ])->assertRedirect(route('admin.institutions.index'));
    expect($institution->fresh()->name)->toBe('Updated Kampala Institution');

    $this->actingAs($admin)->patch(route('admin.institutions.status', $institution))
        ->assertRedirect(route('admin.institutions.index'));
    expect($institution->fresh()->is_active)->toBeFalse();
    $this->get(route('register'))->assertDontSee('Updated Kampala Institution');

    $this->actingAs($admin)->patch(route('admin.institutions.status', $institution));
    expect($institution->fresh()->is_active)->toBeTrue();
});
