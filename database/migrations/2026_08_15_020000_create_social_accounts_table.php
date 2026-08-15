<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform_name')->index();
            $table->string('handle');
            $table->string('profile_url')->nullable();
            $table->string('account_type')->nullable();
            $table->text('notes')->nullable();
            $table->json('credentials')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'platform_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
