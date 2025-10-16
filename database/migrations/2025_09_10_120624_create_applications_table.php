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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'forinterview', 'accepted', 'active', 'rejected','withdrawn', 'completed'])->default('pending');
            $table->text('message')->nullable();
            $table->unique(['job_post_id','user_id']);
            $table->date("workerIsRated")->nullable();
            $table->date("employerIsRated")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
