# BigBoard for Kanboard

One board that shows several projects at once. Pick the projects, then work them side by
side: search across all of them, fold a project away, and drag a task from one project's
board into another's.

This is a maintained fork of
[TimoStahl/kanboard_plugin_bigboard](https://github.com/TimoStahl/kanboard_plugin_bigboard)
(formerly BlueTeck), part of the [carmelosantana Kanboard plugin suite](https://github.com/carmelosantana?tab=repositories&q=kanboard-).
It keeps the plugin name `Bigboard`, so it replaces the upstream plugin in place.

## What changed from upstream 1.5.2

- **Permissions.** The board, the project picker, saved selections and drag-and-drop
  are all checked against the projects you can access now, on both ends of a
  cross-project move. A cross-project move keeps the category when the target project has
  one with the same name, and keeps the assignee only if they can be assigned there.
- **CSRF.** Saving the selection and saving a move need Kanboard's CSRF token.
- **No plugin tables.** Selection and folded state live in `user_metadata`. No migrations,
  and no duplicate-key SQL errors when a fold is toggled twice quickly.
- **Cleanup.** No debug `error_log` on every page load, no override of Kanboard's
  `userSession` service, no N+1 queries per project, project names escaped in the picker,
  and links built with Kanboard's URL helper (works under a sub-folder).

## Install

Download the zip from the [latest release](https://github.com/carmelosantana/kanboard-bigboard/releases/latest)
and unzip it into `plugins/`, or install it from ModMenu. If the upstream `Bigboard`
plugin is installed, delete `plugins/Bigboard` first so its old files don't stay behind.

Upgrading from upstream: choose your projects again once (**BigBoard → Projects
Selection**). The old `bigboard_selected` / `bigboard_collapsed` tables are no longer read
and can be dropped.

Requires Kanboard 1.2.47+ and PHP 8.4+. Tested on SQLite, PostgreSQL and MariaDB.

## Credits

Original authors: [Thomas Stinner](https://github.com/stinnux), [BlueTeck](https://github.com/BlueTeck),
[Pierre Cadeot](https://github.com/pcadeot), [PapeCoding](https://github.com/PapeCoding),
[Chaosmeister](https://github.com/Chaosmeister), [Jake G](https://github.com/jieiku),
Timo Stahl and contributors. MIT licensed; see [LICENSE](LICENSE).
