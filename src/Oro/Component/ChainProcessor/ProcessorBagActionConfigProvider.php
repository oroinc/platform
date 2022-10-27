<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides the ProcessorBag configuration for a specific action.
 */
class ProcessorBagActionConfigProvider
{
    /** @var string[] */
    private $groups;

    /** @var array [[processor id, [attribute name => attribute value, ...]], ...] */
    private $processors;

    /**
     * @param string[] $groups
     * @param array    $processors [[processor id, [attribute name => attribute value, ...]], ...]
     */
    public function __construct(array $groups, array $processors)
    {
        $this->groups = $groups;
        $this->processors = $processors;
    }

    /**
     * Gets groups registered for an action and have at least one processor.
     * The returned groups are sorted by its priority.
     *
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Gets processors registered for an action in the order they should be executed.
     *
     * @return array [[processor id, [attribute name => attribute value, ...]], ...]
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }
}
