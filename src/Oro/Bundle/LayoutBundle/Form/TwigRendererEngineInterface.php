<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Twig\Environment;

/**
 * Enables usage of the setEnvironment() method.
 */
interface TwigRendererEngineInterface extends FormRendererEngineInterface
{
    /**
     * Sets Twig's environment.
     *
     * @param Environment $environment
     */
    public function setEnvironment(Environment $environment);
}
