<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Component\Layout\LayoutRenderer;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;

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
