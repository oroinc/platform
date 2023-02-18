<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Represents a collection of API sub-resources for a specific entity.
 */
class ApiResourceSubresources
{
    private string $entityClass;
    /** @var ApiSubresource[] */
    private array $subresources = [];

    public function __construct(string $entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * Gets the class name of the entity.
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * Checks if at least one sub-resource exists in this collection.
     */
    public function hasSubresources(): bool
    {
        return !empty($this->subresources);
    }

    /**
     * Gets a list of all sub-resources.
     *
     * @return ApiSubresource[] [association name => ApiSubresource, ...]
     */
    public function getSubresources(): array
    {
        return $this->subresources;
    }

    /**
     * Gets a sub-resource.
     */
    public function getSubresource(string $associationName): ?ApiSubresource
    {
        return $this->subresources[$associationName] ?? null;
    }

    /**
     * Adds a sub-resource.
     */
    public function addSubresource(string $associationName, ApiSubresource $subresource = null): ApiSubresource
    {
        if (null === $subresource) {
            $subresource = new ApiSubresource();
        }
        $this->subresources[$associationName] = $subresource;

        return $subresource;
    }

    /**
     * Removes a sub-resource.
     */
    public function removeSubresource(string $associationName): void
    {
        unset($this->subresources[$associationName]);
    }
}
