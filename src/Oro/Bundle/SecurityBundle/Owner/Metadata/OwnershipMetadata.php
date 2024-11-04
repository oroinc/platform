<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

/**
 * Represents the entity ownership metadata.
 * Supported owner types: "NONE", "ORGANIZATION", "BUSINESS_UNIT" or "USER".
 */
class OwnershipMetadata implements OwnershipMetadataInterface
{
    public const OWNER_TYPE_NONE = 0;
    public const OWNER_TYPE_ORGANIZATION = 1;
    public const OWNER_TYPE_BUSINESS_UNIT = 2;
    public const OWNER_TYPE_USER = 3;

    protected int $ownerType;
    protected string $ownerFieldName;
    protected string $ownerColumnName;
    protected string $organizationFieldName;
    protected string $organizationColumnName;

    public function __construct(
        string $ownerType = '',
        string $ownerFieldName = '',
        string $ownerColumnName = '',
        string $organizationFieldName = '',
        string $organizationColumnName = ''
    ) {
        $this->ownerType = $this->resolveOwnerType($ownerType);

        if (self::OWNER_TYPE_NONE !== $this->ownerType) {
            if (!$ownerFieldName) {
                throw new \InvalidArgumentException('The owner field name must not be empty.');
            }
            if (!$ownerColumnName) {
                throw new \InvalidArgumentException('The owner column name must not be empty.');
            }
        }

        $this->ownerFieldName = $ownerFieldName;
        $this->ownerColumnName = $ownerColumnName;
        $this->organizationColumnName = $organizationColumnName;
        $this->organizationFieldName = $organizationFieldName;

        $this->initialize();
    }

    #[\Override]
    public function getOwnerType(): int
    {
        return $this->ownerType;
    }

    #[\Override]
    public function hasOwner(): bool
    {
        return self::OWNER_TYPE_NONE !== $this->ownerType;
    }

    #[\Override]
    public function isOrganizationOwned(): bool
    {
        return self::OWNER_TYPE_ORGANIZATION === $this->ownerType;
    }

    #[\Override]
    public function isBusinessUnitOwned(): bool
    {
        return self::OWNER_TYPE_BUSINESS_UNIT === $this->ownerType;
    }

    #[\Override]
    public function isUserOwned(): bool
    {
        return self::OWNER_TYPE_USER === $this->ownerType;
    }

    #[\Override]
    public function getOwnerFieldName(): string
    {
        return $this->ownerFieldName;
    }

    #[\Override]
    public function getOwnerColumnName(): string
    {
        return $this->ownerColumnName;
    }

    #[\Override]
    public function getOrganizationFieldName(): string
    {
        return $this->organizationFieldName;
    }

    #[\Override]
    public function getOrganizationColumnName(): string
    {
        return $this->organizationColumnName;
    }

    #[\Override]
    public function getAccessLevelNames(): array
    {
        if (!$this->hasOwner()) {
            return [
                AccessLevel::NONE_LEVEL   => AccessLevel::NONE_LEVEL_NAME,
                AccessLevel::SYSTEM_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
            ];
        }

        $minLevel = AccessLevel::BASIC_LEVEL;
        $maxLevel = AccessLevel::SYSTEM_LEVEL;
        if ($this->isUserOwned()) {
            $maxLevel = AccessLevel::GLOBAL_LEVEL;
        } elseif ($this->isBusinessUnitOwned()) {
            $minLevel = AccessLevel::LOCAL_LEVEL;
            $maxLevel = AccessLevel::GLOBAL_LEVEL;
        } elseif ($this->isOrganizationOwned()) {
            $minLevel = AccessLevel::GLOBAL_LEVEL;
            $maxLevel = AccessLevel::GLOBAL_LEVEL;
        }

        return AccessLevel::getAccessLevelNames($minLevel, $maxLevel);
    }

    public function __serialize(): array
    {
        return [
            $this->ownerType,
            $this->ownerFieldName,
            $this->ownerColumnName,
            $this->organizationFieldName,
            $this->organizationColumnName,
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->ownerType,
            $this->ownerFieldName,
            $this->ownerColumnName,
            $this->organizationFieldName,
            $this->organizationColumnName,
        ] = $serialized;
    }

    /**
     * @param array $data Initialization array
     *
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

    protected function resolveOwnerType(string $ownerType): int
    {
        if ($ownerType) {
            $constantName = sprintf('static::OWNER_TYPE_%s', strtoupper($ownerType));
            if (!\defined($constantName)) {
                throw new \InvalidArgumentException(sprintf('Unknown owner type: %s.', $ownerType));
            }

            return \constant($constantName);
        }

        return self::OWNER_TYPE_NONE;
    }

    protected function initialize(): void
    {
        if (self::OWNER_TYPE_ORGANIZATION === $this->ownerType && !$this->organizationFieldName) {
            $this->organizationFieldName = $this->ownerFieldName;
            $this->organizationColumnName = $this->ownerColumnName;
        }
    }
}
