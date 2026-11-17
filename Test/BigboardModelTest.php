<?php

require_once 'tests/units/Base.php';

use KanboardTests\units\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\ProjectUserRoleModel;
use Kanboard\Model\UserMetadataModel;
use Kanboard\Model\UserModel;
use Kanboard\Plugin\Bigboard\Model\BigboardModel;

/**
 * BigBoard's per-user project selection and collapsed state. The selection is a
 * cross-project view, so every read and write is filtered by project access.
 */
class BigboardModelTest extends Base
{
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $projects = new ProjectModel($this->container);
        $projects->create(['name' => 'Mine A']);   // 1
        $projects->create(['name' => 'Mine B']);   // 2
        $projects->create(['name' => 'Foreign']);  // 3
        $this->userId = (new UserModel($this->container))->create(['username' => 'bob']);
        $roles = new ProjectUserRoleModel($this->container);
        $roles->addUser(1, $this->userId, Role::PROJECT_MEMBER);
        $roles->addUser(2, $this->userId, Role::PROJECT_VIEWER);
    }

    private function model(): BigboardModel
    {
        return new BigboardModel($this->container);
    }

    public function testSaveSelectionDropsProjectsTheUserCannotSee(): void
    {
        $this->model()->saveSelection($this->userId, ['1', 2, 3, 999]);

        $this->assertSame([1, 2], $this->model()->getSelectedProjectIds($this->userId));
    }

    public function testSelectionNormalizesDuplicatesAndJunk(): void
    {
        $this->model()->saveSelection($this->userId, [2, '2', 1, 'x', -1, null, ['1']]);

        $this->assertSame([1, 2], $this->model()->getSelectedProjectIds($this->userId));
    }

    public function testStoredProjectIsHiddenOnceAccessIsRevoked(): void
    {
        $this->model()->saveSelection($this->userId, [1, 2]);
        (new ProjectUserRoleModel($this->container))->removeUser(2, $this->userId);

        $this->assertSame([1], $this->model()->getSelectedProjectIds($this->userId));
    }

    public function testTamperedMetadataCannotReachForeignProjects(): void
    {
        (new UserMetadataModel($this->container))->save($this->userId, [BigboardModel::KEY_SELECTED => '[1,3]']);

        $this->assertSame([1], $this->model()->getSelectedProjectIds($this->userId));
    }

    public function testUserWithNoProjectsSeesNothing(): void
    {
        $loner = (new UserModel($this->container))->create(['username' => 'loner']);
        (new UserMetadataModel($this->container))->save($loner, [BigboardModel::KEY_SELECTED => '[1,2,3]']);

        $this->assertSame([], $this->model()->getSelectedProjectIds($loner));
    }

    public function testEmptySelectionByDefault(): void
    {
        $this->assertSame([], $this->model()->getSelectedProjectIds($this->userId));
        $this->assertSame([], $this->model()->getCollapsedProjectIds($this->userId));
    }

    public function testToggleCollapsedRepeatedlyNeverFails(): void
    {
        $this->assertTrue($this->model()->toggleCollapsed($this->userId, 1));
        $this->assertSame([1], $this->model()->getCollapsedProjectIds($this->userId));
        $this->assertFalse($this->model()->toggleCollapsed($this->userId, 1));
        $this->assertTrue($this->model()->toggleCollapsed($this->userId, 1));
        $this->assertSame([1], $this->model()->getCollapsedProjectIds($this->userId));
    }

    public function testCollapseAllCoversTheSelectionAndExpandAllClears(): void
    {
        $this->model()->saveSelection($this->userId, [1, 2]);
        $this->model()->collapseAll($this->userId);
        $this->model()->collapseAll($this->userId);
        $this->assertSame([1, 2], $this->model()->getCollapsedProjectIds($this->userId));

        $this->model()->expandAll($this->userId);
        $this->assertSame([], $this->model()->getCollapsedProjectIds($this->userId));
    }

    public function testListableProjectsAreOnlyAccessibleOnesSortedByName(): void
    {
        $rows = $this->model()->getSelectableProjects($this->userId);

        $this->assertSame(['Mine A', 'Mine B'], array_column($rows, 'name'));
    }
}
