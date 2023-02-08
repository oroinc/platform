<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Oro\Bundle\LayoutBundle\Event\LayoutContextChangedEvent;
use Oro\Bundle\LayoutBundle\Layout\TwigEnvironmentAwareLayoutRendererInterface;
use Twig\Environment;

/**
 * Switches TWIG environment on TWIG layout renderer when layout context is changed.
 */
class LayoutContextChangedListener
{
    private TwigEnvironmentAwareLayoutRendererInterface $twigLayoutRenderer;

    private Environment $environment;

    private array $environmentByContext = [];

    public function __construct(TwigEnvironmentAwareLayoutRendererInterface $twigLayoutRenderer)
    {
        $this->twigLayoutRenderer = $twigLayoutRenderer;
        $this->environment = $this->twigLayoutRenderer->getEnvironment();
    }

    public function onContextChanged(LayoutContextChangedEvent $event): void
    {
        $layoutContext = $event->getCurrentContext();
        if ($layoutContext) {
            $layoutContextHash = $layoutContext->getHash();

            if (!isset($this->environmentByContext[$layoutContextHash])) {
                $this->environmentByContext[$layoutContextHash] = clone $this->environment;
            }

            $newEnvironment = $this->environmentByContext[$layoutContextHash];
        } else {
            $newEnvironment = $this->environment;
        }

        $this->twigLayoutRenderer->setEnvironment($newEnvironment);
    }
}
