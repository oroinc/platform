<?php

namespace Oro\Bundle\LayoutBundle\Twig;

use Symfony\Bridge\Twig\Form\TwigRendererInterface;

use Oro\Component\Layout\Templating\TextHelper;
use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Twig\TokenParser\BlockThemeTokenParser;

class LayoutExtension extends \Twig_Extension
{
    const RENDER_BLOCK_NODE_CLASS = 'Oro\Bundle\LayoutBundle\Twig\Node\SearchAndRenderBlockNode';

    /**
     * This property is public so that it can be accessed directly from compiled
     * templates without having to call a getter, which slightly decreases performance.
     *
     * @var TwigRendererInterface
     */
    public $renderer;

    /** @var TextHelper */
    private $textHelper;

    /**
     * @param TwigRendererInterface $renderer
     * @param TextHelper            $textHelper
     */
    public function __construct(TwigRendererInterface $renderer, TextHelper $textHelper)
    {
        $this->renderer   = $renderer;
        $this->textHelper = $textHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->renderer->setEnvironment($environment);
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return [
            new BlockThemeTokenParser()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'block_widget',
                null,
                ['node_class' => self::RENDER_BLOCK_NODE_CLASS, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'block_label',
                null,
                ['node_class' => self::RENDER_BLOCK_NODE_CLASS, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'block_row',
                null,
                ['node_class' => self::RENDER_BLOCK_NODE_CLASS, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'layout_attr_merge',
                [$this, 'mergeAttributes']
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            // Normalizes and translates (if needed) labels in the given value.
            new \Twig_SimpleFilter('block_text', [$this->textHelper, 'processText']),
            // Merge additional context to BlockView
            new \Twig_SimpleFilter('merge_context', [$this, 'mergeContext']),
        ];
    }

    /**
     * @param BlockView $view
     * @param array $context
     * @return BlockView
     */
    public function mergeContext(BlockView $view, array $context)
    {
        $view->vars = array_merge($view->vars, $context);

        foreach ($view->children as $child) {
            $this->mergeContext($child, $context);
        }

        return $view;
    }

    /**
     * @param array $attr
     * @param array $defaultAttr
     * @return array
     */
    public function mergeAttributes(array $attr, array $defaultAttr)
    {
        foreach ($defaultAttr as $key => $value) {
            if (strpos($key, '~') === 0) {
                $key = substr($key, 1);
                if (array_key_exists($key, $attr)) {
                    if (is_array($value)) {
                        $attr[$key] = array_merge_recursive($value, (array)$attr[$key]);
                    } else {
                        $attr[$key] .= $value;
                    }
                }
            }
            if (!array_key_exists($key, $attr)) {
                $attr[$key] = $value;
            }
        }

        return $attr;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'layout';
    }
}
