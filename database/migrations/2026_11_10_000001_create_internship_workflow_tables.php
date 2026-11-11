<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('field');
            $table->text('description');
            $table->string('location')->default('Kampala');
            $table->json('eligible_courses')->nullable();
            $table->unsignedSmallInteger('slots_total');
            $table->unsignedSmallInteger('duration_weeks');
            $table->date('closes_at');
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('internship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('cover_letter');
            $table->string('status')->default('submitted')->index();
            $table->timestamp('interview_at')->nullable();
            $table->timestamps();
            $table->unique(['internship_id', 'student_id']);
        });

        Schema::create('supervisor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete()->index();
            $table->timestamps();
        });

        Schema::create('placement_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['student_id', 'supervisor_id', 'created_at']);
        });

        Schema::create('visit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('requested_for');
            $table->string('location')->default('Kampala');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('logbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('week_number');
            $table->text('work_summary');
            $table->text('learnings');
            $table->unsignedSmallInteger('hours_worked')->default(0);
            $table->string('status')->default('pending')->index();
            $table->text('supervisor_feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'week_number']);
        });

        Schema::create('activity_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_audits');
        Schema::dropIfExists('logbook_entries');
        Schema::dropIfExists('visit_requests');
        Schema::dropIfExists('placement_messages');
        Schema::dropIfExists('supervisor_assignments');
        Schema::dropIfExists('internship_applications');
        Schema::dropIfExists('internships');
    }
};
