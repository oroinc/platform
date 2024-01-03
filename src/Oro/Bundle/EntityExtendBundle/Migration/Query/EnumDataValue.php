<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Query;

/**
 * Represents an enum value used by {@see InsertEnumValuesQuery}.
 */
class EnumDataValue
{
    public function __construct(
        private string $id,
        private string $name,
        private int $priority,
        private bool $isDefault = false
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }
}
