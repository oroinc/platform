<?php

namespace Oro\Bundle\SecurityBundle\Owner;

/**
 * Purpose of interface is to build tree for AccessLevel level entity representation
 *
 * @internal Should cover most of OwnerTree methods, BC break possible
 */
interface OwnerTreeInterface
{
    /**
     * Add the given basic entity with local owner to the tree
     *
     * @param int|string $basicLevelEntityId
     * @param int|string|null $localLevelEntityId
     */
    public function addBasicEntity($basicLevelEntityId, $localLevelEntityId = null);

    /**
     * Add a global entity to the basic one to the tree
     *
     * @param int|string $basicLevelEntityId
     * @param int|string $globalLevelEntityId
     */
    public function addGlobalEntity($basicLevelEntityId, $globalLevelEntityId);

    /**
     * Add local entity to local-global entities combination to the tree
     *
     * @param int|string $basicLevelEntityId
     * @param int|string $localLevelEntityId
     * @param int|string $globalLevelEntityId
     */
    public function addLocalEntityToBasic($basicLevelEntityId, $localLevelEntityId, $globalLevelEntityId);

    /**
     * Add local entity to local-global entities combination to the tree
     *
     * @param int|string $localLevelEntityId
     * @param int|string $deepLevelEntityId Parent entity
     */
    public function addDeepEntity($localLevelEntityId, $deepLevelEntityId);

    /**
     * Add the given local entity to the tree
     *
     * @param int|string $localLevelEntityId
     * @param int|string|null $globalLevelEntityId
     */
    public function addLocalEntity($localLevelEntityId, $globalLevelEntityId = null);
}
