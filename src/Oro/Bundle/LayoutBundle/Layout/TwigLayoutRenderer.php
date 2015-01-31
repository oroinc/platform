<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Symfony\Bridge\Twig\Form\TwigRendererInterface;

use Oro\Component\Layout\LayoutRenderer;

class TwigLayoutRenderer extends LayoutRenderer
{
    /**
     * @param TwigRendererInterface $innerRenderer
     * @param \Twig_Environment     $environment
     */
    public function __construct(TwigRendererInterface $innerRenderer, \Twig_Environment $environment)
    {
        $innerRenderer->setEnvironment($environment);
        parent::__construct($innerRenderer);
    }
}
