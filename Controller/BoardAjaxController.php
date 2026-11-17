<?php

namespace Kanboard\Plugin\Bigboard\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Model\UserMetadataModel;

/**
 * Ajax endpoints behind the BigBoard: drag-and-drop (within and across
 * projects), polling, per-project filters and collapsed state.
 */
class BoardAjaxController extends BaseController
{
    /** Save a drag-and-drop move. Every id in the body is checked by MoveGuard. */
    public function save()
    {
        $this->checkReusableGETCSRFParam();
        $values = $this->request->getJson();

        if (! $this->request->isAjax() || ! is_array($values) || ! $this->bigboardMoveGuard->isAllowed($values)) {
            throw new AccessForbiddenException(e("You don't have the permission to move this task"));
        }

        $taskId = (int) $values['task_id'];
        $src = (int) $values['src_project_id'];
        $dst = (int) $values['dst_project_id'];
        $column = (int) $values['dst_column_id'];
        $swimlane = (int) $values['dst_swimlane_id'];

        if ($src !== $dst) {
            $task = $this->taskFinderModel->getById($taskId);
            $category = $this->categoryModel->getIdByName($dst, $this->categoryModel->getNameById((int) $task['category_id']));
            $owner = $this->projectPermissionModel->isAssignable($dst, (int) $task['owner_id']) ? (int) $task['owner_id'] : 0;

            if (! $this->taskProjectMoveModel->moveToProject($taskId, $dst, $swimlane, $column, (int) $category, $owner)) {
                throw new AccessForbiddenException(e('Unable to move this task to that project.'));
            }
        }

        $this->taskPositionModel->movePosition($dst, $taskId, $column, max(1, (int) ($values['position'] ?? 1)), $swimlane);
        $this->response->html($this->renderBoard($dst), 201);
    }

    /** Polling: re-render one board when it changed since the given timestamp. */
    public function check()
    {
        $projectId = $this->requireProjectId();

        if (! $this->projectModel->isModifiedSince($projectId, $this->request->getIntegerParam('timestamp'))) {
            $this->response->status(304);
        } else {
            $this->response->html($this->renderBoard($projectId));
        }
    }

    public function reload()
    {
        $projectId = $this->requireProjectId();
        $values = $this->request->getJson();
        $this->userSession->setFilters($projectId, empty($values['search']) ? '' : (string) $values['search']);

        $this->response->html($this->renderBoard($projectId));
    }

    public function collapse()
    {
        $this->changeDisplayMode(1);
    }

    public function expand()
    {
        $this->changeDisplayMode(0);
    }

    /** Toggle one project's folded state on the BigBoard. */
    public function collapseProject()
    {
        $projectId = $this->requireProjectId();
        $collapsed = $this->bigboardModel->toggleCollapsed($this->userSession->getId(), $projectId);

        $this->response->json(['status' => $collapsed]);
    }

    public function collapseAllProjects()
    {
        $this->bigboardModel->collapseAll($this->userSession->getId());
        $this->response->json(['status' => true]);
    }

    public function expandAllProjects()
    {
        $this->bigboardModel->expandAll($this->userSession->getId());
        $this->response->json(['status' => true]);
    }

    /** project_id is a query param, so Kanboard's project ACL has already vetted it. */
    private function requireProjectId(): int
    {
        $projectId = $this->request->getIntegerParam('project_id');

        if (! $projectId || ! $this->request->isAjax()) {
            throw new AccessForbiddenException();
        }

        return $projectId;
    }

    protected function renderBoard($projectId)
    {
        return $this->template->render('bigboard:board/table_container', [
            'project' => $this->projectModel->getById($projectId),
            'board_private_refresh_interval' => $this->configModel->get('board_private_refresh_interval'),
            'board_highlight_period' => $this->configModel->get('board_highlight_period'),
            'swimlanes' => $this->taskLexer
                ->build($this->userSession->getFilters($projectId))
                ->format($this->boardFormatter->withProjectId($projectId)),
        ]);
    }

    private function changeDisplayMode($mode)
    {
        $projectId = $this->requireProjectId();
        $this->userMetadataCacheDecorator->set(UserMetadataModel::KEY_BOARD_COLLAPSED.$projectId, $mode);
        $this->response->html($this->renderBoard($projectId));
    }
}
