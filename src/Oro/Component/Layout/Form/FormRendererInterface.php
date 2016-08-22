<?php
namespace Oro\Component\Layout\Form;

use Symfony\Component\Form\FormRendererInterface as BaseFormRendererInterface;
use Symfony\Component\Form\FormView;

/**
 * {@inheritdoc}
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
    );
}
