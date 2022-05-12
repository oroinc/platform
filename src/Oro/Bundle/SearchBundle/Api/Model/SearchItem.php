<?php

namespace Oro\Bundle\SearchBundle\Api\Model;

/**
 * Represents the search result.
 */
class SearchItem
{
    private string $id;
    private string $entityClass;
    private string $entityId;
    private ?string $entityName;
    private ?string $entityUrl;

    public function __construct(
        string $id,
        string $entityClass,
        string $entityId,
        ?string $entityName,
        ?string $entityUrl
    ) {
        $this->id = $id;
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->entityName = $entityName;
        $this->entityUrl = $entityUrl;
    }

    /**
     * Gets an identifier of a search record.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets FQCN of an entity associated with a search record.
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * Gets an identifier of an entity associated with a search record.
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * Gets a name of an entity associated with a search record.
     */
    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    /**
     * Gets URL of an entity associated with a search record.
     */
    public function getEntityUrl(): ?string
    {
        return $this->entityUrl;
    }
}
