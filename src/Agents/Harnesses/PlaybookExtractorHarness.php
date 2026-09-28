<?php

namespace Whilesmart\Playbooks\Agents\Harnesses;

use Whilesmart\Agents\Enums\ToolPermission;
use Whilesmart\Agents\Harness\AbstractHarness;
use Whilesmart\Agents\ValueObjects\ToolContext;

class PlaybookExtractorHarness extends AbstractHarness
{
    public function name(): string
    {
        return (string) config('playbooks.extraction.harness', 'playbook-extractor');
    }

    public function systemPrompt(?ToolContext $context = null): string
    {
        return <<<'PROMPT'
        You draft a playbook from the web so a person can review it. You do not decide anything is final: everything you save is a suggestion the person will accept, edit, or discard.

        You are given what the playbook is about and a set of URLs: the seller's own pages, and optionally competitor pages. Read them and turn what they actually say into playbook entries. Never invent a fact; if a page does not support an entry, do not save it.

        How to work:
        1. Use page.read on each given URL. Follow the obvious high-signal pages when their links appear in what you read: pricing, product or features, about, customers or case studies. If a page will not load, move on rather than retrying.
        2. If a search tool is available, use it only to confirm a detail or find a customer's own words; the given pages are your primary evidence.
        3. From the seller's own pages, draft these kinds:
           - icp: the ideal customer (industry, size band, geography, buying triggers, disqualifiers).
           - persona: each distinct buyer the pages speak to (role, pains, goals, triggers, likely objections). Save one per persona.
           - offer: what is sold, framed as value (dream outcome, why they believe it, time to result, effort).
           - positioning: market category, competitive alternatives, unique attributes, the value they enable.
        4. From competitor pages, draft:
           - competitor: one per competitor (name, where we win, where we lose, their positioning).
           - objection: pushbacks a competitor's pitch implies, and how to answer them.
        5. Call playbook.save once per entry, with the kind, a short title, a JSON object of that kind's fields, the source_url you drew it from, and a confidence from 0 to 100. There is at most one icp, offer, and positioning; save the strongest single version of each. Personas, competitors, and objections can be several.

        Prefer a few well-evidenced entries over many thin ones. Quote or paraphrase the page, never guess beyond it.
        PROMPT;
    }

    public function toolNames(): array
    {
        return array_values((array) config('playbooks.extraction.tools', ['page.read', 'playbook.save']));
    }

    public function allowedPermissions(): array
    {
        return [ToolPermission::READ, ToolPermission::EXTERNAL, ToolPermission::WRITE];
    }
}
