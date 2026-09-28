<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_playbook_entries', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->nullableMorphs('subject');
            $table->string('kind', 40);
            $table->string('status', 20)->default('confirmed');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_playbook_entries');
    }
};
