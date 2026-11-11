<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('the landing page introduces the internship platform', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Turn what you learn')
        ->assertSee('Explore internships')
        ->assertSee('For universities')
        ->assertSee('How it works');
});

test('a student can register and receives only the student role', function () {
    Storage::fake('local');

    $this->post(route('register.store'), [
        'name' => 'Aisha Namuli',
        'email' => 'aisha@example.test',
        'role' => User::ROLE_STUDENT,
        'course' => 'Computer Science',
        'university' => 'Makerere University',
        'phone' => '+256700000000',
        'campus' => 'Main Campus',
        'faculty' => 'Computing and Informatics',
        'year_of_study' => '3',
        'student_registration_number' => '24/U/1234',
        'academic_interests' => 'Software engineering and accessible technology.',
        'application_letter' => UploadedFile::fake()->create('application-letter.pdf', 100, 'application/pdf'),
        'password' => 'secure-pass-123',
        'password_confirmation' => 'secure-pass-123',
    ])->assertRedirect(route('dashboard.student'));

    $this->assertAuthenticated();
    expect(auth()->user()->role)->toBe(User::ROLE_STUDENT)
        ->and(auth()->user()->is_approved)->toBeTrue();
});

test('administrator registration requires the configured invitation code', function () {
    $registration = [
        'name' => 'Admin User',
        'email' => 'admin-signup@example.test',
        'role' => User::ROLE_ADMIN,
        'password' => 'secure-pass-123',
        'password_confirmation' => 'secure-pass-123',
    ];

    config(['internlink.admin_invitation_code' => null]);
    $this->from(route('register'))->post(route('register.store'), [
        ...$registration,
        'admin_invitation_code' => 'unconfigured-code',
    ])->assertRedirect(route('register'))
        ->assertSessionHasErrors('admin_invitation_code');
    $this->assertGuest();

    config(['internlink.admin_invitation_code' => 'private-admin-invite']);
    $this->from(route('register'))->post(route('register.store'), $registration)
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('admin_invitation_code');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => $registration['email']]);

    $this->post(route('register.store'), [
        ...$registration,
        'admin_invitation_code' => 'private-admin-invite',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    expect(auth()->user()->role)->toBe(User::ROLE_ADMIN)
        ->and(auth()->user()->is_approved)->toBeTrue();
});

