<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\DataAuditBundle\Loggable\LoggableManager;
use Oro\Bundle\DataAuditBundle\Metadata\ExtendMetadataFactory;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;

class EntityListener implements OptionalListenerInterface
{
    /**
     * @var ExtendMetadataFactory
     */
    protected $metadataFactory;

    /**
     * @var LoggableManager
     */
    protected $loggableManager;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param LoggableManager       $loggableManager
     * @param ExtendMetadataFactory $metadataFactory
     */
    public function __construct(LoggableManager $loggableManager, ExtendMetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
        $this->loggableManager = $loggableManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

        $this->loggableManager->handleLoggable($event->getEntityManager());
    }

    /**
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        if ($event->getClassMetadata()->getReflectionClass()
            && $metadata = $this->metadataFactory->extendLoadMetadataForClass($event->getClassMetadata())
        ) {
            $this->loggableManager->addConfig($metadata);
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

        $this->loggableManager->handlePostPersist($event->getEntity(), $event->getEntityManager());
    }
}
