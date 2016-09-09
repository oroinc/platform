<?php

namespace Oro\Bundle\SecurityBundle\Owner;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * This class represents a tree of owners
 */
class OwnerTree implements OwnerTreeInterface
{
    /**
     * A map for owning organization of an user
     * @var array [userId => organizationId, ...]
     */
    protected $userOwningOrganizationId;

    /**
     * A map for owning business unit of an user
     * @var array [userId => businessUnitId, ...]
     */
    protected $userOwningBusinessUnitId;

    /**
     * A map for organizations assigned to an user
     * @var array [userId => [organizationId, ...], ...]
     */
    protected $userOrganizationIds;

    /**
     * A map for business units assigned to an user
     * @var array [userId => [businessUnitId, ...], ...]
     */
    protected $userBusinessUnitIds;

    /**
     * A map for business units assigned to an user through organizations
     * @var array [userId => [organizationId => [businessUnitId, ...], ...], ...]
     */
    protected $userOrganizationBusinessUnitIds;

    /**
     * A map for owning organization of a business unit
     * @var array [businessUnitId => organizationId, ...]
     */
    protected $businessUnitOwningOrganizationId;

    /**
     * A map for users assigned to a business unit
     * @var array [businessUnitId => [userId, ...], ...]
     */
    protected $assignedBusinessUnitUserIds;

    /**
     * A map for subordinate business units
     * @var array [businessUnitId => [businessUnitId, ...], ...]
     */
    protected $subordinateBusinessUnitIds;

    /**
     * A map for business units belong to an organization
     * @var array [organizationId => [businessUnitId, ...], ...]
     */
    protected $organizationBusinessUnitIds;

