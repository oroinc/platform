<?php

namespace Oro\Bundle\EntityBundle\Event;

use Symfony\Bridge\Doctrine\ContainerAwareEventManager;

use Doctrine\Common\EventArgs;

class OroEventManager extends ContainerAwareEventManager
{
    /**
     * @var bool
     */
    protected $enabled = true;

    public function enable()
    {
        $this->enabled = true;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Dispatch events only when manager is enabled
     *
     * @param string $eventName
     * @param EventArgs $eventArgs
     * @return null
     */
    public function dispatchEvent($eventName, EventArgs $eventArgs = null)
    {
        if (!$this->enabled) {
            return;
        }

        parent::dispatchEvent($eventName, $eventArgs);
    }
}
