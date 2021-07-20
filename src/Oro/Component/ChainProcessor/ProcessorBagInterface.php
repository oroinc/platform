<?php

namespace Oro\Component\ChainProcessor;

/**
 * Represents a read-only collection of processors.
 */
interface ProcessorBagInterface
{
    /**
     * Registers a processor applicable checker.
     */
    public function addApplicableChecker(ApplicableCheckerInterface $checker, int $priority = 0): void;

    /**
     * Gets an iterator that can be used to iterate through processors applicable to the given context.
     */
    public function getProcessors(ContextInterface $context): ProcessorIterator;

    /**
     * Gets all registered actions.
     *
     * @return string[]
     */
    public function getActions(): array;

    /**
     * Gets all groups registered for the given action.
     * The returned groups are sorted by its priority.
     *
     * @param string $action An action
     *
     * @return string[]
     */
    public function getActionGroups(string $action): array;
}
