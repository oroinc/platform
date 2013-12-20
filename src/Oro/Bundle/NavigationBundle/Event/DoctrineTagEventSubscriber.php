<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;

class DoctrineTagEventSubscriber implements EventSubscriber
{
    /** @var TopicPublisher */
    protected $publisher;

    /** @var bool */
    protected $isApplicationInstalled;

    public function __construct(TopicPublisher $publisher, $isApplicationInstalled)
    {
        $this->publisher              = $publisher;
        $this->isApplicationInstalled = $isApplicationInstalled;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [Events::onFlush];
    }

    /**
     * Collect changes that were done and notifies subscribers via websockets
     *
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        if ($this->isApplicationInstalled === false) {
            return;
        }

        $em  = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        $a = 1;
    }
}
