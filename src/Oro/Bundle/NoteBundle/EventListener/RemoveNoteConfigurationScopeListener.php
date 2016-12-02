<?php

namespace Oro\Bundle\NoteBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Oro\Bundle\NoteBundle\Migration\RemoveNoteConfigurationScopeMigration;

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
        if ($version && version_compare($version, '1_3', '<')) {
            $this->isApplicable = true;
        }
    }
}