test('lecturer and organisation accounts require approval before sign in', function () {
    $accounts = [
        [
            'name' => 'Grace Lecturer',
            'email' => 'grace@example.test',
            'role' => User::ROLE_SUPERVISOR,
            'university' => 'Makerere University',
        ],
        [
            'name' => 'BrightLabs',
            'email' => 'brightlabs@example.test',
            'role' => User::ROLE_ORGANISATION,
        ],
    ];

    foreach ($accounts as $account) {
        $this->post(route('register.store'), [
            ...$account,
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user = User::where('email', $account['email'])->firstOrFail();
        expect($user->is_approved)->toBeFalse();

        $this->from(route('login'))->post(route('login.store'), [
            'role' => $account['role'],
            'email' => $account['email'],
            'password' => 'secure-pass-123',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee($account['email'])
            ->assertSee('Pending approval')
            ->assertSee('Approve account');
        $this->actingAs($admin)->patch(route('admin.users.approve', $user))
            ->assertRedirect();
        expect($user->fresh()->is_approved)->toBeTrue();

        auth()->logout();
        $this->post(route('login.store'), [
            'role' => $account['role'],
            'email' => $account['email'],
            'password' => 'secure-pass-123',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        auth()->logout();
    }
});

test('student registration only accepts a Kampala university', function () {
    Storage::fake('local');

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Aisha Namuli',
            'email' => 'aisha-outside@example.test',
            'role' => User::ROLE_STUDENT,
            'course' => 'Computer Science',
            'university' => 'Mbarara University',
            'phone' => '+256700000000',
            'campus' => 'Main Campus',
            'faculty' => 'Computing and Informatics',
            'year_of_study' => '3',
            'student_registration_number' => '24/U/1235',
            'academic_interests' => 'Software engineering and accessible technology.',
            'application_letter' => UploadedFile::fake()->create('application-letter.pdf', 100, 'application/pdf'),
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('university');

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('University lecturer')
        ->assertSee('Organisation')
        ->assertSee('Administrator')
        ->assertSee('University or institution')
        ->assertSee('Kampala International University');
});

test('role dashboards reject users with a different role', function () {
    $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $organisation = User::factory()->create(['role' => User::ROLE_ORGANISATION]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($student)->get(route('dashboard.student'))->assertOk();
    $this->actingAs($student)->get(route('dashboard.supervision'))->assertOk();
    $this->actingAs($student)->get(route('dashboard.university'))->assertForbidden();
    $this->actingAs($student)->get(route('dashboard.admin'))->assertForbidden();
    $this->actingAs($supervisor)->get(route('dashboard.university'))->assertOk();
    $this->actingAs($supervisor)->get(route('dashboard.supervision'))->assertForbidden();
    $this->actingAs($supervisor)->get(route('dashboard.student'))->assertForbidden();
    $this->actingAs($organisation)->get(route('dashboard.organisation'))->assertOk();
    $this->actingAs($organisation)->get(route('dashboard.admin'))->assertForbidden();
    $this->actingAs($admin)->get(route('dashboard.admin'))->assertOk();
    $this->actingAs($admin)->get(route('dashboard.student'))->assertForbidden();
    $this->actingAs($admin)->get(route('dashboard.university'))->assertForbidden();
});

test('dashboard dispatches to the account role', function () {
    $organisation = User::factory()->create(['role' => User::ROLE_ORGANISATION]);

    $this->actingAs($organisation)
        ->get(route('dashboard'))
        ->assertRedirect(route('dashboard.organisation'));

    $this->actingAs($organisation)
        ->get(route('dashboard.organisation'))
        ->assertOk()
        ->assertSee('Manage your Kampala placements');
});

test('accounts for each role can sign in with their own passwords', function () {
    foreach ([
        ['email' => 'student@internlink.test', 'role' => User::ROLE_STUDENT, 'password' => 'student-password'],
        ['email' => 'supervisor@internlink.test', 'role' => User::ROLE_SUPERVISOR, 'password' => 'lecturer-password'],
        ['email' => 'employer@internlink.test', 'role' => User::ROLE_ORGANISATION, 'password' => 'organisation-password'],
        ['email' => 'admin@internlink.test', 'role' => User::ROLE_ADMIN, 'password' => 'admin-password'],
    ] as $account) {
        $user = User::factory()->create([
            'email' => $account['email'],
            'role' => $account['role'],
            'password' => $account['password'],
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => $account['password'],
            'role' => $account['role'],
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        auth()->logout();
    }

    $supervisor = User::factory()->create([
        'email' => 'lecturer@example.test',
        'role' => User::ROLE_SUPERVISOR,
        'password' => 'lecturer-password',
    ]);

    $this->from(route('login'))->post(route('login.store'), [
        'email' => $supervisor->email,
        'password' => 'lecturer-password',
        'role' => User::ROLE_ORGANISATION,
    ])->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('the sign-in page offers a choice for each account type', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('University lecturer')
        ->assertSee('Organisation staff')
        ->assertSee('Student')
        ->assertSee('Administrator')
        ->assertSee('value="student" selected', false)
        ->assertDontSee('Choose account type')
        ->assertSee('Create account')
        ->assertDontSee('Create a student account')
        ->assertDontSee('data-demo-login');
});

test('administrator signup can only be selected when an invitation code is configured', function () {
    config(['internlink.admin_invitation_code' => null]);
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('disabled>Administrator', false);

    config(['internlink.admin_invitation_code' => 'private-admin-invite']);
    $this->get(route('register'))
        ->assertOk()
        ->assertDontSee('disabled>Administrator', false)
        ->assertSee('Administrator invitation code');
});
