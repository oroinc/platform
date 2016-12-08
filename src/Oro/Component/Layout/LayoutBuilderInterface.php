<?php

namespace Oro\Component\Layout;

/**
 * Provides an interface for builders which can be used to build {@see Layout}.
 * In additional to LayoutManipulatorInterface allows to get built layout.
 *
 * NOTES: we have to re-declare all methods from {@see LayoutManipulatorInterface} here
 * because in other case "@return self" points to {@see LayoutManipulatorInterface}
 * rather than {@see LayoutBuilderInterface}.
 * But it is important for a client code because this interface provides "fluent" operations.
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
}
