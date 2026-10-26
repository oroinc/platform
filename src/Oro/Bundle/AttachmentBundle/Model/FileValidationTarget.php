<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Model;

/**
 * The entity and field whose upload restrictions apply to a file.
 */
class FileValidationTarget
{
    /**
     * @throws \InvalidArgumentException if the field name is specified without the entity class
     */
    public function __construct(
        public readonly string $entityClass,
        public readonly string $fieldName = ''
    ) {
        if ('' === $entityClass && '' !== $fieldName) {
            throw new \InvalidArgumentException('The field name cannot be specified without the entity class.');
        }
    }

    /**
     * Gets the target of a file whose parent entity is unknown, the system-wide restrictions apply to it.
     */
    public static function system(): self
    {
        return new self('');
    }

    public function isSystem(): bool
    {
        return '' === $this->entityClass;
    }

    /**
     * Gets the string that identifies the entity and field, two targets are the same when their keys are equal.
     */
    public function getKey(): string
    {
        return $this->entityClass . '|' . $this->fieldName;
    }
}
