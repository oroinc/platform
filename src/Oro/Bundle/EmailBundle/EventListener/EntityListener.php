<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;

class EntityListener
{
    /**
     * @var EmailOwnerManager
     */
    protected $emailOwnerManager;

    /**
     * @var EmailActivityManager
     */
    protected $emailActivityManager;

    public function __construct(
        EmailOwnerManager $emailOwnerManager,
        EmailActivityManager $emailActivityManager
    ) {
        $this->emailOwnerManager    = $emailOwnerManager;
        $this->emailActivityManager = $emailActivityManager;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $this->emailOwnerManager->handleOnFlush($event);
        $this->emailActivityManager->handleOnFlush($event);
    }
}