    public function __construct()
    {
        $this->clear();
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     *
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
     * @param int|string $userId
     *
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
     * @param int|string $userId
     *
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
     * @param int|string $userId
     *
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
     * @param int|string      $userId
     * @param int|string|null $organizationId
     *
     * @return array of int|string
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
     * Gets ids of all users assigned to the given business unit
     *
     * @param int[]|string[] $businessUnitId
     *
     * @return array of int|string
     */
    public function getUsersAssignedToBusinessUnit($businessUnitId)
    {
        return isset($this->assignedBusinessUnitUserIds[$businessUnitId])
            ? $this->assignedBusinessUnitUserIds[$businessUnitId]
            : [];
    }

    /**
     * Gets ids of all users assigned to the given business units
     *
     * @param int[]|string[] $businessUnitIds
     *
     * @return int[]|string[]
     */
    public function getUsersAssignedToBusinessUnits(array $businessUnitIds)
    {
        $userIds = array_intersect_key($this->assignedBusinessUnitUserIds, array_flip($businessUnitIds));

        return !$userIds ? [] :array_values(
            array_unique(
                call_user_func_array(
                    'array_merge',
                    $userIds
                )
            )
        );
    }

    /**
     * Gets all users ids for the given business unit id
     *
     * @param int|string $businessUnitId
     *
     * @return array of int|string
     *
     * @deprecated since 1.10. This method is not used and will be removed
     */
    public function getBusinessUnitUserIds($businessUnitId)
    {
        $businessUnitUserIds = [];
        foreach ($this->userOwningBusinessUnitId as $userId => $buId) {
            if ($businessUnitId === $buId) {
                $businessUnitUserIds[] = $userId;
            }
        }

        return $businessUnitUserIds;
    }

    /**
     * Gets the owning organization id for the given business unit id
     *
     * @param int|string $businessUnitId
     *
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
     * @param int|string $organizationId
     *
     * @return array of int|string
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
     * @param int|string $organizationId
     *
     * @return array of int|string
     *
     * @deprecated since 1.10. This method is not used and will be removed
     */
    public function getOrganizationUserIds($organizationId)
    {
        $result = [];
        $buIds = $this->getOrganizationBusinessUnitIds($organizationId);
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
     * @param int|string $businessUnitId
     *
     * @return array of int|string
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
     * @param int        $userId
     * @param int|string $organizationId
     *
     * @return array of int|string
     */
    public function getUserSubordinateBusinessUnitIds($userId, $organizationId = null)
    {
        $buIds = $this->getUserBusinessUnitIds($userId, $organizationId);
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
     *
     * @return array of int|string
     */
    public function getBusinessUnitsIdByUserOrganizations($userId)
    {
        $resultBuIds = [];
        $orgIds = $this->getUserOrganizationIds($userId);
        foreach ($orgIds as $orgId) {
            if (isset($this->organizationBusinessUnitIds[$orgId])) {
                $resultBuIds = array_merge($resultBuIds, $this->organizationBusinessUnitIds[$orgId]);
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
        foreach ($this->organizationBusinessUnitIds as $buIds) {
            $resultBuIds = array_merge($resultBuIds, $buIds);
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
        if (null !== $globalLevelEntityId) {
            $this->businessUnitOwningOrganizationId[$localLevelEntityId] = $globalLevelEntityId;
            $this->organizationBusinessUnitIds[$globalLevelEntityId][] = $localLevelEntityId;
            foreach ($this->userOwningBusinessUnitId as $userId => $buId) {
                if ($localLevelEntityId === $buId) {
                    $this->userOwningOrganizationId[$userId] = $globalLevelEntityId;
                }
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
        $calculatedLevels = array_reverse($this->calculateAdjacencyListLevels());
        foreach ($calculatedLevels as $businessUnitIds) {
            foreach ($businessUnitIds as $buId) {
                $parentBuId = $this->subordinateBusinessUnitIds[$buId];
                if (null !== $parentBuId) {
                    $subordinateBusinessUnitIds[$parentBuId][] = $buId;
                    if (isset($subordinateBusinessUnitIds[$buId])) {
                        $subordinateBusinessUnitIds[$parentBuId] = array_merge(
                            $subordinateBusinessUnitIds[$parentBuId],
                            $subordinateBusinessUnitIds[$buId]
                        );
                    }
                }
            }
        }
        $this->subordinateBusinessUnitIds = $subordinateBusinessUnitIds;
    }

    /**
     * Takes business units adjacency list and calculates tree level for each item in list.
     *
     * For details about Adjacency Lists see https://en.wikipedia.org/wiki/Adjacency_list
     * The performance of the implemented algorithm depends on the order of items in the input list.
     * The best performance is reached when all children are added to the input list after parents.
     *
     * An example:
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
     * @return array [level => [business unit id, ...], ...]
     */
    protected function calculateAdjacencyListLevels()
    {
        $levelsData = [];
        $businessUnits = $this->subordinateBusinessUnitIds;
        while (!empty($businessUnits)) {
            $unprocessed = [];
            foreach ($businessUnits as $buId => $parentBuId) {
                if (null === $parentBuId) {
                    $levelsData[$buId] = 0;
                } elseif (array_key_exists($parentBuId, $levelsData)) {
                    $levelsData[$buId] = $levelsData[$parentBuId] + 1;
                } elseif (array_key_exists($parentBuId, $this->subordinateBusinessUnitIds)) {
                    $unprocessed[$buId] = $parentBuId;
                }
            }
            $businessUnits = $unprocessed;
        }

        $result = [];
        foreach ($levelsData as $buId => $level) {
            $result[$level][] = $buId;
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
        if (null !== $localLevelEntityId) {
            $this->userOwningBusinessUnitId[$basicLevelEntityId] = $localLevelEntityId;
            if (isset($this->businessUnitOwningOrganizationId[$localLevelEntityId])) {
                $this->userOwningOrganizationId[$basicLevelEntityId] =
                    $this->businessUnitOwningOrganizationId[$localLevelEntityId];
            }
        }
    }

    /**
     * @param int|string $buId
     *
     * @return array
     *
     * @deprecated since 1.10. Use getUsersAssignedToBusinessUnit method instead
     */
    public function getUsersAssignedToBU($buId)
    {
        return $this->getUsersAssignedToBusinessUnit($buId);
    }

    /**
     * Add a business unit to the given user
     *
     * @param int|string      $userId
     * @param int|string|null $organizationId
     * @param int|string      $businessUnitId
     *
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
        if (null !== $localLevelEntityId) {
            $this->assignedBusinessUnitUserIds[$localLevelEntityId][] = $basicLevelEntityId;
            $this->userBusinessUnitIds[$basicLevelEntityId][] = $localLevelEntityId;
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
        $this->userOwningOrganizationId = [];
        $this->userOwningBusinessUnitId = [];
        $this->userOrganizationIds = [];
        $this->userBusinessUnitIds = [];
        $this->userOrganizationBusinessUnitIds = [];
        $this->businessUnitOwningOrganizationId = [];
        $this->assignedBusinessUnitUserIds = [];
        $this->subordinateBusinessUnitIds = [];
        $this->organizationBusinessUnitIds = [];
    }
}
