<?php

namespace Oro\Bundle\ScopeBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Oro\Bundle\ScopeBundle\Migration\AddCommentToRoHashManager;
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
    /**
     * @var AddCommentToRoHashManager
     */
    protected $manager;

    /**
     * @var bool
     */
    protected $installed;

    public function __construct(AddCommentToRoHashManager $manager, ?string $installed)
    {
        $this->manager = $manager;
        $this->installed = (bool)$installed;
    }

    /**
     * Added unique index for oro_scope table
     */
    public function onPostUp(PostMigrationEvent $event): void
    {
        // After installing the application created trigger and fill row_hash
        if (!$this->installed) {
            $event->addMigration(new AddTriggerToRowHashColumn($this->manager));
            return;
        }

        $event->addMigration(new UpdateScopeRowHashColumn($this->manager));
    }
}
