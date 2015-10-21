<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\NavigationBundle\Content\TopicSender;

class DoctrineTagEventListener
{
    /** @var bool */
    protected $isApplicationInstalled;

    /** @var array */
    protected $skipTrackingFor = [];

    /** @var TopicSender */
    protected $sender;

    /** @var array */
    protected $collectedTags = [];

    /**
     * @param TopicSender      $sender
     * @param bool|string|null $isApplicationInstalled
     */
    public function __construct(TopicSender $sender, $isApplicationInstalled)
    {
        $this->sender                 = $sender;
        $this->isApplicationInstalled = !empty($isApplicationInstalled);
    }

    /**
     * Collect changes that were done
     * Generates tags and store in protected variable
     *
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        if (!$this->isApplicationInstalled) {
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
            if (!isset($this->skipTrackingFor[ClassUtils::getClass($entity)])) {
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
     *
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $this->sender->send($this->collectedTags);
        $this->collectedTags = [];
    }

    /**
     * Add this method call to service declaration in case when you need
     * to do not send update tags whenever your entity modified
     *
     * @param string $className The FQCN of an entity to be skipped
     *
     * @throws \InvalidArgumentException
     */
    public function markSkipped($className)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(
                sprintf('The class "%s" does not exist.', $className)
            );
        }

        $this->skipTrackingFor[$className] = true;
    }
}
