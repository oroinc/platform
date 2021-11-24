<?php

namespace Oro\Bundle\SearchBundle\Api\Model;

/**
 * The model for the search API resource.
 */
class SearchItem
{
    private string $id;
    private string $entityClass;
    private string $entityId;
    private ?string $entityTitle;
    private ?string $entityUrl;

    public function __construct(
        string $id,
        string $entityClass,
        string $entityId,
        ?string $entityTitle,
        ?string $entityUrl
    ) {
        $this->id = $id;
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->entityTitle = $entityTitle;
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
     * Gets a title of an entity associated with a search record.
     */
    public function getEntityTitle(): ?string
    {
        return $this->entityTitle;
    }

    /**
     * Gets URL of an entity associated with a search record.
     */
    public function getEntityUrl(): ?string
    {
        return $this->entityUrl;
    }
}
