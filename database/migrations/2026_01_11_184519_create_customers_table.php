<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->string('cnic')->nullable();
                $table->text('notes')->nullable();
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->decimal('current_balance', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['name', 'phone']);
            });
        }

        if (!Schema::hasTable('customer_transactions')) {
            Schema::create('customer_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->string('type'); // sale, payment, opening_balance, adjustment
                $table->string('reference_no')->nullable();
                $table->string('description')->nullable();
                $table->decimal('debit', 15, 2)->default(0); // Amount customer owes (sales)
                $table->decimal('credit', 15, 2)->default(0); // Amount customer pays
                $table->decimal('balance', 15, 2)->default(0);
                $table->foreignId('stock_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();

                $table->index(['customer_id', 'date']);
                $table->index(['reference_no']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
        Schema::dropIfExists('customers');
    }
};
