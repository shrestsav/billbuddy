<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('paid_by')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->string('receipt_image')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->date('next_recurrence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'date']);
            $table->index(['paid_by', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
