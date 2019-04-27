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
    public function setSubordinateBusinessUnitIds($parentBusinessUnitId, $businessUnitIds)
    {
        $this->subordinateBusinessUnitIds[$parentBusinessUnitId] = $businessUnitIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        return $this;
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
