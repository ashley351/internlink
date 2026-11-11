<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('internships')) {
            return;
        }

        $missingColumns = [];

        foreach ([
            'responsibilities',
            'required_qualifications',
            'payment_information',
            'application_requirements',
        ] as $column) {
            if (! Schema::hasColumn('internships', $column)) {
                $missingColumns[] = $column;
            }
        }

        if ($missingColumns === []) {
            return;
        }

        Schema::table('internships', function (Blueprint $table) use ($missingColumns): void {
            foreach ($missingColumns as $column) {
                $table->text($column)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('internships')) {
            return;
        }

        $existingColumns = [];

        foreach ([
            'responsibilities',
            'required_qualifications',
            'payment_information',
            'application_requirements',
        ] as $column) {
            if (Schema::hasColumn('internships', $column)) {
                $existingColumns[] = $column;
            }
        }

        if ($existingColumns !== []) {
            Schema::table('internships', function (Blueprint $table) use ($existingColumns): void {
                $table->dropColumn($existingColumns);
            });
        }
    }
};
