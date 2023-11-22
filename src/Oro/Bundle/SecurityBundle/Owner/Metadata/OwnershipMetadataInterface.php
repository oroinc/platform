<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

/**
 * Represents the entity ownership metadata.
 */
interface OwnershipMetadataInterface
{
    /**
     * Gets an owner type for an entity is represented this metadata object.
     */
    public function getOwnerType(): int;

    /**
     * Indicates whether the entity has an owner.
     */
    public function hasOwner(): bool;

    /**
     * Indicates whether the entity owner is an user.
     */
    public function isUserOwned(): bool;

    /**
     * Indicates whether the entity owner is a business user.
     */
    public function isBusinessUnitOwned(): bool;

    /**
     * Indicates whether the entity owner is an organisation.
     */
    public function isOrganizationOwned(): bool;

    /**
     * Gets the name of the field is used to store the entity owner.
     */
    public function getOwnerFieldName(): string;

    /**
     * Gets the name of the database column is used to store the entity owner.
     */
    public function getOwnerColumnName(): string;

    /**
     * Gets the name of the field is used to store the entity organization.
     */
    public function getOrganizationFieldName(): string;

    /**
     * Gets the name of the database column is used to store the entity organization.
     */
    public function getOrganizationColumnName(): string;

    /**
     * Get list of allowed access level names.
     *
     * @return string[]
     */
    public function getAccessLevelNames(): array;
}
