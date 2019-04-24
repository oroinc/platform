<?php

namespace Oro\Bundle\SecurityBundle\Owner;

/**
 * Provides an interface for owner tree builder
 */
interface OwnerTreeBuilderInterface
{
    /**
     * Adds an user to the tree
     *
     * @param int|string      $userId
     * @param int|string|null $owningBusinessUnitId
     */
    public function addUser($userId, $owningBusinessUnitId = null);

    /**
     * Adds a organization to the given user
     *
     * @param int|string $userId
     * @param int|string $organizationId
     */
    public function addUserOrganization($userId, $organizationId);

    /**
     * Adds a business unit to the given user
     *
     * @param int|string      $userId
     * @param int|string      $organizationId
     * @param int|string|null $businessUnitId
     */
    public function addUserBusinessUnit($userId, $organizationId, $businessUnitId = null);

    /**
     * Adds a business unit to the tree
     *
     * @param int|string      $businessUnitId
     * @param int|string|null $owningOrganizationId
     */
    public function addBusinessUnit($businessUnitId, $owningOrganizationId = null);

    /**
     * Set array of subordinate business units to the given business unit
     *
     * @param int|string|null $parentBusinessUnitId
     * @param array           $businessUnitIds
     */
    public function setSubordinateBusinessUnitIds($parentBusinessUnitId, $businessUnitIds);

    /**
     * Gets the built owner tree
     *
     * @return OwnerTreeInterface
     */
    public function getTree();
}
