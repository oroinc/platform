<?php

namespace Oro\Bundle\MigrationBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\PostUpMigrationLifeCycleEvent;
use Oro\Bundle\MigrationBundle\Migration\ResetContainerMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Reset container if migration implements ResetContainerMigration
 */
class ResetContainerMigrationListener implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function onPostUp(PostUpMigrationLifeCycleEvent $event)
    {
        if ($event->getMigration() instanceof ResetContainerMigration) {
            $this->container->reset();
        }
    }
}
