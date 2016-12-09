<?php

namespace Oro\Bundle\NoteBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Oro\Bundle\NoteBundle\Migration\RemoveNoteConfigurationScopeMigration;

/**
 * When existing Oro application is updating to version 2.0 a migration is added
 * to remove outdated configuration scope from Note entity.
 *
 * @see \Oro\Bundle\NoteBundle\Migration\RemoveNoteConfigurationScopeMigration
 */
class RemoveNoteConfigurationScopeListener
{
    /**
     * @var bool
     */
    protected $isApplicable;

    /**
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        if ($this->isApplicable) {
            $event->addMigration(new RemoveNoteConfigurationScopeMigration());
        }
    }

    /**
     * @param PreMigrationEvent $event
     */
    public function onPreUp(PreMigrationEvent $event)
    {
        $version = $event->getLoadedVersion('OroNoteBundle');
        if ($version && version_compare($version, 'v1_3', '<')) {
            $this->isApplicable = true;
        }
    }
}
