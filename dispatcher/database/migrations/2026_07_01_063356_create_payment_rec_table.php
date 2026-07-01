<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_rec', function (Blueprint $table) {
            $table->id();
            //id() is of bigint type and is auto incrementing primary key
            $table->unsignedBigInteger('dispatch_id')->unique(); // foreign key to dispatches table
            $table->string('status');
            $table->string('customer_id');
            $table->timestamps();

            // foreign key constraint
            $table->foreign('dispatch_id')->references('id')->on('dispatches')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payment_rec');
    }
};
