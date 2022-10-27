<?php

namespace Oro\Bundle\WorkflowBundle\EventListener\Extension;

use Doctrine\Persistence\ObjectManager;

interface EventTriggerExtensionInterface
{
    /**
     * @param bool $forceQueued
     */
    public function setForceQueued($forceQueued = false);

    /**
     * @param object $entity
     * @param string $event
     * @return bool
     */
    public function hasTriggers($entity, $event);

    /**
     * @param object $entity
     * @param string $event
     * @param array|null $changeSet
     */
    public function schedule($entity, $event, array $changeSet = null);

    public function process(ObjectManager $manager);

    /**
     * @param string|null $entityClass
     */
    public function clear($entityClass = null);
}
