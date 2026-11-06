# Contributing to PHP MCP Schema

Please open an issue before sending a pull request for a new feature or public
API change.

## Architecture

The package has explicit source boundaries:

- `resources/schema/` contains commit-pinned canonical MCP JSON Schemas.
- `src/Record/`, `src/Contract/`, and `src/Value/` contain deterministic public
  symbols produced from those schemas.
- `src/Internal/Catalog/` and `src/Internal/TypeRegistry.php` contain generated
  revision and type metadata used by the handwritten runtime.

Handwritten revision selection, validation, hydration, immutable storage, and
exceptions live outside those generated paths. The generator stages a complete
output before replacing only that explicit allowlist; handwritten siblings such
as `src/Record.php`, `src/Schema.php`, and other `src/Internal/` files are never
part of its deletion boundary.

Read [the architecture](docs/architecture.md)
before changing the public record model, revision availability, schema
interpretation, or application boundary.

## Requirements and setup

| Tool | Minimum version |
| --- | --- |
| PHP | 7.4 |
| Composer | 2.x |
| Node.js | 18, development only |

```bash
composer install
cd generator
npm install
```

Production Composer installs have no runtime dependency. AJV and Node are used
only to audit canonical inputs and generated output.

## Generated output changes

Do not edit generated PHP directly. Change the plain Node generator or its
reviewed inputs, then run:

```bash
cd generator
npm run generate
npm run verify
```

`verify` checks JavaScript syntax, both canonical source digests, AJV fixtures,
the all-pairs compatibility manifest, and deterministic generation.

Adding or removing a supported revision requires:

1. A commit-pinned official source and reviewed SHA-256 digest.
2. A compatibility comparison against every still-supported revision.
3. Generated structural, field, and directional-message inventories, plus a
   rationale-bearing review decision for each native getter-category or
   same-name kind change.
4. Updated behavioral and cross-revision tests.

Unknown schema constructs, semantic `$ref` siblings, and unreviewed getter or
kind changes must fail generation. The executable file layout and commands are
documented in [the generator guide](generator/README.md#add-a-supported-revision).

## Handwritten runtime changes

The selected schema is the only validation and construction authority. Keep the
runtime PHP 7.4-compatible and dependency-free. Do not add unchecked public
construction, validation-disabled modes, aliases for removed APIs, or alternate
record representations.

Run from the repository root:

```bash
composer test
composer analyse
composer validate --strict
composer autoload:verify
git diff --check
```

Tests should exercise public construction and raw JSON boundaries. When a PHP
interpreter boundary changes, run the suite on PHP 7.4 and a current supported
PHP version.

## Pull requests

- Keep each commit coherent and include generated output with its generator
  change.
- Explain revision-specific behavior with the exact MCP identifier.
- Include only test results actually run.
- Preserve canonical schema bytes; refresh them through
  `generator/update-schemas.mjs` only.
