<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Oro\Component\Layout\Form\FormRendererInterface;
use Twig\Environment;

/**
 * Interface for the twig renderer.
 * Enables usage of the setEnvironment() method.
 */
interface TwigRendererInterface extends FormRendererInterface
{
    /**
     * Sets Twig's environment.
     */
    public function setEnvironment(Environment $environment);
}
