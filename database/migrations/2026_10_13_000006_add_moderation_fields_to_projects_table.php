<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('rejection_reason');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->string('ai_review_verdict', 30)->nullable()->after('reviewed_at');
            $table->text('ai_review_report')->nullable()->after('ai_review_verdict');

            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['reviewed_by']);
            }
            $table->dropColumn([
                'rejection_reason',
                'reviewed_by',
                'reviewed_at',
                'ai_review_verdict',
                'ai_review_report',
            ]);
        });
    }
};
