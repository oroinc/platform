<?php

namespace Oro\Bundle\DataAuditBundle\Model;

/**
 * A Data Audit entry a bundle builds itself, for a change that is not a change of an auditable entity: a
 * system configuration setting, a menu.
 */
class AuditEntry
{
    private array $changes = [];

    public function __construct(
        private readonly string $objectClass,
        private readonly string $objectId,
        private readonly string $objectName,
        private readonly string $action
    ) {
    }

    public function addChange(
        string $field,
        mixed $old,
        mixed $new,
        string $type = AuditFieldTypeRegistry::TYPE_TEXT
    ): static {
        $this->changes[$field] = ['field' => $field, 'type' => $type, 'old' => $old, 'new' => $new];

        return $this;
    }

    public function getObjectClass(): string
    {
        return $this->objectClass;
    }

    public function getObjectId(): string
    {
        return $this->objectId;
    }

    public function getObjectName(): string
    {
        return $this->objectName;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return array<string, array{field: string, type: string, old: mixed, new: mixed}>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    public function hasChanges(): bool
    {
        return [] !== $this->changes;
    }
}
