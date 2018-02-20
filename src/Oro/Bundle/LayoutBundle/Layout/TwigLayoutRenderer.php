<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Oro\Component\Layout\LayoutRenderer;

class TwigLayoutRenderer extends LayoutRenderer
{
    /**
     * @param TwigRendererInterface $innerRenderer
     * @param FormRendererEngineInterface $formRendererEngine
     * @param \Twig_Environment $environment
     */
    public function __construct(
        TwigRendererInterface $innerRenderer,
        FormRendererEngineInterface $formRendererEngine,
        \Twig_Environment $environment
    ) {
        $innerRenderer->setEnvironment($environment);
        parent::__construct($innerRenderer, $formRendererEngine);
    }
}
