<?php

namespace Oro\Bundle\SecurityBundle\Owner;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * This class represents a tree of owners
 */
class OwnerTree implements OwnerTreeInterface
{
    /**
     * An associative array to store owning organization of an user
     * key = userId
     * value = organizationId
     *
     * @var array
     */
    protected $userOwningOrganizationId;

    /**
     * An associative array to store owning organization of a business unit
     * key = businessUnitId
     * value = organizationId
     *
     * @var array
     */
    protected $businessUnitOwningOrganizationId;

    /**
     * An associative array to store owning business unit of an user
     * key = userId
     * value = businessUnitId
     *
     * @var array
     */
    protected $userOwningBusinessUnitId;

    /**
     * An associative array to store organizations assigned to an user
     * key = userId
     * value = array of organizationId
     *
     * @var array
     */
    protected $userOrganizationIds;

    /**
     * An associative array to store business units assigned to an user
     * key = userId
     * value = array of businessUnitId
     *
     * @var array
     */
    protected $userBusinessUnitIds;

    /**
     * An associative array to store business units assigned to an user through organizations
     * key = userId
     * value = array:
     *      key = organizationId
     *      value = array of businessUnitIds
     *
     * @var array
     */
    protected $userOrganizationBusinessUnitIds;

    /**
     * An associative array to store subordinate business units
     * key = businessUnitId
     * value = array of businessUnitId
     *
     * @var array
     */
    protected $subordinateBusinessUnitIds;

    /**
     * An associative array to store users belong to a business unit
     * key = businessUnitId
     * value = array of userId
     *
     * @var array
     */
    protected $businessUnitUserIds;

    /**
     * An associative array to store users belong to a assigned business unit
     * key = businessUnitId
     * value = array of userId
     *
     * @var array
     */
    protected $assignedBusinessUnitUserIds;

    /**
     * An associative array to store business units belong to an organization
     * key = organizationId
     * value = array of businessUnitId
     *
     * @var array
     */
    protected $organizationBusinessUnitIds;

    /**
     * An associative array to store users belong to an organization
     * key = organizationId
     * value = array of userId
     *
     * @var array
     */
    protected $organizationUserIds;

    public function __construct()
    {
        $this->clear();
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return OwnerTree A new instance of a OwnerTree object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        $result = new OwnerTree();
        foreach ($data as $key => $val) {
            $result->{$key} = $val;
        }

        return $result;
    }
    // @codingStandardsIgnoreEnd

    /**
     * Gets the owning organization id for the given user id
     *
     * @param  int|string $userId
     * @return int|string|null
     */
    public function getUserOrganizationId($userId)
    {
        return isset($this->userOwningOrganizationId[$userId])
            ? $this->userOwningOrganizationId[$userId]
            : null;
    }

    /**
     * Gets all organization ids assigned to the given user id
     *
     * @param  int|string $userId
     * @return int|string|null
     */
    public function getUserOrganizationIds($userId)
    {
        return isset($this->userOrganizationIds[$userId])
            ? $this->userOrganizationIds[$userId]
            : [];
    }

    /**
     * Gets the owning business unit id for the given user id
     *
     * @param  int|string $userId
     * @return int|string|null
     */
    public function getUserBusinessUnitId($userId)
    {
        return isset($this->userOwningBusinessUnitId[$userId])
            ? $this->userOwningBusinessUnitId[$userId]
            : null;
    }

    /**
     * Gets all business unit ids assigned to the given user id
     *
     * @param  int|string      $userId
     * @param  int|string|null $organizationId
     * @return array      of int|string
     */
    public function getUserBusinessUnitIds($userId, $organizationId = null)
    {
        if ($organizationId) {
            return isset($this->userOrganizationBusinessUnitIds[$userId][$organizationId])
                ? $this->userOrganizationBusinessUnitIds[$userId][$organizationId]
                : [];
        }

        return isset($this->userBusinessUnitIds[$userId])
            ? $this->userBusinessUnitIds[$userId]
            : [];
    }

    /**
     * Gets all users ids for the given business unit id
     *
     * @param  int|string $businessUnitId
     * @return array      of int|string
     */
    public function getBusinessUnitUserIds($businessUnitId)
    {
        return isset($this->businessUnitUserIds[$businessUnitId])
            ? $this->businessUnitUserIds[$businessUnitId]
            : [];
    }

