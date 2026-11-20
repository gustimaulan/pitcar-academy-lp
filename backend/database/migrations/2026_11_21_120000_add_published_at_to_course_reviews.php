<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A review shows on the sales page only when the student allowed it
        // (may_publish) and the team chose it (published_at). Editing the
        // review clears published_at, so the team sees the new words first.
        Schema::table('course_reviews', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('may_publish');
        });
    }

    public function down(): void
    {
        Schema::table('course_reviews', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};
