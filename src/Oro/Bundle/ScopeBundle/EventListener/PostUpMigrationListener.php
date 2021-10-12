<?php

namespace Oro\Bundle\ScopeBundle\EventListener;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Oro\Bundle\ScopeBundle\Migration\AddCommentToRowHashManager;
use Oro\Bundle\ScopeBundle\Migration\Schema\AddTriggerToRowHashColumn;
use Oro\Bundle\ScopeBundle\Migration\Schema\UpdateScopeRowHashColumn;

/**
 * Listener for executing migration which after install or update application
 * added or update trigger for oro_scope table.
 * This trigger combines all scope relations and create md5 row_hash from them.
 * Row_hash column is unique.
 */
class PostUpMigrationListener
{
    protected AddCommentToRowHashManager $manager;

    private ApplicationState $applicationState;

    public function __construct(AddCommentToRowHashManager $manager, ApplicationState $applicationState)
    {
        $this->manager = $manager;
        $this->applicationState = $applicationState;
    }

    /**
     * Added unique index for oro_scope table
     */
    public function onPostUp(PostMigrationEvent $event): void
    {
        // After installing the application created trigger and fill row_hash
        if (!$this->applicationState->isInstalled()) {
            $event->addMigration(new AddTriggerToRowHashColumn($this->manager));
            return;
        }

        $event->addMigration(new UpdateScopeRowHashColumn($this->manager));
    }
}
