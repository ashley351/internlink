<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('normalized_name')->unique();
                $table->string('district', 80)->default('Kampala');
                $table->string('accreditation_status', 180);
                $table->string('source_url', 500);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        $now = now();
        $institutions = [
            ['Aga Khan University', 'Provisional License to Operate as University'],
            ['Cavendish University Uganda', 'Provisional License to Operate as University'],
            ['Clarke International University', 'Chartered University'],
            ['Cornerstone University', 'Provisional License to Operate as University'],
            ['Ernest Cook University', 'Provisional License to Operate as University'],
            ['International Business, Science and Technology (ISBAT)', 'Chartered University'],
            ['International University of East Africa', 'Chartered University'],
            ['Islamic Call University College', 'Provisional License to Operate as University'],
            ['Islamic University in Uganda, Kampala Campus', 'Active Kampala campus (NCHE campus record)'],
            ['Jeph International University', 'Provisional License to Operate as University'],
            ['Kampala International University', 'Chartered University'],
            ['Kampala University', 'Chartered University'],
            ['King Ceasor University', 'Chartered University'],
            ['Kyambogo University', 'Public University'],
            ['Makerere University', 'Public University'],
            ['Makerere University Business School', 'Public OTI'],
            ['Nexus International University', 'Provisional License to Operate as University'],
            ['St. Lawrence University', 'Provisional License to Operate as University'],
            ['Team University', 'Provisional License to Operate as University'],
            ['Uganda Technology and Management University', 'Provisional License to Operate as University'],
            ['University of World Mission Frontiers', 'Provisional License to Operate as University'],
            ['Victoria University', 'Chartered University'],
        ];

        DB::table('institutions')->insertOrIgnore(array_map(fn (array $institution): array => [
            'name' => $institution[0],
            'normalized_name' => mb_strtolower($institution[0]),
            'district' => 'Kampala',
            'accreditation_status' => $institution[1],
            'source_url' => 'https://unche.or.ug/universities/',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $institutions));

        $this->ensureStudentProfilesSchema();

        if (Schema::hasTable('student_profiles') && Schema::hasColumn('student_profiles', 'institution_id')) {
            foreach (DB::table('institutions')->get(['id', 'name']) as $institution) {
                DB::table('student_profiles')
                    ->join('users', 'users.id', '=', 'student_profiles.user_id')
                    ->where('users.university', $institution->name)
                    ->whereNull('student_profiles.institution_id')
                    ->update(['student_profiles.institution_id' => $institution->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('student_profiles') && Schema::hasColumn('student_profiles', 'institution_id')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('institution_id');
            });
        }

        Schema::dropIfExists('institutions');
    }

    private function ensureStudentProfilesSchema(): void
    {
        if (! Schema::hasTable('student_profiles')) {
            Schema::create('student_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
                $table->string('phone', 30)->nullable();
                $table->string('campus', 120)->nullable();
                $table->string('faculty', 160)->nullable();
                $table->unsignedTinyInteger('year_of_study')->nullable();
                $table->string('student_registration_number', 100)->nullable()->unique();
                $table->text('academic_interests')->nullable();
                $table->string('application_letter_path')->nullable();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('student_profiles', 'user_id')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('student_profiles', 'institution_id')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('student_profiles', 'phone')) {
            Schema::table('student_profiles', fn (Blueprint $table) => $table->string('phone', 30)->nullable());
        }

        if (! Schema::hasColumn('student_profiles', 'campus')) {
            Schema::table('student_profiles', fn (Blueprint $table) => $table->string('campus', 120)->nullable());
        }

        if (! Schema::hasColumn('student_profiles', 'faculty')) {
            Schema::table('student_profiles', fn (Blueprint $table) => $table->string('faculty', 160)->nullable());
        }

        if (! Schema::hasColumn('student_profiles', 'year_of_study')) {
            Schema::table('student_profiles', fn (Blueprint $table) => $table->unsignedTinyInteger('year_of_study')->nullable());
        }

        if (! Schema::hasColumn('student_profiles', 'student_registration_number')) {
            Schema::table('student_profiles', fn (Blueprint $table) => $table->string('student_registration_number', 100)->nullable()->unique());
        }

        if (! Schema::hasColumn('student_profiles', 'academic_interests')) {
            Schema::table('student_profiles', fn (Blueprint $table) => $table->text('academic_interests')->nullable());
        }

        if (! Schema::hasColumn('student_profiles', 'application_letter_path')) {
            Schema::table('student_profiles', fn (Blueprint $table) => $table->string('application_letter_path')->nullable());
        }

        if (! Schema::hasColumn('student_profiles', 'created_at')) {
            Schema::table('student_profiles', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        }

        if (! Schema::hasColumn('student_profiles', 'updated_at')) {
            Schema::table('student_profiles', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
        }
    }
};
