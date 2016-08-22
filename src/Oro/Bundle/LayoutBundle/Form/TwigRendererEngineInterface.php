<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

interface TwigRendererEngineInterface extends FormRendererEngineInterface
{
    /**
     * Sets Twig's environment.
     *
     * @param \Twig_Environment $environment
     */
    public function setEnvironment(\Twig_Environment $environment);
}
