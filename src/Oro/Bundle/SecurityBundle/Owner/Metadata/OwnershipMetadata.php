<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

/**
 * This class represents the entity ownership metadata
 */
class OwnershipMetadata implements \Serializable, OwnershipMetadataInterface
{
    const OWNER_TYPE_NONE = 0;
    const OWNER_TYPE_ORGANIZATION = 1;
    const OWNER_TYPE_BUSINESS_UNIT = 2;
    const OWNER_TYPE_USER = 3;

    /**
     * @var integer
     */
    protected $ownerType;

    /**
     * @var string
     */
    protected $ownerFieldName;

    /**
     * @var string
     */
    protected $ownerColumnName;

    /**
     * @var string
     */
    protected $organizationFieldName;

    /**
     * @var string
     */
    protected $organizationColumnName;

    /**
     * Constructor
     *
     * @param string $ownerType Can be one of ORGANIZATION, BUSINESS_UNIT or USER
     * @param string $ownerFieldName
     * @param string $ownerColumnName
     * @param string $organizationFieldName
     * @param string $organizationColumnName
     * @throws \InvalidArgumentException
     */
    public function __construct(
        $ownerType = '',
        $ownerFieldName = '',
        $ownerColumnName = '',
        $organizationFieldName = '',
        $organizationColumnName = ''
    ) {
        $constantName = $this->getConstantName($ownerType);

        if (defined($constantName)) {
            $this->ownerType = constant($constantName);
        } else {
            if (!empty($ownerType)) {
                throw new \InvalidArgumentException(sprintf('Unknown owner type: %s.', $ownerType));
            }
            $this->ownerType = self::OWNER_TYPE_NONE;
        }

        $this->ownerFieldName = $ownerFieldName;
        if ($this->ownerType !== self::OWNER_TYPE_NONE && empty($this->ownerFieldName)) {
            throw new \InvalidArgumentException('The owner field name must not be empty.');
        }

        $this->ownerColumnName = $ownerColumnName;
        if ($this->ownerType !== self::OWNER_TYPE_NONE && empty($this->ownerColumnName)) {
            throw new \InvalidArgumentException('The owner column name must not be empty.');
        }

        $this->organizationColumnName = $organizationColumnName;
        $this->organizationFieldName = $organizationFieldName;
    }

    /**
     * @param string $ownerType
     * @return string
     */
    protected function getConstantName($ownerType)
    {
        return sprintf('static::OWNER_TYPE_%s', strtoupper($ownerType));
    }

    /**
     * {@inheritdoc}
     */
    public function getOwnerType()
    {
        return $this->ownerType;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOwner()
    {
        return $this->ownerType !== self::OWNER_TYPE_NONE;
    }

    /**
     * Indicates whether the ownership of the entity is Organization
     *
     * @return bool
     *
     * @deprecated since 1.8, use isGlobalLevelOwned method instead
     */
    public function isOrganizationOwned()
    {
        return $this->isGlobalLevelOwned();
    }

    /**
     * {@inheritdoc}
     */
    public function isGlobalLevelOwned()
    {
        return $this->ownerType === self::OWNER_TYPE_ORGANIZATION;
    }

    /**
     * Indicates whether the ownership of the entity is BusinessUnit
     *
     * @return bool
     *
     * @deprecated since 1.8, use isLocalLevelOwned and isDeepLevelOwned method instead
     */
    public function isBusinessUnitOwned()
    {
        return $this->isLocalLevelOwned();
    }

    /**
     * {@inheritdoc}
     */
    public function isLocalLevelOwned($deep = false)
    {
        return $this->ownerType === self::OWNER_TYPE_BUSINESS_UNIT;
    }

    /**
     * Indicates whether the ownership of the entity is User
     *
     * @return bool
     *
     * @deprecated since 1.8, use isBasicLevelOwned method instead
     */
    public function isUserOwned()
    {
        return $this->isBasicLevelOwned();
    }

    /**
     * {@inheritdoc}
     */
    public function isBasicLevelOwned()
    {
        return $this->ownerType === self::OWNER_TYPE_USER;
    }

    /**
     * {@inheritdoc}
     */
    public function isSystemLevelOwned()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwnerFieldName()
    {
        return $this->ownerFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwnerColumnName()
    {
        return $this->ownerColumnName;
    }

    /**
     * @return string
     *
     * @deprecated since 1.8, use getGlobalOwnerColumnName method instead
     */
    public function getOrganizationColumnName()
    {
        return $this->getGlobalOwnerColumnName();
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobalOwnerColumnName()
    {
        return $this->organizationColumnName;
    }

    /**
     * @return string
     *
     * @deprecated since 1.8, use getGlobalOwnerFieldName method instead
     */
    public function getOrganizationFieldName()
    {
        return $this->getGlobalOwnerFieldName();
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobalOwnerFieldName()
    {
        return $this->organizationFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames()
    {
        if (!$this->hasOwner()) {
            return [
                AccessLevel::NONE_LEVEL   => AccessLevel::NONE_LEVEL_NAME,
                AccessLevel::SYSTEM_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
            ];
        }

        $minLevel = AccessLevel::BASIC_LEVEL;
        $maxLevel = AccessLevel::SYSTEM_LEVEL;

        if ($this->isBasicLevelOwned()) {
            $minLevel = AccessLevel::BASIC_LEVEL;
            $maxLevel = AccessLevel::GLOBAL_LEVEL;
        } elseif ($this->isLocalLevelOwned()) {
            $minLevel = AccessLevel::LOCAL_LEVEL;
            $maxLevel = AccessLevel::GLOBAL_LEVEL;
        } elseif ($this->isGlobalLevelOwned()) {
            $minLevel = AccessLevel::GLOBAL_LEVEL;
            $maxLevel = AccessLevel::GLOBAL_LEVEL;
        }

        return AccessLevel::getAccessLevelNames($minLevel, $maxLevel);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->ownerType,
                $this->ownerFieldName,
                $this->ownerColumnName,
                $this->organizationFieldName,
                $this->organizationColumnName,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->ownerType,
            $this->ownerFieldName,
            $this->ownerColumnName,
            $this->organizationFieldName,
            $this->organizationColumnName
            ) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return OwnershipMetadataInterface A new instance of a OwnershipMetadataInterface object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        $result = new static();
        foreach ($data as $property => $value) {
            if (property_exists($result, $property)) {
                $result->{$property} = $value;
            }
        }

        return $result;
    }
    // @codingStandardsIgnoreEnd
}
