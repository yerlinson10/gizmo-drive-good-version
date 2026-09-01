<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->morphs('shareable');
            $table->foreignId('shared_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with')->constrained('users')->cascadeOnDelete();
            $table->string('permission', 20);
            $table->timestamps();

            $table->unique(['shareable_type', 'shareable_id', 'shared_with']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
