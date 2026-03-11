# Purpose

This root policy file is a router for repository-level reviews. It delegates detailed rules to project-specific `AGENTS.md` files.

# Delegation Rules

- Changes under `src/wp-content/plugins/cm-ecommerce/**` MUST follow `src/wp-content/plugins/cm-ecommerce/AGENTS.md` as the authoritative policy.
- If a path has no project-specific `AGENTS.md`, contributors SHALL apply conservative defaults: least-privilege access checks, input sanitization, output escaping, and scoped changes only.
- When both root and project policies apply, the most specific path policy SHALL take precedence.

# Reviewer Instructions

- For diffs touching only `src/wp-content/plugins/cm-ecommerce/**`, evaluate compliance against `src/wp-content/plugins/cm-ecommerce/AGENTS.md`.
- For cross-project diffs, evaluate each file against its nearest path-specific policy and report findings grouped by project.
- Reviewers MUST NOT invent requirements not present in either this root file or the selected project policy.

# Scope

- This file defines routing behavior only.
- It does not replace project-level standards.
