<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Twig\Environment;

/**
 * Interface that can be implemented by TWIG layout renderers to switch between different TWIG environments.
 */
interface TwigEnvironmentAwareLayoutRendererInterface
{
    /**
     * @return Environment Current TWIG environment.
     */
    public function getEnvironment(): Environment;

    /**
     * @param Environment $environment The TWIG environment to switch to.
     */
    public function setEnvironment(Environment $environment): void;
}
