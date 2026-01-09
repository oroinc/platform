<?php

namespace Oro\Bundle\WorkflowBundle\EventListener\Extension;

use Doctrine\Persistence\ObjectManager;

/**
 * Defines the contract for event trigger extensions that manage workflow trigger scheduling and processing.
 *
 * Implementations handle the scheduling of workflow triggers based on entity lifecycle events,
 * checking for applicable triggers, and processing scheduled triggers through the object manager.
 */
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
    public function schedule($entity, $event, ?array $changeSet = null);

    public function process(ObjectManager $manager);

    /**
     * @param string|null $entityClass
     */
    public function clear($entityClass = null);
}
