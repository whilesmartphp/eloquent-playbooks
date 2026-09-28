<?php

namespace Whilesmart\Playbooks\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Playbooks\Enums\PlaybookEntryStatus;
use Whilesmart\Playbooks\Models\PlaybookEntry;

class PlaybookEntryFactory extends Factory
{
    protected $model = PlaybookEntry::class;

    public function definition(): array
    {
        return [
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => 1,
            'type' => 'persona',
            'status' => PlaybookEntryStatus::Confirmed->value,
            'title' => $this->faker->jobTitle(),
            'metadata' => ['role' => $this->faker->jobTitle()],
        ];
    }

    public function suggested(): static
    {
        return $this->state(fn () => ['status' => PlaybookEntryStatus::Suggested->value]);
    }
}
