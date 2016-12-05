<?php

namespace Oro\Component\Layout;

/**
 * Provides a set of methods to manipulate the layout and apply the changes on demand
 *
 * NOTES: we have to re-declare all methods from {@see LayoutManipulatorInterface} here
 * because in other case "@return self" points to {@see LayoutManipulatorInterface}
 * rather than {@see DeferredLayoutManipulatorInterface}.
 * But it is important for a client code because this interface provides "fluent" operations.
 */
interface DeferredLayoutManipulatorInterface extends LayoutManipulatorInterface
{
    /**
     * Returns the number of added items
     *
     * @return int
     */
    public function getNumberOfAddedItems();

    /**
     * Applies all scheduled changes
     *
     * @param ContextInterface $context  The context
     * @param boolean          $finalize This flag determines whether the manipulator should check
     *                                   for all actions were executed.
     *                                   False means that all not executed actions should be kept.
     *                                   True means that not executed actions are the reason for an error.
     *
     * @throws Exception\DeferredUpdateFailureException if not all scheduled action have been performed
     */
    public function applyChanges(ContextInterface $context, $finalize = false);
}
