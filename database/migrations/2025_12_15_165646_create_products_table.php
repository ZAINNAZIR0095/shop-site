// database/migrations/xxxx_create_products_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['physical', 'digital', 'service'])->default('physical');
            $table->string('unit')->nullable();
            $table->string('size')->nullable();
            $table->integer('min_limit')->default(0);
            $table->decimal('sale_price', 10, 2);
            $table->decimal('purchase_price', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
