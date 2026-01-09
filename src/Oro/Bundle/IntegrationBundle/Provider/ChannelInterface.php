<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

/**
 * Defines the contract for integration channel providers.
 *
 * Integration channel providers represent different types of external systems that can be
 * integrated with the application. Implementations of this interface provide metadata about
 * the integration type, such as its display label for the UI.
 */
interface ChannelInterface
{
    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel();
}
