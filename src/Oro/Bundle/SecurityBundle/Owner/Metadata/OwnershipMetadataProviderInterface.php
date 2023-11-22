<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

/**
 * Interface for ownership metadata providers.
 */
interface OwnershipMetadataProviderInterface
{
    /**
     * Checks whether this provider can be used in the current security context.
     */
    public function supports(): bool;

    /**
     * Gets the ownership related metadata for the given entity.
     */
    public function getMetadata(?string $className): OwnershipMetadataInterface;

    /**
     * Gets the class name of the user entity.
     */
    public function getUserClass(): string;

    /**
     * Gets the class name of the business unit entity.
     */
    public function getBusinessUnitClass(): string;

    /**
     * Gets the class name of the organization entity.
     */
    public function getOrganizationClass(): ?string;

    /**
     * Gets the maximum access level this provider supports.
     */
    public function getMaxAccessLevel(int $accessLevel, string $className = null): int;

    /**
     * Clears the ownership metadata cache.
     * When the class name is specified this method clears cached data for this class only;
     * otherwise, this method clears all cached data.
     */
    public function clearCache(?string $className = null): void;

    /**
     * Warms up the cache.
     * When the class name is specified this method warms up cache for this class only;
     * otherwise, this method warms up cache for all classes.
     */
    public function warmUpCache(?string $className = null);
}
