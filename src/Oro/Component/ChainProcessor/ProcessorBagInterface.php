<?php

namespace Oro\Component\ChainProcessor;

interface ProcessorBagInterface
{
    /**
     * Gets an iterator that can be used to iterate through processors applicable to the given context.
     *
     * @param ContextInterface $context
     *
     * @return ProcessorIterator
     */
    public function getProcessors(ContextInterface $context);

    /**
     * Gets all registered actions.
     *
     * @return string[]
     */
    public function getActions();

    /**
     * Gets all groups registered for the given action and have at least one processor.
     * The returned groups are sorted by its priority.
     *
     * @param string $action An action
     *
     * @return string[]
     */
    public function getActionGroups($action);
}
