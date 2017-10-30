<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * @property array $items
 */
trait FormEventSubscriberTrait
{
    /**
     * Gets the form event subscribers.
     *
     * @return string[]|null Each element in the array is the name of a service implements EventSubscriberInterface
     */
    public function getFormEventSubscribers()
    {
        if (!array_key_exists(EntityDefinitionConfig::FORM_EVENT_SUBSCRIBER, $this->items)) {
            return null;
        }

        return $this->items[EntityDefinitionConfig::FORM_EVENT_SUBSCRIBER];
    }

    /**
     * Sets the form event subscribers.
     *
     * @param string[]|null $eventSubscribers Each element in the array should be
     *                                        the name of a service implements EventSubscriberInterface
     */
    public function setFormEventSubscribers(array $eventSubscribers = null)
    {
        if (empty($eventSubscribers)) {
            unset($this->items[EntityDefinitionConfig::FORM_EVENT_SUBSCRIBER]);
        } else {
            $this->items[EntityDefinitionConfig::FORM_EVENT_SUBSCRIBER] = $eventSubscribers;
        }
    }

    /**
     * Adds the form event subscriber.
     *
     * @param string $eventSubscriber The name of a service implements EventSubscriberInterface
     */
    public function addFormEventSubscriber($eventSubscriber)
    {
        $eventSubscribers = $this->getFormEventSubscribers();
        $eventSubscribers[] = $eventSubscriber;
        $this->setFormEventSubscribers($eventSubscribers);
    }
}
