<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('quantity')->default(0);
            $table->string('serial_number')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('place_id')->constrained('places')->onDelete('cascade');
            $table->enum('status', ['in_store', 'borrowed', 'damaged', 'missing'])->default('in_store');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
