<?php

namespace Oro\Bundle\LayoutBundle\Twig;

use Symfony\Bridge\Twig\Form\TwigRendererInterface;

use Oro\Component\Layout\Templating\TextHelper;

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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            // Normalizes and translates (if needed) labels in the given value.
            new \Twig_SimpleFilter('block_text', [$this->textHelper, 'processText'])
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
