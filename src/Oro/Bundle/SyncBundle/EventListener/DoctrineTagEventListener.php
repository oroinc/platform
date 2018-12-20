<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;

/**
 * Collects changes in entities and sends tags to websocket server using DataUpdateTopicSender.
 */
class DoctrineTagEventListener
{
    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @var bool
     */
    private $isApplicationInstalled;

    /**
     * @var array
     */
    private $skipTrackingFor = [];

    /**
     * @var DataUpdateTopicSender
     */
    private $dataUpdateTopicSender;

    /**
     * @var array
     */
    private $collectedTags = [];

    /**
     * @var array
     */
    private $processedEntities = [];

    /**
     * @var TagGeneratorInterface
     */
    private $tagGenerator;

    /**
     * @param DataUpdateTopicSender $dataUpdateTopicSender
     * @param TagGeneratorInterface $tagGenerator
     * @param bool|string|null      $isApplicationInstalled
     */
    public function __construct(
        DataUpdateTopicSender $dataUpdateTopicSender,
        TagGeneratorInterface $tagGenerator,
        $isApplicationInstalled
    ) {
        $this->dataUpdateTopicSender = $dataUpdateTopicSender;
        $this->isApplicationInstalled = !empty($isApplicationInstalled);
        $this->tagGenerator = $tagGenerator;
    }

    /**
     * Enables or disables the listener.
     *
     * @param bool $enabled
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * Collect changes that were done
     * Generates tags and store in protected variable
     *
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        if (!$this->enabled || !$this->isApplicationInstalled) {
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
        $this->dataUpdateTopicSender->send(array_unique($this->collectedTags));
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
                $this->tagGenerator->generate($entity, $includeCollectionTag)
            );
            $this->processedEntities[$hash] = true;
        }
    }
}
