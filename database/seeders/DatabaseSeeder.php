<?php

namespace Database\Seeders;

use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\LogbookEntry;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor@internlink.test'],
            ['name' => 'Grace Nanyonga', 'password' => Hash::make('password'), 'role' => User::ROLE_SUPERVISOR, 'university' => 'Makerere University'],
        );
        $organisation = User::updateOrCreate(
            ['email' => 'employer@internlink.test'],
            ['name' => 'BrightLabs Kampala', 'password' => Hash::make('password'), 'role' => User::ROLE_ORGANISATION],
        );
        User::updateOrCreate(
            ['email' => 'admin@internlink.test'],
            ['name' => 'InternLink Admin', 'password' => Hash::make('password'), 'role' => User::ROLE_ADMIN],
        );

        $student = User::updateOrCreate(
            ['email' => 'student@internlink.test'],
            ['name' => 'Aisha Namuli', 'password' => Hash::make('password'), 'role' => User::ROLE_STUDENT, 'course' => 'Computer Science', 'university' => 'Makerere University'],
        );

        SupervisorAssignment::updateOrCreate(
            ['student_id' => $student->id],
            ['supervisor_id' => $supervisor->id],
        );

        $roles = [
            ['title' => 'Product Design Intern', 'field' => 'Technology', 'description' => 'Work with a Kampala product team to research user needs, prototype interfaces and test improvements with customers.', 'slots_total' => 5, 'duration_weeks' => 12, 'eligible_courses' => ['Computer Science', 'Information Technology']],
            ['title' => 'Finance Operations Intern', 'field' => 'Finance', 'description' => 'Support reconciliations, reporting and process improvements alongside an experienced Kampala finance team.', 'slots_total' => 6, 'duration_weeks' => 16, 'eligible_courses' => ['Business Administration']],
            ['title' => 'Software Engineering Intern', 'field' => 'Technology', 'description' => 'Contribute to tested application features, learn team delivery practices and work with a Kampala engineering mentor.', 'slots_total' => 4, 'duration_weeks' => 12, 'eligible_courses' => ['Computer Science', 'Information Technology', 'Engineering']],
        ];

        foreach ($roles as $role) {
            Internship::updateOrCreate(
                ['organisation_id' => $organisation->id, 'title' => $role['title']],
                [...$role, 'location' => 'Kampala', 'closes_at' => today()->addDays(30), 'status' => 'open'],
            );
        }

        $placedInternship = Internship::where('organisation_id', $organisation->id)->where('title', 'Software Engineering Intern')->firstOrFail();
        InternshipApplication::updateOrCreate(
            ['internship_id' => $placedInternship->id, 'student_id' => $student->id],
            ['cover_letter' => 'I am excited to build practical software skills with a Kampala product team.', 'status' => 'placed'],
        );
        LogbookEntry::updateOrCreate(
            ['student_id' => $student->id, 'week_number' => 1],
            ['work_summary' => 'Completed onboarding and delivered an initial user interface improvement.', 'learnings' => 'Practised code review, testing and communication with the product team.', 'hours_worked' => 35, 'status' => 'approved', 'supervisor_feedback' => 'A good start. Continue documenting your work each week.', 'reviewed_by' => $supervisor->id, 'reviewed_at' => now()],
        );
    }
}
