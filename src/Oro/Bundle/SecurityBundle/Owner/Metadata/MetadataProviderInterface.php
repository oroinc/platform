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
     * @param int        $accessLevel Current object access level
     * @param mixed|null $object      Object for test
     *
     * @return int
     */
    public function getMaxAccessLevel($accessLevel, $object = null);
}
