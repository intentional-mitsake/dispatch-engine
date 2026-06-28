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
        Schema::create('jobs', function(Blueprint $table) {
            $table->id();// primary key
            $table->string('type'); // job type--> payment or inventory
            $table->jsonb('payload'); // job payload(data) --> pyement(amount & others) or inventory(product_id, quantity)
            $table->string('status')->default('pending');
            $table->unsingnedInteger('attempts')->default(0); // number of attempts
            $table->string('idempotency_key')->unidque(); // thsi just make sure that a job is not processed twice
            $table->string('claimed_by')->nullable(); // which worker claimed the jib
            $table->timestamps('claimed_at')->nullable();
            $table->timestamps('available_at')->useCurrent(); // when the job is available-->current time
            $table->timestamps('failed_at')->nullable();
            $table->timestamps(); // in laravel this auto adds created_at and updated_at
        })
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // down is exact reverse of up
        // up runs when migration is applied, down runs when migration is reversed
        // so if create in up, delete in down
        Schema::dropIfExists('jobs');
    }
};
