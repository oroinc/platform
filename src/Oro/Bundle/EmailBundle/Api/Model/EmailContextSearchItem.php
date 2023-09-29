<?php

namespace Oro\Bundle\EmailBundle\Api\Model;

/**
 * Represents the email context search result item.
 */
class EmailContextSearchItem
{
    private string $id;
    private string $entityClass;
    private mixed $entityId;
    private ?string $entityName;
    private ?string $entityUrl;

    public function __construct(
        string $id,
        string $entityClass,
        mixed $entityId,
        ?string $entityName,
        ?string $entityUrl
    ) {
        $this->id = $id;
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->entityName = $entityName;
        $this->entityUrl = $entityUrl;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): mixed
    {
        return $this->entityId;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function getEntityUrl(): ?string
    {
        return $this->entityUrl;
    }
}
