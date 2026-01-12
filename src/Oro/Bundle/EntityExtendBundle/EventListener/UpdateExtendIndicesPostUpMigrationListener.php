<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendIndicesMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

/**
 * Handles post-migration updates for extended entity indices.
 *
 * This listener is triggered after database migrations are applied. It schedules a migration
 * to update database indices for extended entities based on their field type configurations,
 * ensuring that appropriate indices are created for searchable and indexed fields.
 */
class UpdateExtendIndicesPostUpMigrationListener
{
    /** @var EntityMetadataHelper */
    protected $entityMetadataHelper;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        FieldTypeHelper $fieldTypeHelper
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->fieldTypeHelper      = $fieldTypeHelper;
    }

    /**
     * POST UP event handler
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateExtendIndicesMigration(
                $this->entityMetadataHelper,
                $this->fieldTypeHelper
            )
        );
    }
}
