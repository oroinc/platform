<?php

namespace Oro\Bundle\UIBundle\Provider;

/**
 * The interface for providers that can be used to get different kind of widgets
 */
interface WidgetProviderInterface
{
    /**
     * Determines whether this provider is applicable for the given entity
     *
     * @param object $entity The entity object
     *
     * @return bool TRUE if the provider supports the given entity; otherwise, FALSE
     */
    public function supports($entity);

    /**
     * Returns widgets
     *
     * @param object $entity The entity object
     *
     * @return array
     */
    public function getWidgets($entity);
}
