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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('approval_status')->default('approved')->index();
            $table->foreignId('approval_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approval_decided_at')->nullable();
            $table->string('organisation_phone', 30)->nullable();
            $table->string('organisation_website')->nullable();
            $table->text('organisation_description')->nullable();
        });

        DB::table('users')
            ->where('is_approved', false)
            ->whereIn('role', ['supervisor', 'organisation'])
            ->update(['approval_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approval_decided_by');
            $table->dropIndex(['approval_status']);
            $table->dropColumn([
                'approval_status',
                'approval_decided_at',
                'organisation_phone',
                'organisation_website',
                'organisation_description',
            ]);
        });
    }
};
