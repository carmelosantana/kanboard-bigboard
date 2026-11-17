<?php

require_once 'tests/units/Base.php';

use KanboardTests\units\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\ProjectUserRoleModel;
use Kanboard\Model\TaskCreationModel;
use Kanboard\Model\UserModel;
use Kanboard\Plugin\Bigboard\Model\MoveGuard;

/**
 * BigBoard's drag-and-drop endpoint takes project, column and swimlane ids from
 * the request body, so the guard must check every one against the logged user.
 * Default project columns: project 1 → 1..4, project 2 → 5..8, project 3 → 9..12.
 */
class MoveGuardTest extends Base
{
    private int $taskInOne;
    private int $taskInForeign;

    protected function setUp(): void
    {
        parent::setUp();
        $projects = new ProjectModel($this->container);
        $projects->create(['name' => 'One']);      // 1
        $projects->create(['name' => 'Two']);      // 2
        $projects->create(['name' => 'Foreign']);  // 3
        $userId = (new UserModel($this->container))->create(['username' => 'bob']);
        $roles = new ProjectUserRoleModel($this->container);
        $roles->addUser(1, $userId, Role::PROJECT_MEMBER);
        $roles->addUser(2, $userId, Role::PROJECT_MEMBER);
        $_SESSION['user'] = ['id' => $userId, 'role' => Role::APP_USER];

        $tasks = new TaskCreationModel($this->container);
        $this->taskInOne = $tasks->create(['project_id' => 1, 'title' => 'mine']);
        $this->taskInForeign = $tasks->create(['project_id' => 3, 'title' => 'theirs']);
    }

    private function move(int $task, int $src, int $dst, int $srcCol, int $dstCol, int $dstLane): array
    {
        return [
            'task_id' => $task, 'src_project_id' => $src, 'dst_project_id' => $dst,
            'src_column_id' => $srcCol, 'dst_column_id' => $dstCol, 'dst_swimlane_id' => $dstLane,
            'position' => 1,
        ];
    }

    private function guard(): MoveGuard
    {
        return new MoveGuard($this->container);
    }

    public function testMoveWithinOwnProjectIsAllowed(): void
    {
        $this->assertTrue($this->guard()->isAllowed($this->move($this->taskInOne, 1, 1, 1, 2, 1)));
    }

    public function testMoveBetweenTwoOwnProjectsIsAllowed(): void
    {
        $this->assertTrue($this->guard()->isAllowed($this->move($this->taskInOne, 1, 2, 1, 5, 2)));
    }

    public function testTaskFromAnInaccessibleProjectIsRefused(): void
    {
        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInForeign, 3, 1, 9, 1, 1)));
    }

    public function testMismatchedSourceProjectIsRefused(): void
    {
        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInForeign, 1, 1, 1, 2, 1)));
    }

    public function testInaccessibleTargetProjectIsRefused(): void
    {
        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInOne, 1, 3, 1, 9, 3)));
    }

    public function testColumnFromAnotherProjectIsRefused(): void
    {
        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInOne, 1, 1, 1, 9, 1)));
    }

    public function testSwimlaneFromAnotherProjectIsRefused(): void
    {
        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInOne, 1, 2, 1, 5, 3)));
    }

    public function testMissingOrUnknownTaskIsRefused(): void
    {
        $this->assertFalse($this->guard()->isAllowed([]));
        $this->assertFalse($this->guard()->isAllowed($this->move(999, 1, 1, 1, 2, 1)));
    }

    public function testViewerCannotMove(): void
    {
        (new ProjectUserRoleModel($this->container))->changeUserRole(1, $_SESSION['user']['id'], Role::PROJECT_VIEWER);

        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInOne, 1, 1, 1, 2, 1)));
    }
}
