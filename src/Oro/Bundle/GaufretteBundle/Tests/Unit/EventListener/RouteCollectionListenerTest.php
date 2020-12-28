<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Bundle\GaufretteBundle\Controller\PublicFileController;
use Oro\Bundle\GaufretteBundle\EventListener\RouteCollectionListener;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCollectionListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnCollectionAutoload()
    {
        $collection = new RouteCollection();
        $existingRoute = new Route('test', ['_controller' => 'AcmeDemoBundle:Test:get']);
        $collection->add('test', $existingRoute);

        $listener = new RouteCollectionListener();
        $listener->onCollectionAutoload(new RouteCollectionEvent($collection));

        self::assertCount(2, $collection);
        self::assertEquals(
            [
                'test'                      => $existingRoute,
                'oro_gaufrette_public_file' => new Route(
                    'media/{subDirectory}/{fileName}',
                    ['_controller' => PublicFileController::class . '::getPublicFileAction'],
                    ['subDirectory' => '[\w-]+', 'fileName' => '.+']
                )
            ],
            $collection->all()
        );
        self::assertEquals(['test', 'oro_gaufrette_public_file'], array_keys($collection->all()));
    }
}
