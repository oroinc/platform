<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;

class EntityListener
{
    /**
     * @var EmailOwnerManager
     */
    protected $emailOwnerManager;

    public function __construct(EmailOwnerManager $emailOwnerManager)
    {
        $this->emailOwnerManager = $emailOwnerManager;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $this->emailOwnerManager->handleOnFlush($event);
    }
}
