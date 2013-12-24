<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\NavigationBundle\Content\TopicSender;

class DoctrineTagEventSubscriber implements EventSubscriber
{
    /** @var bool */
    protected $isApplicationInstalled;

    /** @var array */
    protected $skipTrackingFor = [];

    /** @var TopicSender */
    protected $sender;

    /** @var array */
    protected $collectedTags = [];

    public function __construct(TopicSender $sender, $isApplicationInstalled)
    {
        $this->sender                 = $sender;
        $this->isApplicationInstalled = $isApplicationInstalled;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [Events::onFlush, Events::postFlush];
    }

    /**
     * Collect changes that were done
     * Generates tags and store in protected variable
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

        $entities = array_merge(
            $uow->getScheduledEntityDeletions(),
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        $collections = array_merge($uow->getScheduledCollectionUpdates(), $uow->getScheduledCollectionDeletions());
        foreach ($collections as $collection) {
            $owner = $collection->getOwner();
            if (!in_array($owner, $entities, true)) {
                $entities[] = $owner;
            }
        }

        $generator = $this->sender->getGenerator();
        foreach ($entities as $entity) {
            if (!in_array(ClassUtils::getClass($entity), $this->skipTrackingFor)) {
                // invalidate collection view pages only when entity has been added or removed
                $includeCollectionTag = $uow->isScheduledForInsert($entity)
                    || $uow->isScheduledForDelete($entity);

                $this->collectedTags = array_merge(
                    $this->collectedTags,
                    $generator->generate($entity, $includeCollectionTag)
                );
            }
        }

        $this->collectedTags = array_unique($this->collectedTags);
    }

    /**
     * Send collected tags to publisher
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $this->sender->send($this->collectedTags);
    }

    /**
     * Add this method call to service declaration in case when you need
     * to do not send update tags whenever your entity modified
     *
     * @param string $entityFQCN
     *
     * @throws \LogicException
     */
    public function markSkipped($entityFQCN)
    {
        if (is_string($entityFQCN) && class_exists($entityFQCN)) {
            $this->skipTrackingFor[] = $entityFQCN;
        } else {
            throw new \LogicException('Invalid entity class name given');
        }
    }
}
