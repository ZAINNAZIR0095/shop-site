// database/migrations/xxxx_create_stocks_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('date');
            $table->text('description')->nullable();
            $table->decimal('net_price', 15, 2)->default(0);
            $table->enum('stock_type', ['sale', 'purchase', 'issue', 'return'])->default('purchase');

            // Store customer/supplier info directly (no foreign keys)
            $table->string('party_name')->nullable(); // Customer or Supplier name
            $table->string('party_phone')->nullable();
            $table->text('party_address')->nullable();

            $table->timestamps();

            // Foreign key only for user
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['date', 'stock_type']);
            $table->index('party_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stocks'); 
    }
};
