<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\EventTriggerExtensionInterface;

class ProcessCollectorListener implements OptionalListenerInterface
{
    /** @var bool */
    protected $enabled = true;

    /** @var ArrayCollection|EventTriggerExtensionInterface[] */
    protected $extensions;

    /**
     * @param array|EventTriggerExtensionInterface[] $extensions
     */
    public function __construct(array $extensions = [])
    {
        $this->extensions = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param EventTriggerExtensionInterface $extension
     */
    public function addExtension(EventTriggerExtensionInterface $extension)
    {
        if ($this->extensions->contains($extension)) {
            $this->extensions->add($extension);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $this->schedule($args->getEntity(), EventTriggerInterface::EVENT_CREATE);
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $changeSet = $args->getEntityChangeSet();
        $fields = array_keys($changeSet);

        foreach ($fields as $field) {
            $changeSet[$field] = ['old' => $args->getOldValue($field), 'new' => $args->getNewValue($field)];
        }

        $this->schedule($args->getEntity(), EventTriggerInterface::EVENT_UPDATE, $changeSet);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $this->schedule($args->getEntity(), EventTriggerInterface::EVENT_DELETE);
    }

    /**
     * @param OnClearEventArgs $args
     */
    public function onClear(OnClearEventArgs $args)
    {
        $entityClass = $args->clearsAllEntities() ? null : $args->getEntityClass();
        foreach ($this->extensions as $extension) {
            $extension->clear($entityClass);
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        foreach ($this->extensions as $extension) {
            $extension->process($args->getEntityManager());
        }
    }

    /**
     * @param object $entity
     * @param string $event
     * @param array|null $changeSet
     */
    protected function schedule($entity, $event, array $changeSet = null)
    {
        foreach ($this->extensions as $extension) {
            if ($extension->hasTriggers($entity, $event)) {
                $extension->schedule($entity, $event, $changeSet);
            }
        }
    }
}
