# whilesmart/eloquent-playbooks

Sales playbooks for Laravel: who a product sells to (ICP), who it talks to (personas), what it offers, how it is positioned, how it qualifies, and the competitors and objections it expects. Each piece is a `PlaybookEntry` owned by a tenant and, optionally, about a subject such as a product.

Entries a person writes are confirmed. Entries drafted by the optional extractor are suggestions until someone accepts them. Only confirmed entries ground an agent's prompt.

## Install

```bash
composer require whilesmart/eloquent-playbooks
php artisan migrate
```

Bind `Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer` to decide who may reach an owner's playbooks.

Add `HasPlaybookEntries` to owner models and `HasPlaybook` to subject models.

## API

All routes use `route_prefix` (default `api`) and `route_middleware` (default `api`, `auth:sanctum`). Responses use `{ "success": true, "data": ... }`.

| Method | Path | Does |
| --- | --- | --- |
| GET | `/playbook-entries` | Paginated list; filter by `owner_type`/`owner_id`, `subject_type`/`subject_id`, `kind`, `status` |
| POST | `/playbook-entries` | Create a confirmed entry |
| GET | `/playbook-entries/{id}` | Show one entry |
| PUT/PATCH | `/playbook-entries/{id}` | Update title, body or metadata; omitted fields keep their values |
| DELETE | `/playbook-entries/{id}` | Soft delete |
| POST | `/playbook-entries/{id}/accept` | Confirm a suggestion |
| POST | `/playbook-entries/extract` | Queue an extraction from `own_urls` and `competitor_urls` (202) |
| GET | `/playbook-entries/extract/status` | State of the latest extraction for an owner and subject |

## Grounding a prompt

```php
use Whilesmart\Playbooks\Support\Playbook;

$fragment = app(Playbook::class)->prompt($workspace, $product);
$fragment = app(Playbook::class)->promptForSubjects($workspace, $products);
```

## Extraction

Install `whilesmart/eloquent-agents`. The package registers the `playbook.save` tool and the `playbook-extractor` harness. The host registers a `page.read` tool (and any search tool) and lists the tools the harness may use in `playbooks.extraction.tools`.

Bind these contracts to plug in host behaviour. Each has a neutral default:

| Contract | Default |
| --- | --- |
| `ExtractionBudget` | `UnlimitedExtractionBudget` |
| `UsageRecorder` | `NullUsageRecorder` |
| `ExtractionStatusStore` | `CacheExtractionStatusStore` |
| `SubjectDescriber` | `AttributeSubjectDescriber` (reads `name` or `title` and `description`) |

## Customising

Everything is in `config/playbooks.php`:

- `models.entry`, `requests.*`, `resources.entry`, `response_formatter`, `controller`: replace a class with a subclass or an implementation of its contract. A wrong type fails at boot with a clear error.
- `route_groups`: turn off `entries` or `extraction`.
- `write_middleware`: extra middleware on routes that change data.
- `kinds`, `fields`, `qualification_frameworks`, `prompt_intro`: the playbook's shape and the line that introduces it in a prompt.
- `playbooks_table` and `run_migrations`: point at a table the host already owns and keep the package migration off.

Listen for `PlaybookEntrySaved`, `PlaybookEntryConfirmed`, `PlaybookEntryDeleted` and `PlaybookExtractionFinished`.

## Development

```bash
make bootstrap
docker compose run --rm app composer test
docker compose run --rm app composer pint
```
