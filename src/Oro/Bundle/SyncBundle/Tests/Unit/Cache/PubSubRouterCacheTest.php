<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Cache;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\PubSubRouterBundle\Router\RouteCollection;
use Oro\Bundle\SyncBundle\Cache\PubSubRouterCache;
use Oro\Component\Testing\TempDirExtension;

class PubSubRouterCacheTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    public function testFetchWhenNoCacheDir()
    {
        $directory = $this->getTempDir('PubSubRouterCache', false);
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router');
        self::assertFalse($cache->fetch('collection.websocket'));
    }

    public function testContainsWhenNoCacheDir()
    {
        $directory = $this->getTempDir('PubSubRouterCache', false);
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router');
        self::assertFalse($cache->contains('collection.websocket'));
    }

    public function testDeleteWhenNoCacheDir()
    {
        $directory = $this->getTempDir('PubSubRouterCache', false);
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router');
        self::assertTrue($cache->delete('collection.websocket'));
    }

    public function testSaveWhenNoCacheDir()
    {
        $data = new RouteCollection();
        $data->add('test_route', new Route('test/route', 'test.route'));

        $directory = $this->getTempDir('PubSubRouterCache', false);
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router');
        self::assertTrue($cache->save('collection.websocket', $data));

        $fetchedData = $cache->fetch('collection.websocket');
        self::assertEquals($data, $fetchedData);
        self::assertNotSame($data, $fetchedData);

        self::assertTrue($cache->contains('collection.websocket'));
        self::assertTrue($cache->delete('collection.websocket'));
        self::assertFalse($cache->contains('collection.websocket'));
    }

    public function testFetchWhenNoCachedFile()
    {
        $directory = $this->getTempDir('PubSubRouterCache');
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router');
        self::assertFalse($cache->fetch('collection.websocket'));
    }

    public function testContainsWhenNoCachedFile()
    {
        $directory = $this->getTempDir('PubSubRouterCache');
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router');
        self::assertFalse($cache->contains('collection.websocket'));
    }

    public function testDeleteWhenNoCachedFile()
    {
        $directory = $this->getTempDir('PubSubRouterCache');
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router');
        self::assertTrue($cache->delete('collection.websocket'));
    }

    public function testSaveWhenNoCachedFile()
    {
        $data = new RouteCollection();
        $data->add('test_route', new Route('test/route', 'test.route'));

        $directory = $this->getTempDir('PubSubRouterCache');
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router');
        self::assertTrue($cache->save('collection.websocket', $data));

        $fetchedData = $cache->fetch('collection.websocket');
        self::assertEquals($data, $fetchedData);
        self::assertNotSame($data, $fetchedData);

        self::assertTrue($cache->contains('collection.websocket'));
        self::assertTrue($cache->delete('collection.websocket'));
        self::assertFalse($cache->contains('collection.websocket'));
    }

    public function testSaveWhenCachedFileAlreadyExists()
    {
        $existingData = new RouteCollection();
        $existingData->add('existing_test_route', new Route('existing_test/route', 'existing_test.route'));

        $data = new RouteCollection();
        $data->add('test_route', new Route('test/route', 'test.route'));

        $directory = $this->getTempDir('PubSubRouterCache');
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router');
        self::assertTrue($cache->save('collection.websocket', $existingData));

        self::assertTrue($cache->save('collection.websocket', $data));

        $fetchedData = $cache->fetch('collection.websocket');
        self::assertEquals($data, $fetchedData);
        self::assertNotSame($data, $fetchedData);

        self::assertTrue($cache->contains('collection.websocket'));
        self::assertTrue($cache->delete('collection.websocket'));
        self::assertFalse($cache->contains('collection.websocket'));
    }

    public function testFetchInDebugMode()
    {
        $directory = $this->getTempDir('PubSubRouterCache');
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router', true);
        self::assertFalse($cache->fetch('collection.websocket'));
    }

    public function testContainsInDebugMode()
    {
        $directory = $this->getTempDir('PubSubRouterCache');
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router', true);
        self::assertFalse($cache->contains('collection.websocket'));
    }

    public function testDeleteInDebugMode()
    {
        $directory = $this->getTempDir('PubSubRouterCache');
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router', true);
        self::assertTrue($cache->delete('collection.websocket'));
    }

    public function testSaveInDebugMode()
    {
        $data = new RouteCollection();
        $data->add('test_route', new Route('test/route', 'test.route'));

        $directory = $this->getTempDir('PubSubRouterCache');
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router', true);
        self::assertTrue($cache->save('collection.websocket', $data));

        self::assertFalse($cache->fetch('collection.websocket'));
        self::assertFalse($cache->contains('collection.websocket'));
    }

    public function testGetStats()
    {
        $directory = $this->getTempDir('PubSubRouterCache');
        $cache = new PubSubRouterCache($directory, 'gos_pubsub_router');
        self::assertNull($cache->getStats());
    }
}