    /**
     * Gets the owning organization id for the given business unit id
     *
     * @param  int|string $businessUnitId
     * @return int|string|null
     */
    public function getBusinessUnitOrganizationId($businessUnitId)
    {
        return isset($this->businessUnitOwningOrganizationId[$businessUnitId])
            ? $this->businessUnitOwningOrganizationId[$businessUnitId]
            : null;
    }

    /**
     * Gets all business unit ids for the given organization id
     *
     * @param  int|string $organizationId
     * @return array      of int|string
     */
    public function getOrganizationBusinessUnitIds($organizationId)
    {
        return isset($this->organizationBusinessUnitIds[$organizationId])
            ? $this->organizationBusinessUnitIds[$organizationId]
            : [];
    }

    /**
     * Gets all user ids for the given organization id
     *
     * @param  int|string $organizationId
     * @return array      of int|string
     */
    public function getOrganizationUserIds($organizationId)
    {
        $result = [];
        $buIds  = $this->getOrganizationBusinessUnitIds($organizationId);
        foreach ($buIds as $buId) {
            $userIds = $this->getBusinessUnitUserIds($buId);
            if (!empty($userIds)) {
                $result = array_merge($result, $userIds);
            }
        }

        return $result;
    }

    /**
     * Gets all subordinate business unit ids for the given business unit id
     *
     * @param  int|string $businessUnitId
     * @return array      of int|string
     */
    public function getSubordinateBusinessUnitIds($businessUnitId)
    {
        return isset($this->subordinateBusinessUnitIds[$businessUnitId])
            ? $this->subordinateBusinessUnitIds[$businessUnitId]
            : [];
    }

    /**
     * Gets all user business unit ids with subordinate business unit ids
     *
     * @param  int        $userId
     * @param  int|string $organizationId
     * @return array  of int|string
     */
    public function getUserSubordinateBusinessUnitIds($userId, $organizationId = null)
    {
        $buIds       = $this->getUserBusinessUnitIds($userId, $organizationId);
        $resultBuIds = array_merge($buIds, []);
        foreach ($buIds as $buId) {
            $diff = array_diff(
                $this->getSubordinateBusinessUnitIds($buId),
                $resultBuIds
            );
            if ($diff) {
                $resultBuIds = array_merge($resultBuIds, $diff);
            }
        }

        return $resultBuIds;
    }

    /**
     * Gets all user business unit ids by user organization ids
     *
     * @param int $userId
     * @return array  of int|string
     */
    public function getBusinessUnitsIdByUserOrganizations($userId)
    {
        $resultBuIds = [];
        $orgIds      = $this->getUserOrganizationIds($userId);
        foreach ($orgIds as $orgId) {
            $buIds = $this->getOrganizationBusinessUnitIds($orgId);
            if (!empty($buIds)) {
                $resultBuIds = array_merge($resultBuIds, $buIds);
            }
        }

        return $resultBuIds;
    }

    /**
     * Get all business units in system
     *
     * @return array
     */
    public function getAllBusinessUnitIds()
    {
        $resultBuIds = [];

        if (is_array($this->organizationBusinessUnitIds) && count($this->organizationBusinessUnitIds)) {
            foreach ($this->organizationBusinessUnitIds as $businessUnits) {
                $resultBuIds = array_merge($resultBuIds, $businessUnits);
            }
        }

        return $resultBuIds;
    }

    /**
     * Add the given business unit to the tree
     *
     * @param int|string      $businessUnitId
     * @param int|string|null $owningOrganizationId
     *
     * @deprecated 1.8.0:2.1.0 use OwnerTree::addLocalEntity method
     */
    public function addBusinessUnit($businessUnitId, $owningOrganizationId)
    {
        $this->addLocalEntity($businessUnitId, $owningOrganizationId);
    }

    /**
     * {@inheritdoc}
     */
    public function addLocalEntity($localLevelEntityId, $globalLevelEntityId = null)
    {
        $this->businessUnitOwningOrganizationId[$localLevelEntityId] = $globalLevelEntityId;

        if ($globalLevelEntityId !== null) {
            if (!isset($this->organizationBusinessUnitIds[$globalLevelEntityId])) {
                $this->organizationBusinessUnitIds[$globalLevelEntityId] = [];
            }
            $this->organizationBusinessUnitIds[$globalLevelEntityId][] = $localLevelEntityId;
        }

        $this->businessUnitUserIds[$localLevelEntityId] = [];
        foreach ($this->userOwningBusinessUnitId as $userId => $buId) {
            if ($localLevelEntityId === $buId) {
                $this->businessUnitUserIds[$localLevelEntityId][] = $userId;
                $this->userOwningOrganizationId[$userId]          = $globalLevelEntityId;
            }
        }
    }

