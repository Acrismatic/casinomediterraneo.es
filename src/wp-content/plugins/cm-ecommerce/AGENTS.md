# Purpose

Define enforceable and reviewable rules for contributors and agents working on this WordPress/WooCommerce plugin. These rules prioritize security, compatibility, and maintainability under strict policy evaluation.

# Mandatory Rules

## Security (WordPress/WooCommerce)

- Privileged POST or AJAX actions MUST verify user capabilities with `current_user_can()` before mutating state.
- Privileged POST or AJAX actions MUST verify nonce using `check_admin_referer()` or `check_ajax_referer()`.
- Input persisted to database, post meta, options, or order data MUST be sanitized with context-appropriate WordPress sanitizers (for example `sanitize_text_field()`, `absint()`, `sanitize_email()`, `wp_kses_post()`).
- User-facing output rendered in admin or frontend templates MUST be escaped with context-appropriate escaping functions (for example `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`).
- Errors MUST NOT be silenced with the `@` operator.

## Integration Boundaries

- WooCommerce behavior SHALL be extended through official hooks, APIs, and domain objects when available.
- Core WooCommerce files and third-party plugin core files MUST NOT be modified directly.
- Dynamic SQL MUST use `$wpdb->prepare()`.
- New or changed hooks SHALL be registered in a clear bootstrap location with single-responsibility callbacks.

## Naming and Syntax Validity

- New plugin PHP files MUST use the `cm-` prefix in filenames.
- New global helper functions introduced by the plugin SHALL use a `cm_` prefix when technically valid.
- Public method and function names MUST be valid PHP identifiers and MUST NOT include hyphens.
- Policy examples and required identifiers SHALL remain syntactically valid for PHP 8.x.

## Change Safety and Quality Gates

- Changes MUST remain scoped to the requested task and MUST avoid unrelated functional edits.
- Before finalizing a change, updated PHP files MUST pass `php -l` when syntax validation is feasible in the local environment.
- If tests cannot be executed locally, the change report MUST explicitly state what could not be validated.
- Added or modified user-visible strings MUST be internationalized with the plugin text domain.
- Change reports MUST include: what changed, why, risks, and how to validate.

# Recommended Rules

## Architecture Direction

- New modules SHOULD move toward DDD, Screaming Architecture, and SOLID-aligned boundaries.
- Business logic SHOULD be separated from framework glue code when practical.
- Large legacy sections MAY be incrementally refactored instead of rewritten in one shot.

## Maintainability

- New PHP code SHOULD follow WPCS and PSR-12 where they do not conflict.
- New internal services detached from direct WordPress API calls SHOULD use `declare(strict_types=1);` with explicit parameter and return types.
- Complex functions SHOULD be split into smaller private methods with explicit names.
- Mutable global state SHOULD be minimized.

## Performance and Operations

- Repeated database calls in loops SHOULD be reduced through batching, caching, or indexed lookups.
- Heavy logic SHOULD be loaded conditionally and MAY be deferred using cron or async actions.
- Data migration and uninstall behavior SHOULD remain idempotent and safe for plugin-owned data only.

# Compatibility Constraints

## Legacy Boundary

- Mandatory enforcement SHALL apply to files touched by the current diff.
- Legacy code outside the current diff SHALL NOT block the change solely for historical non-conformance.
- Contributors SHOULD leave nearby legacy code better than found when low-risk improvements are obvious.

## Repository Reality Alignment

- Mandatory checks MUST stay compatible with current project reality and runtime constraints.
- Target architecture guidance SHALL be treated as recommended direction, not an immediate hard gate for untouched areas.

# Validation Checklist

- [ ] Top-level sections exist in this exact order: Purpose, Mandatory Rules, Recommended Rules, Compatibility Constraints, Validation Checklist, Change Scope.
- [ ] Every mandatory statement uses RFC 2119 strength (`MUST`, `SHALL`, `MUST NOT`, `SHALL NOT`) and is objectively verifiable.
- [ ] No mandatory statement uses subjective language that cannot be tested from repo evidence.
- [ ] Naming rules separate filename/class conventions from PHP method/function identifier constraints.
- [ ] WordPress/WooCommerce security guardrails are present as mandatory and testable rules.
- [ ] Legacy-safe enforcement boundary is explicit and non-retroactive for untouched files.

# Change Scope

## In Scope

- Document structure, wording, and normative severity for this policy file.
- Clarification of enforceable security, integration, naming, and review behavior.

## Out of Scope

- Refactoring runtime plugin code to fully match target architecture.
- Modifying `.gga` behavior or strict-mode engine implementation.
- Enforcing retroactive policy fixes in untouched legacy files.
