<?php

namespace Oro\Component\Layout\Form\RendererEngine;

use Symfony\Component\Form\FormRendererEngineInterface as BaseFormRendererEngineInterfaceInterface;
use Symfony\Component\Form\FormView;

/**
 * {@inheritdoc}
 */
interface FormRendererEngineInterface extends BaseFormRendererEngineInterfaceInterface
{
    /**
     * Sets the theme(s) to be used for rendering a view and its children.
     *
     * @param string|string[] $themes The theme(s). The type of these themes
     * is open to the implementation.
     */
    public function addDefaultThemes($themes);

    /**
     * Switches to the next parent resource for a block hierarchy. This method
     * is invoked to find next parent resource when invoking "parent_block_widget"
     *
     * A block hierarchy is an array which starts with the root of the hierarchy
     * and continues with the child of that root, the child of that child etc.
     * The following is an example for a block hierarchy:
     *
     * <code>
     * form_widget
     * text_widget
     * url_widget
     * </code>
     *
     * In this example, "url_widget" is the most specific block, while the other
     * blocks are its ancestors in the hierarchy.
     *
     * The type of the resource is decided by the implementation. The resource
     * is later passed to {@link renderBlock()} by the rendering algorithm.
     *
     * @param FormView $view               The view for determining the used themes
     *                                     First the themes  attached directly to
     *                                     the view with {@link setTheme()} are
     *                                     considered, then the ones of its parent etc.
     * @param array    $blockNameHierarchy The block name hierarchy, with the root block
     *                                     at the beginning.
     *
     * @return mixed The renderer resource or false, if none was found
     */
    public function switchToNextParentResource(FormView $view, array $blockNameHierarchy);
}
