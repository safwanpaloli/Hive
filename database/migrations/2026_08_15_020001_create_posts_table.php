<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('script_body')->nullable();
            $table->json('media_links')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->enum('status', ['draft', 'scheduled', 'posted', 'skipped'])
                ->default('draft')
                ->index();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scheduled_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
