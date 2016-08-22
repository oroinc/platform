<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Oro\Component\Layout\Form\FormRendererInterface;

interface TwigRendererInterface extends FormRendererInterface
{
    /**
     * Sets Twig's environment.
     *
     * @param \Twig_Environment $environment
     */
    public function setEnvironment(\Twig_Environment $environment);
}
