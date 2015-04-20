<?php

namespace Oro\Component\Layout;

interface ContextConfiguratorInterface
{
    /**
     * Configures the layout context.
     *
     * @param ContextInterface $context The context
     */
    public function configureContext(ContextInterface $context);
}
