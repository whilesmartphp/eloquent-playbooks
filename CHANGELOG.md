## [1.0.0] - 2026-09-28
- Polymorphic playbook entries (ICP, persona, offer, positioning, qualification, competitor, objection), scoped per owner via owner-access and optionally per subject
- Kinds and their fields configured in `config/playbooks.php`; unknown fields are dropped on save
- Single kinds keep one confirmed entry per subject; confirming one retires the others
- Suggested entries wait for a person to accept them, and only confirmed entries ground a prompt
- Grounding prompts for one subject or several
- Optional extraction from web pages with whilesmart/eloquent-agents: the playbook.save tool, the playbook-extractor harness and a queued job
- Contracts for the extraction budget, usage recording, status storage and subject naming, with neutral defaults
- Configurable model, requests, resource, response formatter, controller, route groups and write middleware
- Domain events after saves, confirmations, deletions and finished extractions
