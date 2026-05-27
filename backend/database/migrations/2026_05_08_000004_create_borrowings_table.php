<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->string('borrower_name');
            $table->string('contact_details');
            $table->date('borrow_date');
            $table->date('expected_return_date');
            $table->date('actual_return_date')->nullable();
            $table->integer('quantity_borrowed');
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};
