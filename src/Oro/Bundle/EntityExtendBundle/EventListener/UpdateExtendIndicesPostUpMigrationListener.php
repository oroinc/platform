<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendIndicesMigration;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class UpdateExtendIndicesPostUpMigrationListener
{
    /** @var EntityMetadataHelper */
    protected $entityMetadataHelper;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;


    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param FieldTypeHelper      $fieldTypeHelper
     * @param CommandExecutor      $commandExecutor
     * @param LoggerInterface      $logger
     */
    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        FieldTypeHelper $fieldTypeHelper,
        CommandExecutor $commandExecutor,
        LoggerInterface $logger
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->fieldTypeHelper      = $fieldTypeHelper;
        $this->commandExecutor      = $commandExecutor;
        $this->logger               = $logger;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateExtendIndicesMigration(
                $this->entityMetadataHelper,
                $this->fieldTypeHelper,
                $this->commandExecutor,
                $this->logger
            )
        );
    }
}
