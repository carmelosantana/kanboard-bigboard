<?php

require_once 'tests/units/Base.php';

use KanboardTests\units\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Model\ColumnMoveRestrictionModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\ProjectRoleModel;
use Kanboard\Model\ProjectRoleRestrictionModel;
use Kanboard\Model\ProjectUserRoleModel;
use Kanboard\Model\TaskCreationModel;
use Kanboard\Model\TaskStatusModel;
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

    /** Gives bob a custom role on the project; returns the role id. */
    private function customRole(int $projectId, string $name): int
    {
        $roleId = (new ProjectRoleModel($this->container))->create($projectId, $name);
        (new ProjectUserRoleModel($this->container))->changeUserRole($projectId, $_SESSION['user']['id'], $name);

        return $roleId;
    }

    private function taskInColumn(int $projectId, int $columnId, int $ownerId = 0): int
    {
        return (new TaskCreationModel($this->container))->create([
            'project_id' => $projectId, 'column_id' => $columnId, 'owner_id' => $ownerId, 'title' => 'placed',
        ]);
    }

    public function testForgedSourceColumnIsIgnored(): void
    {
        $roleId = $this->customRole(1, 'Mover');
        (new ColumnMoveRestrictionModel($this->container))->create(1, $roleId, 1, 2);
        $taskInThree = $this->taskInColumn(1, 3);

        // The task really sits in column 3; claiming column 1 must not unlock 1 -> 2.
        $this->assertFalse($this->guard()->isAllowed($this->move($taskInThree, 1, 1, 1, 2, 1)));
    }

    public function testClosedTaskIsRefused(): void
    {
        (new TaskStatusModel($this->container))->close($this->taskInOne);

        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInOne, 1, 1, 1, 2, 1)));
        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInOne, 1, 2, 1, 5, 2)));
    }

    public function testCustomRoleColumnRestrictionsHoldInTheSameProject(): void
    {
        $roleId = $this->customRole(1, 'Mover');
        (new ColumnMoveRestrictionModel($this->container))->create(1, $roleId, 1, 2);

        $this->assertTrue($this->guard()->isAllowed($this->move($this->taskInOne, 1, 1, 1, 2, 1)));
        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInOne, 1, 1, 1, 3, 1)));
    }

    public function testCustomRoleWithTaskMoveRestrictionCannotMoveInTheSameProject(): void
    {
        $roleId = $this->customRole(1, 'Frozen');
        (new ProjectRoleRestrictionModel($this->container))->create(1, $roleId, ProjectRoleRestrictionModel::RULE_TASK_MOVE);

        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInOne, 1, 1, 1, 2, 1)));
    }

    public function testCrossProjectMoveNeedsTheRealSourceColumnToBeMovable(): void
    {
        $roleId = $this->customRole(1, 'Mover');
        (new ColumnMoveRestrictionModel($this->container))->create(1, $roleId, 1, 2);
        $taskInThree = $this->taskInColumn(1, 3);

        $this->assertTrue($this->guard()->isAllowed($this->move($this->taskInOne, 1, 2, 1, 5, 2)));
        $this->assertFalse($this->guard()->isAllowed($this->move($taskInThree, 1, 2, 3, 5, 2)));
        $this->assertFalse($this->guard()->isAllowed($this->move($taskInThree, 1, 2, 1, 5, 2)));
    }

    public function testCrossProjectMoveHonoursTheTaskMoveRestriction(): void
    {
        $roleId = $this->customRole(1, 'Frozen');
        (new ProjectRoleRestrictionModel($this->container))->create(1, $roleId, ProjectRoleRestrictionModel::RULE_TASK_MOVE);

        $this->assertFalse($this->guard()->isAllowed($this->move($this->taskInOne, 1, 2, 1, 5, 2)));
    }

    public function testCrossProjectMoveHonoursOnlyAssigned(): void
    {
        $roleId = $this->customRole(1, 'Mover');
        (new ColumnMoveRestrictionModel($this->container))->create(1, $roleId, 1, 2, true);
        $someoneElse = (new UserModel($this->container))->create(['username' => 'alice']);
        $theirs = $this->taskInColumn(1, 1, $someoneElse);
        $mine = $this->taskInColumn(1, 1, $_SESSION['user']['id']);

        $this->assertFalse($this->guard()->isAllowed($this->move($theirs, 1, 2, 1, 5, 2)));
        $this->assertTrue($this->guard()->isAllowed($this->move($mine, 1, 2, 1, 5, 2)));
    }

    public function testCrossProjectMoveIgnoresDestinationColumnRules(): void
    {
        // Core's "move to another project" applies no custom-role rule on the destination.
        $roleId = $this->customRole(2, 'Mover');
        (new ColumnMoveRestrictionModel($this->container))->create(2, $roleId, 6, 7);

        $this->assertTrue($this->guard()->isAllowed($this->move($this->taskInOne, 1, 2, 1, 5, 2)));
    }
}
