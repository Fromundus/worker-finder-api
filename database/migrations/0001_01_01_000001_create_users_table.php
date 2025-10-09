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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('contact_number')->nullable();
            $table->date('birth_day')->nullable();
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('worker');
            $table->string("status")->default("pending");

            $table->string('sex')->nullable();
            $table->string('religion')->nullable();
            $table->string('civil_status')->nullable();

            $table->string('height')->nullable();

            $table->boolean('has_disability')->default(false);
            $table->text('disabilities')->nullable();
            $table->string('disability_specify')->nullable();
            
            $table->text("skills")->nullable();
            $table->string('skill_specify')->nullable();

            $table->text("experience")->nullable();
            
            $table->decimal('average_rating', 3, 2)->default(0.00);
            
            $table->enum('employer_type', ['household', 'establishment'])->nullable();
            $table->string('business_name')->nullable();

            $table->string('lat')->nullable();
            $table->string('lng')->nullable();

            $table->unsignedBigInteger('location_id')->nullable();
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');

            // Common required images
            $table->string('barangay_clearance_photo')->nullable(); 
            $table->string('valid_id_photo')->nullable();
            $table->string('selfie_with_id_photo')->nullable();

            // Employer-specific docs
            $table->string('business_permit_photo')->nullable();
            $table->string('bir_certificate_photo')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        // Schema::create('users', function (Blueprint $table) {
        //     $table->id();

        //     // Basic info
        //     $table->string('first_name');
        //     $table->string('middle_name')->nullable();
        //     $table->string('last_name');
        //     $table->string('suffix')->nullable();

        //     // Contact & login info
        //     $table->string('contact_number')->nullable();
        //     $table->string('email')->unique()->nullable();
        //     $table->timestamp('email_verified_at')->nullable();
        //     $table->string('password');

        //     $table->date('birthday')->nullable();
        //     $table->enum('sex', ['male', 'female', 'other'])->nullable();
        //     $table->enum('civil_status', ['single', 'married', 'widowed'])->nullable();

        //     $table->decimal('height', 5, 2)->nullable(); // e.g., 165.50 cm

        //     // Disability fields
            // $table->boolean('has_disability')->default(false);
            // $table->text('disabilities')->nullable(); // store as array: ["visual", "hearing", "speech", "physical", "mental"]
            // $table->string('disability_specify')->nullable();

        //     // User classification
        //     $table->enum('role', ['worker', 'employer'])->default('worker');
        //     $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        //     $table->enum('employer_type', ['household', 'establishment'])->nullable();

        //     // Location
        //     $table->string('lat')->nullable();
        //     $table->string('lng')->nullable();
        //     $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

        //     // Business info (for employers)
        //     $table->string('business_name')->nullable();

        //     // Ratings (for workers)
        //     $table->text('skills');
        //     $table->text('experience');
        //     $table->decimal('average_rating', 3, 2)->default(0.00);

        //     // Common required images
        //     $table->string('barangay_clearance_photo')->nullable();
        //     $table->string('valid_id_photo')->nullable();
        //     $table->string('selfie_with_id_photo')->nullable();

        //     // Employer-specific docs
        //     $table->string('business_permit_photo')->nullable();
        //     $table->string('bir_certificate_photo')->nullable();

        //     $table->rememberToken();
        //     $table->timestamps();
        // });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
