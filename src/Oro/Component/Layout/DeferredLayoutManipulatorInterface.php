<?php

namespace Oro\Component\Layout;

/**
 * Provides a set of methods to manipulate the layout and apply the changes on demand
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
     */
    public function applyChanges(ContextInterface $context, $finalize = false);

    /**
     * @return array
     */
    public function getNotAppliedActions();
}
