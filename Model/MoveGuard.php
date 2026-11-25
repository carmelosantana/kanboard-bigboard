<?php

namespace Kanboard\Plugin\Bigboard\Model;

use Kanboard\Core\Base;

/**
 * Authorizes a BigBoard drag-and-drop move for the logged user. Every id comes
 * from the request body, so each is checked: the task really lives in the
 * source project, the target column and swimlane belong to the target project,
 * and the user may move tasks on both boards (same ACL as core's board save).
 * The source column is always the task's real column: the request's
 * src_column_id is never trusted. Closed tasks are refused, since
 * moveToProject would reopen them (core's board only drags open tasks).
 */
class MoveGuard extends Base
{
    public function isAllowed(array $move): bool
    {
        $taskId = (int) ($move['task_id'] ?? 0);
        $src = (int) ($move['src_project_id'] ?? 0);
        $dst = (int) ($move['dst_project_id'] ?? 0);
        $dstColumn = (int) ($move['dst_column_id'] ?? 0);
        $dstSwimlane = (int) ($move['dst_swimlane_id'] ?? 0);

        if ($taskId <= 0 || $src <= 0 || $dst <= 0) {
            return false;
        }

        $task = $this->taskFinderModel->getById($taskId);
        if (empty($task) || (int) $task['project_id'] !== $src || (int) $task['is_active'] !== 1) {
            return false;
        }

        if (! $this->belongsTo('columns', $dstColumn, $dst) || ! $this->belongsTo('swimlanes', $dstSwimlane, $dst)) {
            return false;
        }

        foreach (array_unique([$src, $dst]) as $projectId) {
            if (! $this->helper->user->hasProjectAccess('BoardAjaxController', 'save', $projectId)) {
                return false;
            }
        }

        if ($src === $dst) {
            $srcColumn = (int) $task['column_id'];

            // Reordering inside one column has no pair: use core's column rule.
            if ($srcColumn === $dstColumn) {
                return (bool) $this->helper->projectRole->isDraggable($task);
            }

            // isDraggable is column-wide, so an unrelated unrestricted rule on the
            // source column would hide an "only assigned" rule on this exact pair.
            // Authorize the pair itself, then its ownership requirement.
            return $this->pairAllowed($task, $src, $srcColumn, $dstColumn);
        }

        // Cross-project: the drag starts on the source board, so the task must be
        // draggable there by core's own rule (isDraggable: open task, column move
        // restrictions incl. "only assigned", and the task_move role restriction).
        // Core's "move to another project" (TaskDuplicationController::move) applies
        // no custom-role restriction on the destination beyond project access, which
        // is checked above, so none is invented here.
        return (bool) $this->helper->projectRole->isDraggable($task);
    }

    /**
     * Same-project move of an open task from $srcColumn to $dstColumn, using
     * core's pair semantics (a rule matches in either direction). When any rule
     * matching the pair is "only assigned", the task must belong to the user.
     */
    private function pairAllowed(array $task, int $projectId, int $srcColumn, int $dstColumn): bool
    {
        if (! $this->helper->projectRole->canMoveTask($projectId, $srcColumn, $dstColumn)) {
            return false;
        }

        $role = $this->helper->projectRole->getProjectUserRole($projectId);
        if (! $this->role->isCustomProjectRole($role)) {
            return true;
        }

        $matching = [];
        foreach ($this->columnMoveRestrictionCacheDecorator->getSortableColumns($projectId, $role) as $rule) {
            $forward = $rule['src_column_id'] == $srcColumn && $rule['dst_column_id'] == $dstColumn;
            $reverse = $rule['src_column_id'] == $dstColumn && $rule['dst_column_id'] == $srcColumn;
            if ($forward || $reverse) {
                $matching[] = $rule;
            }
        }

        foreach ($matching as $rule) {
            if ((int) $rule['only_assigned'] === 1) {
                return (int) $task['owner_id'] === (int) $this->userSession->getId();
            }
        }

        return true;
    }

    private function belongsTo(string $table, int $id, int $projectId): bool
    {
        return $id > 0 && $this->db->table($table)->eq('id', $id)->eq('project_id', $projectId)->exists();
    }
}
