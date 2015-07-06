<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

/**
 * @see Oro\Bundle\SecurityBundle\Acl\AccessLevel
 */
interface OwnershipMetadataInterface
{
    /**
     * Gets an owner type for an entity is represented this metadata object
     *
     * @return int Can be a value of one of OwnershipMetadata::OWNER_TYPE_* constants
     */
    public function getOwnerType();

    /**
     * Indicates whether the entity has an owner
     *
     * @return bool
     */
    public function hasOwner();

    /**
     * Indicates whether the entity owner has basic level
     *
     * @see Oro\Bundle\SecurityBundle\Acl\AccessLevel::BASIC_LEVEL
     *
     * @return bool
     */
    public function isBasicLevelOwned();

    /**
     * Indicates whether the entity owner has local level
     *
     * @see Oro\Bundle\SecurityBundle\Acl\AccessLevel::LOCAL_LEVEL
     * and @see Oro\Bundle\SecurityBundle\Acl\AccessLevel::DEEP_LEVEL if true passed
     *
     * @param bool $deep false by default
     *
     * @return bool
     */
    public function isLocalLevelOwned($deep = false);

    /**
     * Indicates whether the entity owner has global level
     *
     * @see Oro\Bundle\SecurityBundle\Acl\AccessLevel::GLOBAL_LEVEL
     *
     * @return bool
     */
    public function isGlobalLevelOwned();

    /**
     * Indicates whether the entity owner has system level
     *
     * @see Oro\Bundle\SecurityBundle\Acl\AccessLevel::SYSTEM_LEVEL
     *
     * @return bool
     */
    public function isSystemLevelOwned();

    /**
     * Gets the name of the field is used to store the entity owner
     *
     * @return string
     */
    public function getOwnerFieldName();

    /**
     * Gets the name of the database column is used to store the entity owner
     *
     * @return string
     */
    public function getOwnerColumnName();

    /**
     * @return string
     */
    public function getGlobalOwnerColumnName();

    /**
     * @return string
     */
    public function getGlobalOwnerFieldName();

    /**
     * Get list of allowed access level names
     *
     * @return array
     */
    public function getAccessLevelNames();
}
