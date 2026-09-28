<?php

namespace Tests\Support;

use Whilesmart\Agents\Contracts\AgentEngine;
use Whilesmart\Agents\ValueObjects\AgentRequest;
use Whilesmart\Agents\ValueObjects\AgentResult;

/** Drives the real playbook.save tool the way a model would. */
class FakePlaybookEngine implements AgentEngine
{
    public ?AgentRequest $request = null;

    public function run(AgentRequest $request): AgentResult
    {
        $this->request = $request;

        foreach ($request->tools as $tool) {
            if ($tool->name() !== 'playbook.save') {
                continue;
            }

            $tool->handle([
                'kind' => 'icp',
                'title' => 'Mid-market SaaS',
                'fields' => json_encode(['industry' => 'SaaS platforms', 'size_band' => '50-500']),
                'source_url' => 'https://acme.example/product',
                'confidence' => 80,
            ], $request->context);

            $tool->handle([
                'kind' => 'persona',
                'title' => 'Head of DevEx',
                'fields' => json_encode(['role' => 'Head of Developer Experience', 'pains' => ['tooling sprawl']]),
                'source_url' => 'https://acme.example/customers',
                'confidence' => 170,
            ], $request->context);
        }

        return AgentResult::success('Drafted 2 entries.', 2, [], ['prompt_tokens' => 20, 'completion_tokens' => 10]);
    }
}
