<?php

namespace Oro\Bundle\MigrationBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\PostUpMigrationLifeCycleEvent;
use Oro\Bundle\MigrationBundle\Migration\ResetContainerMigration;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Reset container if migration implements ResetContainerMigration
 */
class ResetContainerMigrationListener implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Static flag to prevent recursive resets across multiple listener instances
     * This is necessary because container reset may recreate the listener
     */
    private static bool $isResetting = false;

    public function onPostUp(PostUpMigrationLifeCycleEvent $event)
    {
        if ($event->getMigration() instanceof ResetContainerMigration) {
            // Prevent recursive reset calls - use static flag to persist across instances
            if (self::$isResetting) {
                return;
            }

            self::$isResetting = true;
            try {
                $this->container->reset();
            } finally {
                self::$isResetting = false;
            }
        }
    }
}
