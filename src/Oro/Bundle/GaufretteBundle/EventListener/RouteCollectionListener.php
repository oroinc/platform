<?php

namespace Oro\Bundle\GaufretteBundle\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Bundle\GaufretteBundle\Controller\PublicFileController;
use Symfony\Component\Routing\Route;

/**
 * Adds a route to get files from public Gaufrette filesystems to the end of the routes collection
 * to be sure that it will be checked last, to be able to create custom controllers
 * for specific sub-directories in the public storage.
 */
class RouteCollectionListener
{
    public function onCollectionAutoload(RouteCollectionEvent $event): void
    {
        $collection = $event->getCollection();
        $collection->add(
            'oro_gaufrette_public_file',
            new Route(
                'media/{subDirectory}/{fileName}',
                ['_controller' => PublicFileController::class . '::getPublicFileAction'],
                ['subDirectory' => '[\w-]+', 'fileName' => '.+']
            ),
            -10
        );
    }
}
