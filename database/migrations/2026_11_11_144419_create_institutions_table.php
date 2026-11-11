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

        DB::table('institutions')->insert(array_map(fn (array $institution): array => [
            'name' => $institution[0],
            'normalized_name' => mb_strtolower($institution[0]),
            'district' => 'Kampala',
            'accreditation_status' => $institution[1],
            'source_url' => 'https://unche.or.ug/universities/',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $institutions));

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->foreignId('institution_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        foreach (DB::table('institutions')->get(['id', 'name']) as $institution) {
            DB::table('student_profiles')
                ->join('users', 'users.id', '=', 'student_profiles.user_id')
                ->where('users.university', $institution->name)
                ->update(['student_profiles.institution_id' => $institution->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('institution_id');
        });

        Schema::dropIfExists('institutions');
    }
};
