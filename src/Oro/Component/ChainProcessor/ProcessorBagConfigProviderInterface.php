<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides an interface that should be implemented by classes
 * that provides the configuration of groups and processors for the ProcessorBag.
 */
interface ProcessorBagConfigProviderInterface
{
    /**
     * Gets names of all actions that have at least one processor.
     *
     * @return string[]
     */
    public function getActions(): array;

    /**
     * Gets groups registered for the given action and have at least one processor.
     * The returned groups are sorted by its priority.
     *
     * @param string $action
     *
     * @return string[]
     */
    public function getGroups(string $action): array;

    /**
     * Gets processors registered for the given action in the order they should be executed.
     *
     * @param string $action
     *
     * @return array [[processor id, [attribute name => attribute value, ...]], ...]
     */
    public function getProcessors(string $action): array;
}
