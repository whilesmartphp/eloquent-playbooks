<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('playbooks.playbooks_table', 'playbook_entries'), function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->nullableMorphs('subject');
            $table->string('type', 40);
            $table->string('status', 20)->default('confirmed');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_type', 'owner_id', 'subject_type', 'subject_id', 'type'], 'playbook_entries_scope_index');
            $table->index(['owner_type', 'owner_id', 'status'], 'playbook_entries_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('playbooks.playbooks_table', 'playbook_entries'));
    }
};
