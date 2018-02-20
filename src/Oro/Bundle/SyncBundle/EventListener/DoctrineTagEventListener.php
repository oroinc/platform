<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\SyncBundle\Content\TopicSender;

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

    /** @var array */
    private $processedEntities = [];

    /**
     * @param TopicSender      $sender
     * @param bool|string|null $isApplicationInstalled
     */
    public function __construct(TopicSender $sender, $isApplicationInstalled)
    {
        $this->sender = $sender;
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

        $uow = $event->getEntityManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->addEntityTags($entity, true);
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->addEntityTags($entity, true);
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->addEntityTags($entity);
        }
        foreach ($uow->getScheduledCollectionDeletions() as $collection) {
            $this->addEntityTags($collection->getOwner());
        }
        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            $this->addEntityTags($collection->getOwner());
        }
    }

    /**
     * Send collected tags to publisher
     *
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $this->sender->send(array_unique($this->collectedTags));
        $this->collectedTags = [];
        $this->processedEntities = [];
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

    /**
     * @param object $entity
     * @param bool   $includeCollectionTag
     */
    private function addEntityTags($entity, $includeCollectionTag = false)
    {
        $hash = spl_object_hash($entity);
        if (!isset($this->processedEntities[$hash])
            && !isset($this->skipTrackingFor[ClassUtils::getClass($entity)])
        ) {
            $this->collectedTags = array_merge(
                $this->collectedTags,
                $this->sender->getGenerator()->generate($entity, $includeCollectionTag)
            );
            $this->processedEntities[$hash] = true;
        }
    }
}
