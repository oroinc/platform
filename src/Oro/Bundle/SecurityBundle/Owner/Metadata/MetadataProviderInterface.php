<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

interface MetadataProviderInterface
{
    /**
     * @return bool
     */
    public function supports();

    /**
     * Get the ownership related metadata for the given entity
     *
     * @param string $className
     *
     * @return OwnershipMetadataInterface
     */
    public function getMetadata($className);

    /**
     * @return string
     */
    public function getBasicLevelClass();

    /**
     * @param bool $deep
     *
     * @return string
     */
    public function getLocalLevelClass($deep = false);

    /**
     * @return string
     */
    public function getGlobalLevelClass();

    /**
     * @return string
     */
    public function getSystemLevelClass();

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
