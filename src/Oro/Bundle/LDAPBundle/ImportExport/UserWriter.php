<?php
namespace Oro\Bundle\LDAPBundle\ImportExport;

use Doctrine\Common\EventManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;
use Oro\Bundle\LDAPBundle\EventListener\UserChangeListener;

class UserWriter extends EntityWriter
{
    /**
     * Initializes user writer.
     * Removes UserChangeListener, so it won't trigger for each imported user.
     */
    public function initialize()
    {
        $manager = $this->entityManager->getEventManager();
        $eventListeners = $manager->getListeners();

        $userChangeListener = false;
        foreach ($eventListeners as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof UserChangeListener) {
                    $userChangeListener = $listener;
                    break 2;
                }
            }
        }

        if ($userChangeListener !== false) {
            $manager->removeEventListener(['onFlush', 'postFlush'], $userChangeListener);
        }
    }
}