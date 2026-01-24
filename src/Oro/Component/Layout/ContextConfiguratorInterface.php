<?php

namespace Oro\Component\Layout;

/**
 * Defines the contract for configuring the layout context.
 *
 * Implementations of this interface configure the layout context by setting up context variables,
 * resolvers, and other context-related settings needed for layout building.
 */
interface ContextConfiguratorInterface
{
    /**
     * Configures the layout context.
     *
     * @param ContextInterface $context The context
     */
    public function configureContext(ContextInterface $context);
}
