<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InternshipController;
use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SupervisorController;
use App\Models\Internship;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $internships = Internship::query()
        ->open()
        ->where('location', config('internlink.city'))
        ->with('organisation')
        ->withCount(['applications as filled_slots' => fn ($query) => $query->whereIn('status', ['offered', 'placed'])])
        ->latest()
        ->take(3)
        ->get();

    return view('welcome-internlink', compact('internships'));
})->name('home');
Route::get('/internships', [InternshipController::class, 'index'])->name('internships.index');
Route::get('/internships/{internship}', [InternshipController::class, 'show'])->name('internships.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/student', [StudentController::class, 'dashboard'])
        ->middleware('role:student')
        ->name('dashboard.student');
    Route::get('/student/profile', [StudentController::class, 'showProfile'])
        ->middleware('role:student')
        ->name('student.profile');
    Route::patch('/student/profile', [StudentController::class, 'updateProfile'])
        ->middleware('role:student')
        ->name('student.profile.update');
    Route::get('/student/profile/application-letter', [StudentController::class, 'downloadApplicationLetter'])
        ->middleware('role:student')
        ->name('student.profile.application-letter');
    Route::get('/my-supervision', [StudentController::class, 'supervision'])
        ->middleware('role:student')
        ->name('dashboard.supervision');
    Route::get('/university', [SupervisorController::class, 'dashboard'])
        ->middleware('role:supervisor')
        ->name('dashboard.university');
    Route::get('/organisation', [OrganisationController::class, 'dashboard'])
        ->middleware('role:organisation')
        ->name('dashboard.organisation');
    Route::get('/admin', [AdminController::class, 'dashboard'])
        ->middleware('role:admin')
        ->name('dashboard.admin');

    Route::post('/internships/{internship}/applications', [InternshipController::class, 'apply'])
        ->middleware('role:student')->name('internships.apply');
    Route::post('/student/logbooks', [StudentController::class, 'submitLogbook'])
        ->middleware('role:student')->name('student.logbooks.store');
    Route::post('/student/messages', [StudentController::class, 'sendMessage'])
        ->middleware('role:student')->name('student.messages.store');
    Route::post('/student/visits', [StudentController::class, 'requestVisit'])
        ->middleware('role:student')->name('student.visits.store');

    Route::post('/university/messages', [SupervisorController::class, 'sendMessage'])
        ->middleware('role:supervisor')->name('supervisor.messages.store');
    Route::patch('/university/logbooks/{entry}', [SupervisorController::class, 'reviewLogbook'])
        ->middleware('role:supervisor')->name('supervisor.logbooks.review');
    Route::patch('/university/visits/{visit}', [SupervisorController::class, 'respondToVisit'])
        ->middleware('role:supervisor')->name('supervisor.visits.respond');

    Route::post('/organisation/internships', [OrganisationController::class, 'storeInternship'])
        ->middleware('role:organisation')->name('organisation.internships.store');
    Route::patch('/organisation/applications/{application}', [OrganisationController::class, 'updateApplication'])
        ->middleware('role:organisation')->name('organisation.applications.update');

    Route::patch('/admin/users/{user}/role', [AdminController::class, 'updateRole'])
        ->middleware('role:admin')->name('admin.users.role');
    Route::patch('/admin/users/{user}/approve', [AdminController::class, 'approveAccount'])
        ->middleware('role:admin')->name('admin.users.approve');
    Route::post('/admin/supervisor-assignments', [AdminController::class, 'assignSupervisor'])
        ->middleware('role:admin')->name('admin.supervisors.assign');
    Route::patch('/admin/internships/{internship}', [AdminController::class, 'moderateInternship'])
        ->middleware('role:admin')->name('admin.internships.moderate');
});