    /**
     * Add a business unit relation to the tree
     *
     * @param int|string      $businessUnitId
     * @param int|string|null $parentBusinessUnitId
     *
     * @deprecated 1.8.0:2.1.0 use OwnerTree::addDeepEntity method
     */
    public function addBusinessUnitRelation($businessUnitId, $parentBusinessUnitId)
    {
        $this->addDeepEntity($businessUnitId, $parentBusinessUnitId);
    }

    /**
     * {@inheritdoc}
     */
    public function addDeepEntity($localLevelEntityId, $deepLevelEntityId)
    {
        $this->subordinateBusinessUnitIds[$localLevelEntityId] = $deepLevelEntityId;
    }

    /**
     * {@inheritdoc}
     */
    public function buildTree()
    {
        $subordinateBusinessUnitIds = [];
        $mapping = [];

        $calculatedLevels = $this->calculateAdjacencyListLevels();

        $levelsData = array_reverse($calculatedLevels);
        foreach ($levelsData as $childIds) {
            foreach ($childIds as $childId) {
                $subordinateBusinessUnitIds[$childId] = [];
                $parentsId = $this->subordinateBusinessUnitIds[$childId];

                if (null !== $parentsId) {
                    $mapping[$parentsId][] = $childId;
                }

                if (isset($mapping[$childId])) {
                    if (null !== $parentsId) {
                        $mapping[$parentsId] = array_merge($mapping[$parentsId], $mapping[$childId]);
                    }
                    $subordinateBusinessUnitIds[$childId] = array_merge(
                        $subordinateBusinessUnitIds[$childId],
                        $mapping[$childId]
                    );
                }
            }
        }

        $this->subordinateBusinessUnitIds = $subordinateBusinessUnitIds;
    }

    /**
     * Takes business units adjacency list and calculates tree level for each item in list.
     *
     * For details about Adjacency Lists see https://en.wikipedia.org/wiki/Adjacency_list
     * The only limitation for the algorithm is the input list should be sorted by parentId, at least all elements
     *  that do not has parentId should go first.
     * So we walk through all items and if item has parent - take parent level, increment it by 1 and assign to self.
     * For example:
     *
     *  id    -  parentID          Tree                        id    -  parentID  - level
     * ------------------       --------------------           ----------------------------
     *  b1    -  null              b1                          b1    -  null         0
     *  b2    -  null               +-- b11                    b2    -  null         0
     *  b11   -  b1                 |   +-- b111               b11   -  b1           1
     *  b12   -  b1                 |       +-- b1111          b12   -  b1           1
     *  b21   -  b2                 |       +-- b1112          b21   -  b2           1
     *  b111  -  b11                +-- b12                    b111  -  b11          2
     *  b121  -  b12                    +-- b121               b121  -  b12          2
     *  b122  -  b12                    +-- b122               b122  -  b12          2
     *  b1111 -  b111                       +-- b1221          b1111 -  b111         3
     *  b1112 -  b111              b2                          b1112 -  b111         3
     *  b1221 -  b122               +-- b21                    b1221 -  b122         3
     *
     * @return array Collection of business unit ids grouped by level
     */
    protected function calculateAdjacencyListLevels()
    {
        $levelsData = [];

        $hasUnprocessedRows = true;
        while ($hasUnprocessedRows) {
            $hasUnprocessedRows = false;
            while (list($i) = each($this->subordinateBusinessUnitIds)) {
                if ($this->subordinateBusinessUnitIds[$i] === null && !isset($levelsData[$i])) {
                    $levelsData[$i] = 0;
                } elseif (!isset($levelsData[$i]) && isset($levelsData[$this->subordinateBusinessUnitIds[$i]])) {
                    $levelsData[$i] = $levelsData[$this->subordinateBusinessUnitIds[$i]] + 1;
                } elseif (!isset($levelsData[$i])) {
                    $hasUnprocessedRows = true;
                }
            }
        }

        $result = [];
        foreach ($levelsData as $id => $level) {
            $result[$level][] = $id;
        }

        return $result;
    }

    /**
     * Add the given user to the tree
     *
     * @param int|string      $userId
     * @param int|string|null $owningBusinessUnitId
     *
     * @deprecated 1.8.0:2.1.0 use OwnerTree::addBasicEntity method
     */
    public function addUser($userId, $owningBusinessUnitId)
    {
        $this->addBasicEntity($userId, $owningBusinessUnitId);
    }

