# Changelog

## 2.0.1 — 2026-11-25

### Security
- Drag-and-drop moves are authorized from the task's real column, not the posted source column, and closed tasks are refused (moving one to another project used to reopen it). Moves within a project and across projects both follow core's draggable rule, including column restrictions and "only assigned".
- A same-project move is authorized against the exact source and destination column pair, so an unrelated unrestricted rule on the source column no longer lets a user move another user's task through an "only assigned" pair.

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
