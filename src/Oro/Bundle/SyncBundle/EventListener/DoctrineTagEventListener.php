<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Collects changes in entities and sends tags to websocket server.
 */
class DoctrineTagEventListener implements OptionalListenerInterface, ServiceSubscriberInterface
{
    use OptionalListenerTrait;

    private ContainerInterface $container;
    private ApplicationState $applicationState;
    private array $skipTrackingFor = [];
    private array $collectedTags = [];
    private array $processedEntities = [];

    public function __construct(ContainerInterface $container, ApplicationState $applicationState)
    {
        $this->container = $container;
        $this->applicationState = $applicationState;
    }

    /**
     * Collects changes that were done, generates and stores tags in memory.
     */
    public function onFlush(OnFlushEventArgs $event): void
    {
        if (!$this->enabled || !$this->applicationState->isInstalled()) {
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
     * Sends collected tags to websocket server.
     */
    public function postFlush(): void
    {
        if (!$this->enabled || !$this->applicationState->isInstalled()) {
            return;
        }
        if (!$this->collectedTags) {
            return;
        }

        $collectedTags = array_unique($this->collectedTags);
        $this->collectedTags = [];
        $this->processedEntities = [];

        $this->getDataUpdateTopicSender()->send($collectedTags);
    }

    /**
     * Add this method call to service declaration in case when you need
     * to do not send update tags whenever your entity modified
     *
     * @param string $className The FQCN of an entity to be skipped
     */
    public function markSkipped(string $className): void
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('The class "%s" does not exist.', $className));
        }

        $this->skipTrackingFor[$className] = true;
    }

    private function addEntityTags(object $entity, bool $includeCollectionTag = false): void
    {
        $hash = spl_object_hash($entity);
        if (!isset($this->processedEntities[$hash])
            && !isset($this->skipTrackingFor[ClassUtils::getClass($entity)])
        ) {
            $this->collectedTags = array_merge(
                $this->collectedTags,
                $this->getTagGenerator()->generate($entity, $includeCollectionTag)
            );
            $this->processedEntities[$hash] = true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_sync.content.tag_generator' => TagGeneratorInterface::class,
            'oro_sync.content.data_update_topic_sender' => DataUpdateTopicSender::class
        ];
    }

    private function getTagGenerator(): TagGeneratorInterface
    {
        return $this->container->get('oro_sync.content.tag_generator');
    }

    private function getDataUpdateTopicSender(): DataUpdateTopicSender
    {
        return $this->container->get('oro_sync.content.data_update_topic_sender');
    }
}
