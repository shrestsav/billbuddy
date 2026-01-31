<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('payer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payee_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method')->nullable();
            $table->timestamp('settled_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payer_id', 'payee_id']);
            $table->index(['group_id', 'settled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
