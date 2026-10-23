<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->default('');
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('note')->default('');
            $t->string('status')->default('active'); // active | soon | hidden
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->text('description')->default('');
            $t->json('prices')->nullable(); // [{"label":"16oz","cents":12000}], cents null = not set
            $t->string('photo')->default('');
            $t->boolean('sold_out')->default(false);
            $t->boolean('hidden')->default(false);
            $t->boolean('featured')->default(false);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->string('what');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['activities', 'items', 'categories', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
