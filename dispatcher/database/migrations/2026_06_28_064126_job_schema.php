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
        // this will create the jobs table
        Schema::create('dispatches', function(Blueprint $table) {
            $table->id();// primary key

            $table->string('type'); // job type--> payment or inventory
            $table->jsonb('payload'); // job payload(data) --> pyement(amount & others) or inventory(product_id, quantity)
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0); // number of attempts
            $table->string('idempotency_key')->unidque(); // thsi just make sure that a job is not processed twice
            $table->string('claimed_by')->nullable(); // which worker claimed the jib

            $table->timestamp('claimed_at')->nullable();
            // this sets to the timezone of DB not UTC
            $table->timestamp('available_at')->useCurrent(); // when the job is available-->current time
            $table->timestamp('failed_at')->nullable();

            $table->timestamps(); // in laravel this auto adds created_at and updated_at
            // also it used UTC timezone
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // down is exact reverse of up
        // up runs when migration is applied, down runs when migration is reversed
        // so if create in up, delete in down
        Schema::dropIfExists('dispatches');
    }
};
