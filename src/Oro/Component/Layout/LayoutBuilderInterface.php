<?php

namespace Oro\Component\Layout;

interface LayoutBuilderInterface extends RawLayoutManipulatorInterface
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
