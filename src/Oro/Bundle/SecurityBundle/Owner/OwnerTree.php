<?php

namespace Oro\Bundle\SecurityBundle\Owner;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * This class represents a tree of owners
 */
class OwnerTree implements OwnerTreeInterface, OwnerTreeBuilderInterface
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
     * {@inheritdoc}
     */
    public function getUserOrganizationId($userId)
    {
        if (!isset($this->userOwningOrganizationId[$userId])) {
            return null;
        }

        return $this->userOwningOrganizationId[$userId];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserOrganizationIds($userId)
    {
        if (!isset($this->userOrganizationIds[$userId])) {
            return [];
        }

        return $this->userOrganizationIds[$userId];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserBusinessUnitId($userId)
    {
        if (!isset($this->userOwningBusinessUnitId[$userId])) {
            return null;
        }

        return $this->userOwningBusinessUnitId[$userId];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserBusinessUnitIds($userId, $organizationId = null)
    {
        if ($organizationId) {
            if (!isset($this->userOrganizationBusinessUnitIds[$userId][$organizationId])) {
                return [];
            }

            return $this->userOrganizationBusinessUnitIds[$userId][$organizationId];
        }

        if (!isset($this->userBusinessUnitIds[$userId])) {
            return [];
        }

        return $this->userBusinessUnitIds[$userId];
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersAssignedToBusinessUnit($businessUnitId)
    {
        if (!isset($this->assignedBusinessUnitUserIds[$businessUnitId])) {
            return [];
        }

        return $this->assignedBusinessUnitUserIds[$businessUnitId];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getBusinessUnitOrganizationId($businessUnitId)
    {
        if (!isset($this->businessUnitOwningOrganizationId[$businessUnitId])) {
            return null;
        }

        return $this->businessUnitOwningOrganizationId[$businessUnitId];
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizationBusinessUnitIds($organizationId)
    {
        if (!isset($this->organizationBusinessUnitIds[$organizationId])) {
            return [];
        }

        return $this->organizationBusinessUnitIds[$organizationId];
    }

    /**
     * {@inheritdoc}
     */
    public function getSubordinateBusinessUnitIds($businessUnitId)
    {
        if (!isset($this->subordinateBusinessUnitIds[$businessUnitId])) {
            return [];
        }

        return $this->subordinateBusinessUnitIds[$businessUnitId];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function addBusinessUnit($businessUnitId, $owningOrganizationId = null)
    {
        if (null !== $owningOrganizationId) {
            $this->businessUnitOwningOrganizationId[$businessUnitId] = $owningOrganizationId;
            $this->organizationBusinessUnitIds[$owningOrganizationId][] = $businessUnitId;
            foreach ($this->userOwningBusinessUnitId as $userId => $buId) {
                if ($businessUnitId === $buId) {
                    $this->userOwningOrganizationId[$userId] = $owningOrganizationId;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addBusinessUnitRelation($businessUnitId, $parentBusinessUnitId)
    {
        $this->subordinateBusinessUnitIds[$businessUnitId] = $parentBusinessUnitId;
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
     * {@inheritdoc}
     */
    public function getTree()
    {
        return $this;
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
     * {@inheritdoc}
     */
    public function addUser($userId, $owningBusinessUnitId = null)
    {
        if (null !== $owningBusinessUnitId) {
            $this->userOwningBusinessUnitId[$userId] = $owningBusinessUnitId;
            if (isset($this->businessUnitOwningOrganizationId[$owningBusinessUnitId])) {
                $this->userOwningOrganizationId[$userId] =
                    $this->businessUnitOwningOrganizationId[$owningBusinessUnitId];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addUserBusinessUnit($userId, $organizationId, $businessUnitId = null)
    {
        if (null !== $businessUnitId) {
            $this->assignedBusinessUnitUserIds[$businessUnitId][] = $userId;
            $this->userBusinessUnitIds[$userId][] = $businessUnitId;
            $this->userOrganizationBusinessUnitIds[$userId][$organizationId][] = $businessUnitId;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addUserOrganization($userId, $organizationId)
    {
        $this->userOrganizationIds[$userId][] = $organizationId;
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
