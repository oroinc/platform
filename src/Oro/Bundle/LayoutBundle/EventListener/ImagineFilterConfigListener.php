<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;

/**
 * Loads dynamic Imagine filters
 */
class ImagineFilterConfigListener
{
    /**
     * @var ImageFilterLoader
     */
    protected $imageFilterLoader;

    /**
     * @param ImageFilterLoader $imageFilterLoader
     */
    public function __construct(ImageFilterLoader $imageFilterLoader)
    {
        $this->imageFilterLoader = $imageFilterLoader;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            $this->imageFilterLoader->load();
        }
    }
}
