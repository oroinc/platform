<?php

namespace Oro\Bundle\GaufretteBundle\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Symfony\Component\Routing\Route;

/**
 * Registers the public file router as the last at the routes collection to be sure that it will be checked last.
 */
class RouteCollectionListener
{
    /**
     * @param RouteCollectionEvent $event
     */
    public function onCollectionAutoload(RouteCollectionEvent $event)
    {
        $collection = $event->getCollection();
        $collection->add(
            'oro_gaufrette_public_file',
            new Route(
                'media/{filePrefixDir}/{filePath}',
                ['_controller' => 'OroGaufretteBundle:PublicFile:getPublicFile'],
                ['filePrefixDir' => '[\w-]+', 'filePath' => '.+']
            )
        );
    }
}
