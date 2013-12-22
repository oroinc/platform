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
            $entities[] = $collection->getOwner();
        }

        $this->collectedTags($entities);
    }

    /**
     * Send collected tags to publisher
     */
    public function postFlush()
    {
        $this->send($this->collectedTags);
    }

    /**
     * Publish tags in topic
     *
     * @param array $tags
     */
    protected function send(array $tags)
    {
        if (!empty($tags)) {
            $this->publisher->send(self::UPDATE_TOPIC, implode(self::TAG_DELIMITER, $tags));
        }
    }

    /**
     * Collect tags to protected property
     *
     * @param array $entities
     */
    protected function collectedTags(array $entities)
    {
        $generator = $this->generatorLink->getService();
        foreach ($entities as $entity) {
            if (!in_array(ClassUtils::getClass($entity), self::$skipTrackingFor)) {
                $this->collectedTags = array_merge($this->collectedTags, $generator->generate($entity, true));
            }
        }
    }
}
