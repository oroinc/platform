<?php

namespace Oro\Bundle\NotificationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched for notification processing of entities.
 *
 * This event carries an entity object that triggers notification workflows. Listeners
 * can subscribe to this event to perform notification-related actions such as sending
 * emails, creating alerts, or logging activities when entities are created, updated,
 * or deleted. The event allows both reading and modifying the entity through getter
 * and setter methods.
 */
class NotificationEvent extends Event
{
    /**
     * @var object
     */
    protected $entity;

    /**
     * @param object $entity
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Set entity
     *
     * @param object $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
