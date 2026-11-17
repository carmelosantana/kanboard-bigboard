<?php

namespace Kanboard\Plugin\Bigboard\Model;

use Kanboard\Core\Base;
use Kanboard\Model\ProjectModel;

/**
 * Per-user BigBoard state: which projects are on the board and which are
 * collapsed. Stored as JSON id lists in user_metadata (no plugin tables), and
 * always intersected with the projects the user can currently access.
 */
class BigboardModel extends Base
{
    public const KEY_SELECTED = 'bigboard_selected';
    public const KEY_COLLAPSED = 'bigboard_collapsed';

    /** @return list<int> accessible selected project ids, ascending */
    public function getSelectedProjectIds(int $userId): array
    {
        return $this->accessible($userId, $this->read($userId, self::KEY_SELECTED));
    }

    public function saveSelection(int $userId, array $projectIds): void
    {
        $this->write($userId, self::KEY_SELECTED, $this->accessible($userId, $projectIds));
    }

    /** @return list<array{id:int,name:string,is_private:int}> accessible projects, by name */
    public function getSelectableProjects(int $userId): array
    {
        $ids = $this->projectPermissionModel->getActiveProjectIds($userId);

        if (empty($ids)) {
            return [];
        }

        return $this->db->table(ProjectModel::TABLE)
            ->columns('id', 'name', 'is_private')
            ->in('id', $ids)
            ->asc('name')
            ->findAll();
    }

    /** @return list<int> */
    public function getCollapsedProjectIds(int $userId): array
    {
        return $this->normalize($this->read($userId, self::KEY_COLLAPSED));
    }

    /** Flip one project's collapsed state; returns true when it is now collapsed. */
    public function toggleCollapsed(int $userId, int $projectId): bool
    {
        $collapsed = $this->getCollapsedProjectIds($userId);
        $isCollapsed = in_array($projectId, $collapsed, true);
        $next = $isCollapsed ? array_diff($collapsed, [$projectId]) : array_merge($collapsed, [$projectId]);
        $this->write($userId, self::KEY_COLLAPSED, $this->normalize($next));

        return ! $isCollapsed;
    }

    public function collapseAll(int $userId): void
    {
        $this->write($userId, self::KEY_COLLAPSED, $this->getSelectedProjectIds($userId));
    }

    public function expandAll(int $userId): void
    {
        $this->write($userId, self::KEY_COLLAPSED, []);
    }

    /** @return list<int> */
    private function accessible(int $userId, array $projectIds): array
    {
        $allowed = array_map('intval', $this->projectPermissionModel->getActiveProjectIds($userId));

        return array_values(array_intersect($this->normalize($projectIds), $allowed));
    }

    /** @return list<int> unique positive ints, ascending */
    private function normalize(array $ids): array
    {
        $clean = [];
        foreach ($ids as $id) {
            if ((is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0) {
                $clean[(int) $id] = (int) $id;
            }
        }
        ksort($clean);

        return array_values($clean);
    }

    private function read(int $userId, string $key): array
    {
        $decoded = json_decode((string) $this->userMetadataModel->get($userId, $key, '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function write(int $userId, string $key, array $ids): void
    {
        $this->userMetadataModel->save($userId, [$key => json_encode(array_values($ids))]);
    }
}
