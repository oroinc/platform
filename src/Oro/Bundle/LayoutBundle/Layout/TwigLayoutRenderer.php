<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Oro\Component\Layout\LayoutRenderer;
use Twig\Environment;

/**
 * Layout renderer that uses Twig for rendering.
 * @see \Oro\Component\Layout\Layouts
 */
class TwigLayoutRenderer extends LayoutRenderer
{
    public function __construct(
        TwigRendererInterface $innerRenderer,
        FormRendererEngineInterface $formRendererEngine,
        Environment $environment
    ) {
        $innerRenderer->setEnvironment($environment);
        parent::__construct($innerRenderer, $formRendererEngine);
    }
}
