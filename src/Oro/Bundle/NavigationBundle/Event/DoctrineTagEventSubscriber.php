<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class DoctrineTagEventSubscriber implements EventSubscriber
{
    const UPDATE_TOPIC  = 'oro/data/update';
    const TAG_DELIMITER = ',';

    /** @var TopicPublisher */
    protected $publisher;

    /** @var bool */
    protected $isApplicationInstalled;

    /** @var ServiceLink */
    protected $generatorLink;

    /** @var array */
    protected static $skipTrackingFor = [
        'Oro\Bundle\DataAuditBundle\Entity\Audit',
        'Oro\Bundle\DataAuditBundle\Entity\AuditData',
        'Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem'
    ];

    /** @var array */
    protected $collectedTags = [];

    public function __construct(TopicPublisher $publisher, ServiceLink $generatorLink, $isApplicationInstalled)
    {
        $this->publisher              = $publisher;
        $this->generatorLink          = $generatorLink;
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

        $generator = $this->generatorLink->getService();
        foreach ($entities as $entity) {
            if (!in_array(ClassUtils::getClass($entity), self::$skipTrackingFor)) {
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
    public function postFlush()
    {
        if (!empty($this->collectedTags)) {
            $this->publisher->send(self::UPDATE_TOPIC, implode(self::TAG_DELIMITER, $this->collectedTags));
        }
    }
}
