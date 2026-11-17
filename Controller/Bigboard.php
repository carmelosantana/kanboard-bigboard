<?php

namespace Kanboard\Plugin\Bigboard\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Formatter\BoardFormatter;
use Kanboard\Model\UserMetadataModel;

/**
 * BigBoard: one page rendering the boards of several projects. Every project id
 * comes from BigboardModel, which only returns projects the user can access.
 */
class Bigboard extends BaseController
{
    /** Modal listing the user's accessible projects, with the current selection ticked. */
    public function select()
    {
        $userId = $this->userSession->getId();

        $this->response->html($this->template->render('bigboard:board/list', [
            'projects' => $this->bigboardModel->getSelectableProjects($userId),
            'selected' => $this->bigboardModel->getSelectedProjectIds($userId),
            'starred' => $this->starredProjectIds($userId),
        ]));
    }

    public function saveList()
    {
        $this->checkCSRFForm();
        $values = $this->request->getValues();
        $selection = isset($values['selection']) && is_array($values['selection']) ? $values['selection'] : [];

        $this->bigboardModel->saveSelection($this->userSession->getId(), $selection);
        $this->response->redirect($this->helper->url->to('Bigboard', 'index', ['plugin' => 'Bigboard']), true);
    }

    public function index()
    {
        $userId = $this->userSession->getId();
        $projectIds = $this->bigboardModel->getSelectedProjectIds($userId);
        $search = $this->request->getStringParam('search', (string) session_get('bigboardSearch'));
        session_set('bigboardSearch', $search);

        $categories = $users = $customFilters = [];
        foreach ($projectIds as $projectId) {
            $categories += array_flip($this->categoryModel->getList($projectId, false));
            $users += array_flip($this->projectUserRoleModel->getAssignableUsersList($projectId, false));
            foreach ($this->customFilterModel->getAll($projectId, $userId) as $filter) {
                $customFilters[] = $filter;
            }
        }

        $this->response->html($this->helper->layout->app('bigboard:board/show', [
            'values' => ['search' => $search],
            'custom_filters_list' => $customFilters,
            'users_list' => array_keys($users),
            'categories_list' => array_keys($categories),
            'title' => t('BigBoard').' ('.count($projectIds).')',
            'boards' => $this->renderBoards($projectIds, $search),
            'bigboarddisplaymode' => session_is_true('bigboardCollapsed'),
        ]));
    }

    public function collapseAll()
    {
        $this->changeDisplayMode(true);
    }

    public function expandAll()
    {
        $this->changeDisplayMode(false);
    }

    private function renderBoards(array $projectIds, string $search): string
    {
        $userId = $this->userSession->getId();
        $collapsedProjects = $this->bigboardModel->getCollapsedProjectIds($userId);
        $tasksCollapsed = session_is_true('bigboardCollapsed');
        $starred = $this->starredProjectIds($userId);
        $html = '';

        foreach ($this->projectModel->getAllByIds($projectIds) as $project) {
            $projectId = (int) $project['id'];
            $this->userMetadataCacheDecorator->set(UserMetadataModel::KEY_BOARD_COLLAPSED.$projectId, $tasksCollapsed);

            $html .= $this->template->render('bigboard:board/view', [
                'project' => $project,
                'is_collapsed' => in_array($projectId, $collapsedProjects, true),
                'is_starred' => in_array($projectId, $starred, true),
                'board_private_refresh_interval' => $this->configModel->get('board_private_refresh_interval'),
                'board_highlight_period' => $this->configModel->get('board_highlight_period'),
                'swimlanes' => $this->taskLexer
                    ->build($search)
                    ->format(BoardFormatter::getInstance($this->container)->withProjectId($projectId)),
            ]);
        }

        return $html;
    }

    /** Ids from the optional StarredProjects plugin; empty when it is absent. */
    private function starredProjectIds(int $userId): array
    {
        if (! isset($this->container['starredProjectsModel'])) {
            return [];
        }

        $model = $this->container['starredProjectsModel'];

        return method_exists($model, 'findAllProjects')
            ? array_map('intval', array_column($model->findAllProjects($userId), 'project_id'))
            : [];
    }

    private function changeDisplayMode(bool $collapsed)
    {
        session_set('bigboardCollapsed', $collapsed);

        if ($this->request->isAjax()) {
            // BoardDragAndDrop.refresh() swaps the whole #bigboard element.
            $this->response->html('<div id="bigboard">'.$this->renderBoards(
                $this->bigboardModel->getSelectedProjectIds($this->userSession->getId()),
                (string) session_get('bigboardSearch')
            ).'</div>');
        } else {
            $this->response->redirect($this->helper->url->to('Bigboard', 'index', ['plugin' => 'Bigboard']));
        }
    }
}
