<?php

namespace Oro\Bundle\DraftBundle\EventListener\ORM;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;

/**
 * Adds an association configuration to "draft source" property for draftable entities.
 */
class DraftSourceListener
{
    private const FIELD_NAME = 'draftSource';
    private const COLUMN_NAME = 'draft_source_id';

    private string $databaseDriver;

    public function __construct(string $databaseDriver)
    {
        $this->databaseDriver = $databaseDriver;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        if (!$this->isPlatformSupported()) {
            return;
        }

        $metadata = $event->getClassMetadata();
        if (!is_subclass_of($metadata->getName(), DraftableInterface::class)) {
            return;
        }

        if ($metadata->hasAssociation(self::FIELD_NAME)) {
            return;
        }

        $metadata->mapManyToOne($this->getPropertyMetadata($metadata));
    }

    private function getPropertyMetadata(ClassMetadataInfo $metadata): array
    {
        $className = $metadata->getName();
        $entityIdentifier = $this->getEntityIdentifier($metadata);

        return [
            'joinColumns' => [[
                'name' => self::COLUMN_NAME,
                'nullable' => true,
                'onDelete' => 'CASCADE',
                'columnDefinition' => null,
                'referencedColumnName' => $entityIdentifier
            ]],
            'isOwningSide' => true,
            'fieldName' => self::FIELD_NAME,
            'targetEntity' => $className,
            'sourceEntity' => $className,
            'fetch' => ClassMetadataInfo::FETCH_LAZY,
            'type' => ClassMetadataInfo::MANY_TO_ONE,
            'sourceToTargetKeyColumns' => [self::COLUMN_NAME => $entityIdentifier],
            'joinColumnFieldNames' => [self::COLUMN_NAME => self::COLUMN_NAME],
            'targetToSourceKeyColumns' => [$entityIdentifier => self::COLUMN_NAME]
        ];
    }

    private function getEntityIdentifier(ClassMetadataInfo $metadata): string
    {
        $identifier = $metadata->getIdentifier();

        return reset($identifier);
    }

    private function isPlatformSupported(): bool
    {
        return
            DatabaseDriverInterface::DRIVER_POSTGRESQL === $this->databaseDriver
            || DatabaseDriverInterface::DRIVER_MYSQL === $this->databaseDriver;
    }
}
