<?php

use Whilesmart\Playbooks\Http\Controllers\PlaybookEntryController;
use Whilesmart\Playbooks\Http\Requests\ExtractPlaybookRequest;
use Whilesmart\Playbooks\Http\Requests\StorePlaybookEntryRequest;
use Whilesmart\Playbooks\Http\Requests\UpdatePlaybookEntryRequest;
use Whilesmart\Playbooks\Http\Resources\PlaybookEntryResource;
use Whilesmart\Playbooks\Http\Responses\DefaultResponseFormatter;
use Whilesmart\Playbooks\Models\PlaybookEntry;

return [
    'register_routes' => env('PLAYBOOKS_REGISTER_ROUTES', true),
    'route_prefix' => env('PLAYBOOKS_ROUTE_PREFIX', 'api'),
    'route_middleware' => ['api', 'auth:sanctum'],

    // Extra middleware on routes that change data (store, update, destroy, accept, extract).
    'write_middleware' => [],

    // Route groups the package registers. Turn one off to keep its routes out.
    'route_groups' => [
        'entries' => true,
        'extraction' => true,
    ],

    'playbooks_table' => env('PLAYBOOKS_TABLE', 'playbook_entries'),

    // Hosts that already own the table (for example after adopting this package) turn this off.
    'run_migrations' => env('PLAYBOOKS_RUN_MIGRATIONS', true),

    'models' => [
        'entry' => PlaybookEntry::class,
    ],

    'requests' => [
        'store' => StorePlaybookEntryRequest::class,
        'update' => UpdatePlaybookEntryRequest::class,
        'extract' => ExtractPlaybookRequest::class,
    ],

    'resources' => [
        'entry' => PlaybookEntryResource::class,
    ],

    'response_formatter' => DefaultResponseFormatter::class,

    'controller' => PlaybookEntryController::class,

    // Kinds a subject holds at most one confirmed entry of, and kinds it holds many of.
    'kinds' => [
        'single' => ['icp', 'offer', 'positioning', 'qualification'],
        'many' => ['persona', 'competitor', 'objection'],
    ],

    // The structured metadata fields each kind keeps. Anything else is dropped on save.
    'fields' => [
        'icp' => ['industry', 'size_band', 'revenue_band', 'geography', 'technographics', 'trigger_signals', 'disqualifiers'],
        'persona' => ['role', 'seniority', 'department', 'goals', 'kpis', 'pains', 'triggers', 'objections', 'watering_holes', 'comms_preference'],
        'offer' => ['dream_outcome', 'perceived_likelihood', 'time_delay', 'effort', 'guarantees', 'bonuses'],
        'positioning' => ['competitive_alternatives', 'unique_attributes', 'value', 'target_market', 'market_category'],
        'qualification' => ['framework', 'weights', 'notes'],
        'competitor' => ['name', 'category', 'positioning', 'where_we_win', 'where_we_lose', 'landmines'],
        'objection' => ['objection', 'category', 'response', 'framework'],
    ],

    // The line that introduces a confirmed playbook when it is folded into an agent's input.
    'prompt_intro' => 'Playbook, treat these as the source of truth for who to reach and why:',

    'qualification_frameworks' => [
        'bant' => ['budget', 'authority', 'need', 'timeline'],
        'meddic' => ['metrics', 'economic_buyer', 'decision_criteria', 'decision_process', 'identify_pain', 'champion'],
        'meddpicc' => ['metrics', 'economic_buyer', 'decision_criteria', 'decision_process', 'identify_pain', 'champion', 'paper_process', 'competition'],
        'spin' => ['situation', 'problem', 'implication', 'need_payoff'],
        'champ' => ['challenges', 'authority', 'money', 'prioritisation'],
        'gpct' => ['goals', 'plans', 'challenges', 'timeline'],
    ],

    // Drafting entries from web pages. Needs whilesmart/eloquent-agents.
    'extraction' => [
        'register_agent' => env('PLAYBOOKS_REGISTER_AGENT', true),
        'harness' => 'playbook-extractor',

        // The host provides page.read (and any search tool); the package provides playbook.save.
        'tools' => ['page.read', 'playbook.save'],
        'status_ttl_minutes' => (int) env('PLAYBOOKS_EXTRACTION_STATUS_TTL', 360),
    ],
];
