<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

/**
 * Interface for ownership metadata providers.
 */
interface OwnershipMetadataProviderInterface
{
    /**
     * @return bool
     */
    public function supports();

    /**
     * Get the ownership related metadata for the given entity
     *
     * @param string|null $className
     *
     * @return OwnershipMetadataInterface
     */
    public function getMetadata($className);

    /**
     * Gets the class name of the user entity
     *
     * @return string
     */
    public function getUserClass();

    /**
     * Gets the class name of the business unit entity
     *
     * @return string
     */
    public function getBusinessUnitClass();

    /**
     * Gets the class name of the organization entity
     *
     * @return string
     */
    public function getOrganizationClass();

    /**
     * @param int    $accessLevel Current object access level
     * @param string $className   Class name to test
     *
     * @return int
     */
    public function getMaxAccessLevel($accessLevel, $className = null);

    /**
     * Clears the ownership metadata cache
     *
     * If the class name is not specified this method clears all cached data
     *
     * @param string|null $className
     */
    public function clearCache($className = null);

    /**
     * Warms up the cache
     *
     * If the class name is specified this method warms up cache for this class only
     *
     * @param string|null $className
     */
    public function warmUpCache($className = null);
}
