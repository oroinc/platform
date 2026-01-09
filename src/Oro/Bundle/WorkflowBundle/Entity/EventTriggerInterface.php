<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

/**
 * Defines the contract for event trigger entities.
 *
 * Event triggers are used to automatically initiate workflow transitions based on entity
 * lifecycle events (create, update, delete) and field changes, enabling event-driven
 * workflow automation.
 */
interface EventTriggerInterface
{
    public const EVENT_CREATE = 'create';
    public const EVENT_UPDATE = 'update';
    public const EVENT_DELETE = 'delete';

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getEntityClass();

    /**
     * @return string
     */
    public function getEvent();

    /**
     * @return string
     */
    public function getField();
}
