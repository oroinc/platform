<?php

namespace Oro\Component\Layout;

interface LayoutBuilderInterface extends RawLayoutManipulatorInterface
{
    /**
     * Sets the theme(s) to be used for rendering a block and its children
     *
     * @param string|string[] $themes  The theme(s). For example 'MyBundle:Layout:my_theme.html.twig'
     * @param string|null     $blockId The id of a block to assign the theme(s) to
     */
    public function setBlockTheme($themes, $blockId = null);

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
