<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides an interface that should be implemented by classes
 * that provides the configuration of groups and processors for the ProcessorBag.
 */
interface ProcessorBagConfigProviderInterface
{
    /**
     * Gets groups registered for all actions and have at least one processor.
     * The returned groups are sorted by its priority.
     *
     * @return array [action => [group, ...], ...]
     */
    public function getGroups();

    /**
     * Gets processors registered for all actions in the order they should be executed.
     *
     * @return array [action => [[processor id, [attribute name => attribute value, ...]], ...], ...]
     */
    public function getProcessors();
}
