<?php

namespace Oro\Bundle\UIBundle\Provider;

/**
 * The interface for providers that can be used to get different kind of widgets
 */
interface WidgetProviderInterface
{
    /**
     * Determines whether this provider is applicable for the given object
     *
     * @param object $object
     *
     * @return bool TRUE if the provider supports the given object; otherwise, FALSE
     */
    public function supports($object);

    /**
     * Returns widgets
     *
     * @param object $object
     *
     * @return array
     */
    public function getWidgets($object);
}
