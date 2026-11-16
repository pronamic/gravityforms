# MCP PHP schema generator

This directory contains the development-only generator for
`wordpress/php-mcp-schema`. Production installs include this guide but exclude
the generator code and its AJV development dependency.

The generator consumes only the commit-pinned canonical files under
`resources/schema/`. One loader verifies each raw digest, applies the reviewed
entries in `patches/<revision>.json`, verifies the patch and effective-document
digests, and rejects unsupported schema keywords or semantic `$ref` siblings.
Normal generation is offline. It writes:

- public records under `src/Record/`;
- public union contracts under `src/Contract/`;
- public enum value constants under `src/Value/`;
- one complete PHP-literal catalog per exact revision under
  `src/Internal/Catalog/`; and
- exact-revision type metadata in `src/Internal/TypeRegistry.php`.

The generator completes all writes in a staging tree, then replaces only those
five allowlisted paths. Handwritten siblings such as `src/Record.php`,
`src/Schema.php`, and other files under `src/Internal/` are never part of the
deletion boundary. Generated provenance is carried by the file header and this
ownership allowlist, not by a namespace layer.

## Install and verify

```bash
cd generator
npm install
npm run verify
```

`npm run verify` checks JavaScript syntax, the shared canonical AJV corpus,
pinned raw, patch, and effective-document digests, the all-pairs compatibility
audit, and deterministic generated output.

## Update canonical sources

```bash
cd generator
npm run schemas:update
npm run audit
npm run generate
```

`schemas:update` downloads only the commit-pinned URLs in
`schema-sources.json` and rejects a raw digest mismatch. The patch ledger is
data, not runtime code. Each entry names an exact JSON pointer, its expected old
value, its replacement, a rationale, and a line in the commit-pinned
`schema.ts`. An empty patch list records that no correction was needed.

Each revision's `messageRoots` in `schema-sources.json` declares all five
directional roots as `present` or `absent`. Audit and generation reject a root
whose presence differs from that expectation, or a present root that yields no
methods. For `2025-11-25`, `InputRequest` is intentionally absent; for
`2026-07-28`, `ServerRequest` is intentionally absent. Review these expectations
against the pinned source when updating or adding a revision.

## Add a supported revision

1. Add the exact revision, official commit, raw URL, and reviewed raw SHA-256 to
   `schema-sources.json`, including explicit `messageRoots` expectations.
   Add `patches/<revision>.json` using the existing file
   shape, even when its `patches` list is empty.
2. If the canonical JSON disagrees with the pinned TypeScript source, add only
   the reviewed exact-site corrections to that patch file. Run
   `npm run schemas:digests`, then record its `patchSha256` and
   `effectiveSha256` values in `schema-sources.json`.
3. Run `npm run schemas:check` to verify the raw source, patch ledger, effective
   document, vocabulary, references, and recorded digests.
4. Run `node audit-schemas.mjs --print-fingerprint` and record each pair's value
   as `reviewedStructuralFingerprint` in
   `compatibility/<older>__<newer>.json`.
5. Run `node audit-schemas.mjs --print-required-decisions`. Copy only the listed
   `getter:*` and `kind:*` keys into that pair's `decisions` object. Preserve the
   printed classification and write a substantive rationale for each decision.
   Structural, field, and message inventories are generated and must not be
   copied into the human review file.
6. Run `npm run audit`, `npm run generate`, and `npm run verify`. Add or update
   the shared canonical fixture and PHP behavior tests for observable revision
   differences.

Generation independently recomputes the required getter and kind decision set,
so a stale or missing review entry fails even when `audit` was not run first.

Review generated changes and run `npm run verify` before committing them. Never
edit generated PHP directly.
