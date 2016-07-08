<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Oro\Bundle\WorkflowBundle\Migrations\Schema\UpdateWorkflowItemFieldsMigration;

class PostUpMigrationListener
{
    /**
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(new UpdateWorkflowItemFieldsMigration(), true);
    }
}
