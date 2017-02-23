<?php

namespace Oro\Component\Layout;

/**
 * Provides an interface for builders which can be used to build {@see Layout}.
 * In additional to LayoutManipulatorInterface allows to get built layout.
 */
interface LayoutBuilderInterface extends LayoutManipulatorInterface
{
    /**
     * Returns the layout object
     *
     * @param ContextInterface $context The context
     * @param string|null      $rootId  The id of root layout item
     *
     * @return Layout
     */
    public function getLayout(ContextInterface $context, $rootId = null);

    /**
     * Returns all actions which still not applied to layout
     *
     * @return array
     */
    public function getNotAppliedActions();
}
