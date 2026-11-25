<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Who in the team opened a class for free. Empty for paid and imported access.
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('granted_by')->nullable()->after('order_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('granted_by');
        });
    }
};
