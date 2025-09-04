<?php

namespace Oro\Bundle\SearchBundle\Api\Model;

/**
 * Represents an entity available for the search API resource.
 */
class SearchEntity
{
    private string $entityType;
    private string $entityName;
    private bool $searchable;
    private array $fields;

    public function __construct(string $entityType, string $entityName, bool $searchable, array $fields = [])
    {
        $this->entityType = $entityType;
        $this->entityName = $entityName;
        $this->searchable = $searchable;
        $this->fields = $fields;
    }

    /**
     * Gets an identifier of a record.
     */
    public function getId(): string
    {
        return $this->entityType;
    }

    /**
     * Gets an API alias of an entity.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Gets a localized name of an entity.
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * Indicates whether a searching by an entity is allowed for the current logged in user.
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Returns an array of fields that can be used during a search for an entity.
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
