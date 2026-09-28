<?php

namespace Whilesmart\Playbooks;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Whilesmart\Agents\Facades\Agents;
use Whilesmart\Playbooks\Agents\Harnesses\PlaybookExtractorHarness;
use Whilesmart\Playbooks\Agents\Tools\PlaybookSaveTool;
use Whilesmart\Playbooks\Contracts\ExtractionBudget;
use Whilesmart\Playbooks\Contracts\ExtractionStatusStore;
use Whilesmart\Playbooks\Contracts\ResponseFormatter;
use Whilesmart\Playbooks\Contracts\SubjectDescriber;
use Whilesmart\Playbooks\Contracts\UsageRecorder;
use Whilesmart\Playbooks\Http\Controllers\PlaybookEntryController;
use Whilesmart\Playbooks\Http\Requests\ExtractPlaybookRequest;
use Whilesmart\Playbooks\Http\Requests\StorePlaybookEntryRequest;
use Whilesmart\Playbooks\Http\Requests\UpdatePlaybookEntryRequest;
use Whilesmart\Playbooks\Models\PlaybookEntry;
use Whilesmart\Playbooks\Support\Defaults\AttributeSubjectDescriber;
use Whilesmart\Playbooks\Support\Defaults\CacheExtractionStatusStore;
use Whilesmart\Playbooks\Support\Defaults\NullUsageRecorder;
use Whilesmart\Playbooks\Support\Defaults\UnlimitedExtractionBudget;
use Whilesmart\Playbooks\Support\Playbook;
use Whilesmart\Playbooks\Support\PlaybookSchema;

class PlaybooksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/playbooks.php', 'playbooks');

        $this->app->singleton(PlaybookSchema::class);
        $this->app->singleton(Playbook::class);

        $this->app->bindIf(ExtractionBudget::class, UnlimitedExtractionBudget::class);
        $this->app->bindIf(UsageRecorder::class, NullUsageRecorder::class);
        $this->app->bindIf(ExtractionStatusStore::class, CacheExtractionStatusStore::class);
        $this->app->bindIf(SubjectDescriber::class, AttributeSubjectDescriber::class);

        $this->app->bindIf(ResponseFormatter::class, function ($app) {
            return $app->make($this->configured('response_formatter', ResponseFormatter::class));
        });
    }

    public function boot(): void
    {
        if (config('playbooks.run_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $this->publishes([
            __DIR__.'/../config/playbooks.php' => config_path('playbooks.php'),
        ], 'playbooks-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'playbooks-migrations');

        if (config('playbooks.register_routes', true)) {
            $this->validateConfiguredClasses();

            $model = config('playbooks.models.entry');
            Route::bind('playbook_entry', fn (string $value) => $model::query()->findOrFail($value));

            Route::middleware(config('playbooks.route_middleware', ['api', 'auth:sanctum']))
                ->prefix(config('playbooks.route_prefix', 'api'))
                ->group(__DIR__.'/../routes/api.php');
        }

        $this->registerAgentTools();
    }

    /**
     * Offer the save tool and the extractor harness to eloquent-agents when it is installed.
     */
    protected function registerAgentTools(): void
    {
        if (! class_exists(Agents::class) || ! $this->app->bound('agents') || ! config('playbooks.extraction.register_agent', true)) {
            return;
        }

        Agents::registerTool(PlaybookSaveTool::class);
        Agents::registerHarness((string) config('playbooks.extraction.harness', 'playbook-extractor'), PlaybookExtractorHarness::class);
    }

    protected function validateConfiguredClasses(): void
    {
        $this->configured('models.entry', PlaybookEntry::class);
        $this->configured('requests.store', StorePlaybookEntryRequest::class);
        $this->configured('requests.update', UpdatePlaybookEntryRequest::class);
        $this->configured('requests.extract', ExtractPlaybookRequest::class);
        $this->configured('resources.entry', JsonResource::class);
        $this->configured('controller', PlaybookEntryController::class);
    }

    /**
     * The configured class for $key, which must be or extend $base.
     */
    protected function configured(string $key, string $base): string
    {
        $class = config("playbooks.{$key}");

        if (! is_string($class) || ! class_exists($class) || ! is_a($class, $base, true)) {
            throw new InvalidArgumentException("playbooks.{$key} must be a class that is or extends {$base}.");
        }

        return $class;
    }
}
