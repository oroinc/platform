<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Oro\Component\Layout\Form\FormRendererInterface;
use Twig\Environment;

/**
 * Enables usage of the setEnvironment() method.
 */
interface TwigRendererInterface extends FormRendererInterface
{
    /**
     * Sets Twig's environment.
     *
     * @param Environment $environment
     */
    public function setEnvironment(Environment $environment);
}
