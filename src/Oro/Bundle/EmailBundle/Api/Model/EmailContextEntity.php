<?php

namespace Oro\Bundle\EmailBundle\Api\Model;

/**
 * Represents an entity available for the email context API resources.
 */
class EmailContextEntity
{
    private string $entityType;
    private string $entityName;
    private bool $allowed;

    public function __construct(string $entityType, string $entityName, bool $allowed)
    {
        $this->entityType = $entityType;
        $this->entityName = $entityName;
        $this->allowed = $allowed;
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
     * Indicates whether a an entity is allowed to be used in email context API resources
     * for the current logged in user.
     */
    public function isAllowed(): bool
    {
        return $this->allowed;
    }
}
