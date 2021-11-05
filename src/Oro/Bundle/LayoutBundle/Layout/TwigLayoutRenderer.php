<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Bundle\LayoutBundle\Cache\PlaceholderRenderer;
use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Oro\Component\Layout\LayoutRenderer;
use Twig\Environment;

/**
 * Layout renderer that uses Twig for rendering.
 * @see \Oro\Component\Layout\Layouts
 */
class TwigLayoutRenderer extends LayoutRenderer
{
    /**
     * @var PlaceholderRenderer
     */
    private $placeholderRenderer;

    public function __construct(
        TwigRendererInterface $innerRenderer,
        FormRendererEngineInterface $formRendererEngine,
        Environment $environment,
        PlaceholderRenderer $placeholderRenderer
    ) {
        $innerRenderer->setEnvironment($environment);
        $this->placeholderRenderer = $placeholderRenderer;
        parent::__construct($innerRenderer, $formRendererEngine);
    }

    /**
     * {@inheritDoc}
     */
    public function renderBlock(BlockView $view)
    {
        $this->placeholderRenderer->reset();

        return parent::renderBlock($view);
    }
}
