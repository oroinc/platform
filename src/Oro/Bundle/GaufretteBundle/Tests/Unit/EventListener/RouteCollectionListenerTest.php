<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Bundle\GaufretteBundle\EventListener\RouteCollectionListener;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCollectionListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnCollectionAutoload()
    {
        $collection = new RouteCollection();
        $collection->add(
            'test',
            new Route(
                'test',
                ['_controller' => 'AcmeDemoBundle:Test:getTest']
            )
        );
        $event = new RouteCollectionEvent($collection);

        $listener = new RouteCollectionListener();
        $listener->onCollectionAutoload($event);

        self::assertCount(2, $collection);
        self::assertEquals(
            [
                'test'=> new Route(
                    'test',
                    ['_controller' => 'AcmeDemoBundle:Test:getTest']
                ),
                'oro_gaufrette_public_file' => new Route(
                    'media/{filePrefixDir}/{filePath}',
                    ['_controller' => 'OroGaufretteBundle:PublicFile:getPublicFile'],
                    ['filePrefixDir' => '[\w-]+', 'filePath' => '.+']
                )
            ],
            $collection->all()
        );
    }
}
