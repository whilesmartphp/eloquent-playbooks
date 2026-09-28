<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Product;
use Tests\TestCase;
use Whilesmart\Playbooks\Models\PlaybookEntry;
use Whilesmart\Playbooks\Support\Playbook;

class PlaybookGroundingTest extends TestCase
{
    private function owner(): Model
    {
        return new class extends Model
        {
            protected $attributes = ['id' => 1];

            public function getMorphClass(): string
            {
                return 'App\\Models\\Workspace';
            }
        };
    }

    #[Test]
    public function the_prompt_covers_confirmed_entries_only_in_reading_order(): void
    {
        $product = Product::create(['name' => 'SourceAnt']);
        $base = ['owner_type' => self::OWNER, 'owner_id' => 1, 'subject_type' => Product::class, 'subject_id' => $product->id];
        PlaybookEntry::create($base + ['type' => 'persona', 'status' => 'confirmed', 'title' => 'Staff engineer', 'metadata' => ['pains' => ['review load']]]);
        PlaybookEntry::create($base + ['type' => 'icp', 'status' => 'confirmed', 'title' => 'Mid-market', 'metadata' => ['industry' => 'SaaS', '_confidence' => 90]]);
        PlaybookEntry::create($base + ['type' => 'offer', 'status' => 'suggested', 'title' => 'Unreviewed', 'metadata' => []]);

        $prompt = $this->app->make(Playbook::class)->prompt($this->owner(), $product);

        $this->assertStringContainsString("Ideal customer profile:\n- Mid-market\n  Industry: SaaS", $prompt);
        $this->assertLessThan(strpos($prompt, 'Target buyer personas'), strpos($prompt, 'Ideal customer profile'));
        $this->assertStringNotContainsString('Unreviewed', $prompt);
        $this->assertStringNotContainsString('90', $prompt);
    }

    #[Test]
    public function the_multi_subject_prompt_heads_each_block_with_the_subject_label(): void
    {
        $a = Product::create(['name' => 'SourceAnt']);
        $b = Product::create(['name' => 'Pagebeam']);
        PlaybookEntry::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'subject_type' => Product::class, 'subject_id' => $a->id, 'type' => 'icp', 'status' => 'confirmed', 'title' => 'Dev teams']);

        $prompt = $this->app->make(Playbook::class)->promptForSubjects($this->owner(), [$a, $b]);

        $this->assertStringContainsString('For "SourceAnt":', $prompt);
        $this->assertStringNotContainsString('Pagebeam', $prompt);
    }
}
