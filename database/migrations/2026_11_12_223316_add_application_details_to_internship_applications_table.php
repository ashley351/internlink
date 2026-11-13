<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_applications', function (Blueprint $table): void {
            $table->string('application_letter_path')->nullable();
            $table->string('university')->nullable();
            $table->string('campus', 120)->nullable();
            $table->string('faculty', 160)->nullable();
            $table->string('course', 120)->nullable();
            $table->unsignedTinyInteger('year_of_study')->nullable();
            $table->string('student_registration_number', 100)->nullable();
            $table->text('academic_interests')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('internship_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'application_letter_path',
                'university',
                'campus',
                'faculty',
                'course',
                'year_of_study',
                'student_registration_number',
                'academic_interests',
            ]);
        });
    }
};
