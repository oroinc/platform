<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Twig\Environment;

/**
 * Interface for the twig renderer engine.
 * Enables usage of the setEnvironment() method.
 */
interface TwigRendererEngineInterface extends FormRendererEngineInterface
{
    /**
     * Sets Twig's environment.
     */
    public function setEnvironment(Environment $environment);
}
