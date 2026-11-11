<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the landing page introduces the internship platform', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Placement season at a glance')
        ->assertSee('Open internships')
        ->assertSee('University supervisors');
});

test('a student can register but cannot choose a privileged role', function () {
    $this->post(route('register.store'), [
        'name' => 'Aisha Namuli',
        'email' => 'aisha@example.test',
        'course' => 'Computer Science',
        'university' => 'Makerere University',
        'password' => 'secure-pass-123',
        'password_confirmation' => 'secure-pass-123',
        'role' => User::ROLE_ADMIN,
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    expect(auth()->user()->role)->toBe(User::ROLE_STUDENT);
});

test('student registration only accepts a Kampala university', function () {
    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Aisha Namuli',
            'email' => 'aisha-outside@example.test',
            'course' => 'Computer Science',
            'university' => 'Mbarara University',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('university');

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('University in Kampala')
        ->assertSee('Kampala International University');
});

test('role dashboards reject users with a different role', function () {
    $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

    $this->actingAs($student)->get(route('dashboard.student'))->assertOk();
    $this->actingAs($student)->get(route('dashboard.supervision'))->assertOk();
    $this->actingAs($student)->get(route('dashboard.university'))->assertForbidden();
    $this->actingAs($supervisor)->get(route('dashboard.university'))->assertOk();
    $this->actingAs($supervisor)->get(route('dashboard.supervision'))->assertForbidden();
    $this->actingAs($supervisor)->get(route('dashboard.student'))->assertForbidden();
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

test('development demo accounts can sign in for each role', function () {
    foreach ([
        ['email' => 'student@internlink.test', 'role' => User::ROLE_STUDENT],
        ['email' => 'supervisor@internlink.test', 'role' => User::ROLE_SUPERVISOR],
        ['email' => 'employer@internlink.test', 'role' => User::ROLE_ORGANISATION],
        ['email' => 'admin@internlink.test', 'role' => User::ROLE_ADMIN],
    ] as $account) {
        $user = User::factory()->create([
            'email' => $account['email'],
            'role' => $account['role'],
            'password' => bcrypt('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        auth()->logout();
    }
});
