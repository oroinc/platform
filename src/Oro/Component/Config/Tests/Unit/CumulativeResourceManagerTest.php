<?php

namespace Oro\Component\Config\Tests\Unit;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;

class CumulativeResourceManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAndSetBundles()
    {
        CumulativeResourceManager::getInstance()->clear();

        $this->assertCount(
            0,
            CumulativeResourceManager::getInstance()->getBundles()
        );

        $bundle = new TestBundle1();
        CumulativeResourceManager::getInstance()->setBundles(['TestBundle1' => get_class($bundle)]);
        $this->assertEquals(
            ['TestBundle1' => get_class($bundle)],
            CumulativeResourceManager::getInstance()->getBundles()
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Resource loaders for "unknown" was not found.
     */
    public function testGetLoaderUnknown()
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->getResourceLoaders('unknown');
    }

    public function testAddResourceLoader()
    {
        $resourceLoader = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->addResourceLoader('test', $resourceLoader);

        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders('test');
        $this->assertCount(1, $resourceLoaders);
        $this->assertSame($resourceLoader, $resourceLoaders[0]);
    }

    public function testAddResourceLoaderForArray()
    {
        $resourceLoader1 = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');
        $resourceLoader2 = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->addResourceLoader('test', [$resourceLoader1, $resourceLoader2]);

        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders('test');
        $this->assertCount(2, $resourceLoaders);
        $this->assertSame($resourceLoader1, $resourceLoaders[0]);
        $this->assertSame($resourceLoader2, $resourceLoaders[1]);
    }

    public function testAddResourceLoaderWithSameName()
    {
        $resourceLoader1 = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');
        $resourceLoader2 = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->addResourceLoader('test', $resourceLoader1)
            ->addResourceLoader('test', $resourceLoader2);

        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders('test');
        $this->assertCount(2, $resourceLoaders);
        $this->assertSame($resourceLoader1, $resourceLoaders[0]);
        $this->assertSame($resourceLoader2, $resourceLoaders[1]);
    }
}
