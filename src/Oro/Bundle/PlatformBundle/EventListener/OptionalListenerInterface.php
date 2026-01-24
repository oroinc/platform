<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

/**
 * Defines the contract for event listeners that can be conditionally enabled or disabled.
 *
 * Implementations of this interface can be toggled on or off at runtime, allowing the application
 * to selectively activate or deactivate event listeners based on configuration or runtime conditions.
 */
interface OptionalListenerInterface
{
    /**
     * Set if current listener is enabled
     *
     * @param bool $enabled
     */
    public function setEnabled($enabled = true);
}