    /**
     * {@inheritdoc}
     */
    public function addBasicEntity($basicLevelEntityId, $localLevelEntityId = null)
    {
        $this->userOwningBusinessUnitId[$basicLevelEntityId] = $localLevelEntityId;

        if ($localLevelEntityId !== null) {
            if (isset($this->businessUnitUserIds[$localLevelEntityId])) {
                $this->businessUnitUserIds[$localLevelEntityId][] = $basicLevelEntityId;
            }

            $this->userOrganizationIds[$basicLevelEntityId] = [];
            if (isset($this->businessUnitOwningOrganizationId[$localLevelEntityId])) {
                $this->userOwningOrganizationId[$basicLevelEntityId] =
                    $this->businessUnitOwningOrganizationId[$localLevelEntityId];
            } else {
                $this->userOwningOrganizationId[$basicLevelEntityId] = null;
            }
        } else {
            $this->userOwningOrganizationId[$basicLevelEntityId] = null;
            $this->userOrganizationIds[$basicLevelEntityId]      = [];
        }

        $this->userBusinessUnitIds[$basicLevelEntityId]             = [];
        $this->userOrganizationBusinessUnitIds[$basicLevelEntityId] = [];
    }

    /**
     * @param $buId
     * @return array
     */
    public function getUsersAssignedToBU($buId)
    {
        return isset($this->assignedBusinessUnitUserIds[$buId])
            ? $this->assignedBusinessUnitUserIds[$buId]
            : [];
    }

    /**
     * Add a business unit to the given user
     *
     * @param  int|string      $userId
     * @param  int|string|null $organizationId
     * @param  int|string      $businessUnitId
     * @throws \LogicException
     *
     * @deprecated 1.8.0:2.1.0 use OwnerTree::addLocalEntityToBasic method
     */
    public function addUserBusinessUnit($userId, $organizationId, $businessUnitId)
    {
        $this->addLocalEntityToBasic($userId, $businessUnitId, $organizationId);
    }

    /**
     * Pay attention that now local level entity is second and global entity is third
     *
     * {@inheritdoc}
     */
    public function addLocalEntityToBasic($basicLevelEntityId, $localLevelEntityId, $globalLevelEntityId)
    {
        if (!isset($this->userOrganizationBusinessUnitIds[$basicLevelEntityId])
            || !isset($this->userBusinessUnitIds[$basicLevelEntityId])
        ) {
            throw new \LogicException(
                sprintf('First call OwnerTreeInterface::addBasicEntity for userId: %s.', (string) $basicLevelEntityId)
            );
        }
        if ($localLevelEntityId !== null) {
            if (!isset($this->assignedBusinessUnitUserIds[$localLevelEntityId])) {
                $this->assignedBusinessUnitUserIds[$localLevelEntityId] = [];
            }
            $this->assignedBusinessUnitUserIds[$localLevelEntityId][] = $basicLevelEntityId;
            $this->userBusinessUnitIds[$basicLevelEntityId][]         = $localLevelEntityId;
            if (!isset($this->userOrganizationBusinessUnitIds[$basicLevelEntityId][$globalLevelEntityId])) {
                $this->userOrganizationBusinessUnitIds[$basicLevelEntityId][$globalLevelEntityId] = [];
            }
            $this->userOrganizationBusinessUnitIds[$basicLevelEntityId][$globalLevelEntityId][] = $localLevelEntityId;
        }
    }

    /**
     * Add a organization to the given user
     *
     * @param int|string $userId
     * @param int|string $organizationId
     *
     * @deprecated 1.8.0:2.1.0 use OwnerTree::addGlobalEntity method
     */
    public function addUserOrganization($userId, $organizationId)
    {
        $this->addGlobalEntity($userId, $organizationId);
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobalEntity($basicLevelEntityId, $globalLevelEntityId)
    {
        $this->userOrganizationIds[$basicLevelEntityId][] = $globalLevelEntityId;
    }

    /**
     * Removes all elements from the tree
     */
    public function clear()
    {
        $this->userOwningOrganizationId         = [];
        $this->businessUnitOwningOrganizationId = [];
        $this->organizationBusinessUnitIds      = [];
        $this->userOwningBusinessUnitId         = [];
        $this->subordinateBusinessUnitIds       = [];
        $this->userOrganizationIds              = [];
        $this->userBusinessUnitIds              = [];
        $this->businessUnitUserIds              = [];
        $this->userOrganizationBusinessUnitIds  = [];
        $this->assignedBusinessUnitUserIds      = [];
    }
}
