<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Loads dynamic Imagine filters
 *
 * @deprecated will be remove in 4.0 in favor of on demand filter configuration loading
 */
class ImagineFilterConfigListener
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            /** @var ImageFilterLoader $loader */
            $loader = $this->container->get('oro_layout.loader.image_filter');
            $loader->load();
        }
    }
}
