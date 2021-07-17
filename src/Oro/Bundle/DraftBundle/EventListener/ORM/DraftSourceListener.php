<?php

namespace Oro\Bundle\DraftBundle\EventListener\ORM;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;

/**
 * Add an association configuration to "draft source" property for draftable entities
 */
class DraftSourceListener
{
    /**
     * Entity field name
     */
    private const FIELD_NAME_ENTITY = 'draftSource';

    /**
     * Table field name
     */
    private const FIELD_NAME_TABLE = 'draft_source_id';

    /**
     * @var string
     */
    private $databaseDriver;

    /**
     * @param string $databaseDriver
     */
    public function __construct($databaseDriver)
    {
        $this->databaseDriver = $databaseDriver;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        if (!$this->isPlatformSupport()) {
            return;
        }

        $metadata = $event->getClassMetadata();
        if (!$this->isDraft($metadata->getReflectionClass())) {
            return;
        }

        if ($metadata->hasAssociation(self::FIELD_NAME_ENTITY)) {
            return;
        }

        $draftSourceMetadata = $this->getPropertyMetadata($metadata);
        $metadata->mapManyToOne($draftSourceMetadata);
    }

    private function getPropertyMetadata(ClassMetadataInfo $metadata): array
    {
        $className = $metadata->getName();
        $entityIdentifier = $this->getEntityIdentifier($metadata);

        return [
            'joinColumns' => [[
                'name' => self::FIELD_NAME_TABLE,
                'nullable' => true,
                'onDelete' => 'CASCADE',
                'columnDefinition' => null,
                'referencedColumnName' => $entityIdentifier
            ]],
            'isOwningSide' => true,
            'fieldName' => self::FIELD_NAME_ENTITY,
            'targetEntity' => $className,
            'sourceEntity' => $className,
            'fetch' => ClassMetadataInfo::FETCH_LAZY,
            'type' => ClassMetadataInfo::MANY_TO_ONE,
            'sourceToTargetKeyColumns' => [self::FIELD_NAME_TABLE => $entityIdentifier],
            'joinColumnFieldNames' => [self::FIELD_NAME_TABLE => self::FIELD_NAME_TABLE],
            'targetToSourceKeyColumns' => [$entityIdentifier => self::FIELD_NAME_TABLE]
        ];
    }

    private function getEntityIdentifier(ClassMetadataInfo $metadata): string
    {
        $identifier = $metadata->getIdentifier();

        return reset($identifier);
    }

    private function isPlatformSupport(): bool
    {
        $drivers = [DatabaseDriverInterface::DRIVER_MYSQL, DatabaseDriverInterface::DRIVER_POSTGRESQL];

        return in_array($this->databaseDriver, $drivers);
    }

    private function isDraft(\ReflectionClass $class): bool
    {
        return in_array(DraftableInterface::class, $class->getInterfaceNames());
    }
}
