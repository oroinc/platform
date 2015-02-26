<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
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

    /**
     * @var EmailThreadManager
     */
    protected $emailThreadManager;

    public function __construct(
        EmailOwnerManager    $emailOwnerManager,
        EmailActivityManager $emailActivityManager,
        EmailThreadManager   $emailThreadManager
    ) {
        $this->emailOwnerManager    = $emailOwnerManager;
        $this->emailActivityManager = $emailActivityManager;
        $this->emailThreadManager   = $emailThreadManager;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $this->emailOwnerManager->handleOnFlush($event);
        $this->emailActivityManager->handleOnFlush($event);
        $this->emailThreadManager->handleOnFlush($event);
    }

    /**
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $this->emailThreadManager->handlePostFlush($event);
    }
}
