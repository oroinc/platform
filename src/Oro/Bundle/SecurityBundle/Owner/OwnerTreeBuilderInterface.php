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
     * Adds a parent business unit to the given business unit
     *
     * @param int|string      $businessUnitId
     * @param int|string|null $parentBusinessUnitId
     */
    public function addBusinessUnitRelation($businessUnitId, $parentBusinessUnitId);

    /**
     * Add the given basic entity with local owner to the tree
     *
     * @param int|string $basicLevelEntityId
     * @param int|string|null $localLevelEntityId
     * @deprecated since 2.3. Use addUser instead
     */
    public function addBasicEntity($basicLevelEntityId, $localLevelEntityId = null);

    /**
     * Add a global entity to the basic one to the tree
     *
     * @param int|string $basicLevelEntityId
     * @param int|string $globalLevelEntityId
     * @deprecated since 2.3. Use addUserOrganization instead
     */
    public function addGlobalEntity($basicLevelEntityId, $globalLevelEntityId);

    /**
     * Add local entity to local-global entities combination to the tree
     *
     * @param int|string $basicLevelEntityId
     * @param int|string $localLevelEntityId
     * @param int|string $globalLevelEntityId
     * @deprecated since 2.3. Use addUserBusinessUnit instead
     */
    public function addLocalEntityToBasic($basicLevelEntityId, $localLevelEntityId, $globalLevelEntityId);

    /**
     * Add local entity to local-global entities combination to the tree
     *
     * @param int|string $localLevelEntityId
     * @param int|string $deepLevelEntityId Parent entity
     * @deprecated since 2.3. Use addBusinessUnitRelation instead
     */
    public function addDeepEntity($localLevelEntityId, $deepLevelEntityId);

    /**
     * Add the given local entity to the tree
     *
     * @param int|string $localLevelEntityId
     * @param int|string|null $globalLevelEntityId
     * @deprecated since 2.3. Use addBusinessUnit instead
     */
    public function addLocalEntity($localLevelEntityId, $globalLevelEntityId = null);

    /**
     * Calculate subordinated entity ids.
     *
     * The main aim is to remove such calculations inside each "addBusinessUnitRelation"
     * as it has performance impact in case of huge amount of entities.
     *
     * Should be called only once after all "addBusinessUnitRelation".
     */
    public function buildTree();

    /**
     * Gets the built owner tree
     *
     * @return OwnerTreeInterface
     */
    public function getTree();
}
