<?php

namespace Oro\Bundle\LayoutBundle\Twig;

use Symfony\Bridge\Twig\Form\TwigRendererInterface;

use Oro\Bundle\LayoutBundle\Twig\TokenParser\LayoutThemeTokenParser;

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

    /**
     * @param TwigRendererInterface $renderer
     */
    public function __construct(TwigRendererInterface $renderer)
    {
        $this->renderer = $renderer;
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
            new LayoutThemeTokenParser(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'layout_widget',
                null,
                ['node_class' => self::RENDER_BLOCK_NODE_CLASS, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'layout_label',
                null,
                ['node_class' => self::RENDER_BLOCK_NODE_CLASS, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'layout_row',
                null,
                ['node_class' => self::RENDER_BLOCK_NODE_CLASS, 'is_safe' => ['html']]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'layout';
    }
}
