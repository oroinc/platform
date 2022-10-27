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
class TwigLayoutRenderer extends LayoutRenderer implements TwigEnvironmentAwareLayoutRendererInterface
{
    private PlaceholderRenderer $placeholderRenderer;

    private Environment $environment;

    public function __construct(
        TwigRendererInterface $innerRenderer,
        FormRendererEngineInterface $formRendererEngine,
        Environment $environment,
        PlaceholderRenderer $placeholderRenderer
    ) {
        parent::__construct($innerRenderer, $formRendererEngine);

        $this->placeholderRenderer = $placeholderRenderer;
        $this->setEnvironment($environment);
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function setEnvironment(Environment $environment): void
    {
        $this->environment = $environment;

        $this->innerRenderer->setEnvironment($this->environment);
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
