<?php

namespace Oro\Component\Layout\Form;

use Symfony\Component\Form\FormRendererInterface as BaseFormRendererInterface;
use Symfony\Component\Form\FormView;

/**
 * Extends Symfony's form renderer with layout-specific form rendering capabilities.
 *
 * This interface provides methods for searching and rendering form blocks by name suffix,
 * supporting recursive block searches and optional parent block rendering.
 */
interface FormRendererInterface extends BaseFormRendererInterface
{
    /**
     * Searches and renders a block for a given name suffix.
     *
     * The block is searched by combining the block names stored in the
     * form view with the given suffix. If a block name is found, that
     * block is rendered.
     *
     * If this method is called recursively, the block search is continued
     * where a block was found before.
     *
     * @param FormView $view              The view for which to render the block
     * @param string   $blockNameSuffix   The suffix of the block name
     * @param array    $variables         The variables to pass to the template
     * @param bool     $renderParentBlock Render parent block template
     *
     * @return string The HTML markup
     */
    public function searchAndRenderBlock(
        FormView $view,
        $blockNameSuffix,
        array $variables = [],
        $renderParentBlock = false
    ): string;
}
