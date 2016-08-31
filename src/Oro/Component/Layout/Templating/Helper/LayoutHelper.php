<?php

namespace Oro\Component\Layout\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;

use Oro\Component\Layout\Form\FormRendererInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Templating\TextHelper;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

/**
 * LayoutHelper provides helpers to help display layout blocks
 */
class LayoutHelper extends Helper
{
    /** @var FormRendererInterface */
    private $renderer;

    /** @var TextHelper */
    private $textHelper;

    /** @var FormRendererEngineInterface */
    private $formRendererEngine;

    /**
     * @param FormRendererInterface $renderer
     * @param TextHelper $textHelper
     * @param FormRendererEngineInterface $formRendererEngine
     */
    public function __construct(
        FormRendererInterface $renderer,
        TextHelper $textHelper,
        FormRendererEngineInterface $formRendererEngine
    ) {
        $this->renderer = $renderer;
        $this->textHelper = $textHelper;
        $this->formRendererEngine = $formRendererEngine;
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
     * Sets the theme(s) to be used for rendering forms
     *
     * The theme format is "<Bundle>:<Controller>".
     *
     * @param string|string[] $themes The theme(s). For example 'MuBundle:Layout/php'
     */
    public function setFormTheme($themes)
    {
        $this->formRendererEngine->addDefaultThemes($themes);
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
     * Renders the parent block widget defined in other resources on all levels of block prefix hierarchy
     *
     * @param BlockView $view
     * @param array $variables
     *
     * @return string
     */
    public function parentBlockWidget(BlockView $view, array $variables = [])
    {
        return $this->renderer->searchAndRenderBlock($view, 'widget', $variables, true);
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
