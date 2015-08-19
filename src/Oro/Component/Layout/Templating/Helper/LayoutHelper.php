<?php

namespace Oro\Component\Layout\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\Form\FormRendererInterface;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Templating\TextHelper;

/**
 * LayoutHelper provides helpers to help display layout blocks
 */
class LayoutHelper extends Helper
{
    /** @var FormRendererInterface */
    private $renderer;

    /** @var TextHelper */
    private $textHelper;

    /**
     * @param FormRendererInterface $renderer
     * @param TextHelper            $textHelper
     */
    public function __construct(FormRendererInterface $renderer, TextHelper $textHelper)
    {
        $this->renderer   = $renderer;
        $this->textHelper = $textHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'layout';
    }

    /**
     * Sets the theme(s) to be used for rendering a view and its children
     *
     * The theme format is "<Bundle>:<Controller>".
     *
     * @param BlockView       $view   The view to assign the theme(s) to
     * @param string|string[] $themes The theme(s). For example 'MuBundle:Layout/php'
     */
    public function setBlockTheme(BlockView $view, $themes)
    {
        $this->renderer->setTheme($view, $themes);
    }

    /**
     * Renders the HTML for a given view
     *
     * Example usage:
     *     <?php echo $view['block']->widget($block) ?>
     *     <?php echo $view['block']->widget($block, array('attr' => array('class' => 'foo'))) ?>
     *
     * @param BlockView $view      The view for which to render the widget
     * @param array     $variables Additional variables passed to the template
     *
     * @return string
     */
    public function widget(BlockView $view, array $variables = [])
    {
        return $this->renderer->searchAndRenderBlock($view, 'widget', $variables);
    }

    /**
     * Renders the entire block "row"
     * Usually the "row" is a combination of a block label and block widget
     *
     * @param BlockView $view      The view for which to render the row
     * @param array     $variables Additional variables passed to the template
     *
     * @return string
     */
    public function row(BlockView $view, array $variables = [])
    {
        return $this->renderer->searchAndRenderBlock($view, 'row', $variables);
    }

    /**
     * Renders the label of the given view
     *
     * @param BlockView $view      The view for which to render the label
     * @param string    $label     The label
     * @param array     $variables Additional variables passed to the template
     *
     * @return string
     */
    public function label(BlockView $view, $label = null, array $variables = [])
    {
        if (null !== $label) {
            $variables += ['label' => $label];
        }

        return $this->renderer->searchAndRenderBlock($view, 'label', $variables);
    }

    /**
     * Renders a block of the template
     *
     * @param BlockView $view      The view for determining the used themes.
     * @param string    $blockName The name of the block to render.
     * @param array     $variables The variable to pass to the template.
     *
     * @return string
     */
    public function block(BlockView $view, $blockName, array $variables = [])
    {
        return $this->renderer->renderBlock($view, $blockName, $variables);
    }

    /**
     * Normalizes and translates (if needed) labels in the given value.
     *
     * @param mixed       $value
     * @param string|null $domain
     *
     * @return mixed
     */
    public function text($value, $domain = null)
    {
        return $this->textHelper->processText($value, $domain);
    }
}
