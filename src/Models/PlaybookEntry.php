<?php

namespace Whilesmart\Playbooks\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Whilesmart\Playbooks\Database\Factories\PlaybookEntryFactory;
use Whilesmart\Playbooks\Enums\PlaybookEntryStatus;

/** One playbook entry; `owner` is the tenant, `subject` what the playbook is about. */
class PlaybookEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => PlaybookEntryStatus::class,
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('playbooks.playbooks_table', 'playbook_entries');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', PlaybookEntryStatus::Confirmed->value);
    }

    public function scopeSuggested(Builder $query): Builder
    {
        return $query->where('status', PlaybookEntryStatus::Suggested->value);
    }

    public function scopeForOwner(Builder $query, string $ownerType, mixed $ownerId): Builder
    {
        return $query->where('owner_type', $ownerType)->where('owner_id', $ownerId);
    }

    public function scopeForSubject(Builder $query, ?string $subjectType, mixed $subjectId): Builder
    {
        if ($subjectType === null) {
            return $query->whereNull('subject_type')->whereNull('subject_id');
        }

        return $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }

    protected static function newFactory(): PlaybookEntryFactory
    {
        return PlaybookEntryFactory::new();
    }
}
