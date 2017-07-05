<?php

namespace Oro\Bundle\SecurityBundle\Owner;

/**
 * Provides an interface for owner tree
 */
interface OwnerTreeInterface
{
    /**
     * Gets the owning organization id for the given user id
     *
     * @param int|string $userId
     *
     * @return int|string|null
     */
    public function getUserOrganizationId($userId);

    /**
     * Gets all organization ids assigned to the given user id
     *
     * @param int|string $userId
     *
     * @return int[]|string[]
     */
    public function getUserOrganizationIds($userId);

    /**
     * Gets the owning business unit id for the given user id
     *
     * @param int|string $userId
     *
     * @return int|string|null
     */
    public function getUserBusinessUnitId($userId);

    /**
     * Gets all business unit ids assigned to the given user id
     *
     * @param int|string      $userId
     * @param int|string|null $organizationId
     *
     * @return array of int|string
     */
    public function getUserBusinessUnitIds($userId, $organizationId = null);

    /**
     * Gets ids of all users assigned to the given business unit
     *
     * @param int|string $businessUnitId
     *
     * @return array of int|string
     */
    public function getUsersAssignedToBusinessUnit($businessUnitId);

    /**
     * Gets ids of all users assigned to the given business units
     *
     * @param int[]|string[] $businessUnitIds
     *
     * @return int[]|string[]
     */
    public function getUsersAssignedToBusinessUnits(array $businessUnitIds);

    /**
     * Gets the owning organization id for the given business unit id
     *
     * @param int|string $businessUnitId
     *
     * @return int|string|null
     */
    public function getBusinessUnitOrganizationId($businessUnitId);

    /**
     * Gets all business unit ids for the given organization id
     *
     * @param int|string $organizationId
     *
     * @return array of int|string
     */
    public function getOrganizationBusinessUnitIds($organizationId);

    /**
     * Gets all subordinate business unit ids for the given business unit id
     *
     * @param int|string $businessUnitId
     *
     * @return array of int|string
     */
    public function getSubordinateBusinessUnitIds($businessUnitId);

    /**
     * Gets all user business unit ids with subordinate business unit ids
     *
     * @param int        $userId
     * @param int|string $organizationId
     *
     * @return array of int|string
     */
    public function getUserSubordinateBusinessUnitIds($userId, $organizationId = null);

    /**
     * Gets all user business unit ids by user organization ids
     *
     * @param int $userId
     *
     * @return array of int|string
     */
    public function getBusinessUnitsIdByUserOrganizations($userId);

    /**
     * Get all business units in system
     *
     * @return array of int|string
     */
    public function getAllBusinessUnitIds();
}
