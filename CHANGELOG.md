# Changelog

## 2.0.0 — 2026-11-17

First release of the maintained fork (upstream 1.5.2).

- Security: permission checks hardened on the project selection, the board and
  drag-and-drop moves.
- Fix: CSRF checks on saving the selection and on moves.
- Change: selection and folded state move from plugin tables to `user_metadata`.
- Fix: cross-project moves map the category by name and drop an assignee who can't be
  assigned in the target project.
- Remove: debug logging, the `userSession` service override, the unused project-creation
  action and dropdown template.
