<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Config;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderResolver;
use Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader\Fixtures\Bundle\TestBundle\TestBundle;
use Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader\Fixtures\Bundle\TestBundle1\TestBundle1;

class CumulativeResourceManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAndSetBundles()
    {
        CumulativeResourceManager::getInstance()->clear();

        $this->assertCount(
            0,
            CumulativeResourceManager::getInstance()->getBundles()
        );

        $bundle = new TestBundle();
        CumulativeResourceManager::getInstance()->setBundles([$bundle->getName() => get_class($bundle)]);
        $this->assertEquals(
            [$bundle->getName() => get_class($bundle)],
            CumulativeResourceManager::getInstance()->getBundles()
        );
    }

    public function testGetAndSetResourceLoaderResolver()
    {
        CumulativeResourceManager::getInstance()->clear();

        $this->assertEquals(
            new CumulativeResourceLoaderResolver(),
            CumulativeResourceManager::getInstance()->getResourceLoaderResolver()
        );

        $resolver = $this->getMockBuilder('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderResolver')
            ->disableOriginalConstructor()
            ->getMock();
        CumulativeResourceManager::getInstance()->setResourceLoaderResolver($resolver);
        $this->assertSame(
            $resolver,
            CumulativeResourceManager::getInstance()->getResourceLoaderResolver()
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

    public function testRegisterResourceString()
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', 'test.yml');

        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders('test');
        $this->assertCount(1, $resourceLoaders);
        $this->assertInstanceOf(
            'Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader',
            $resourceLoaders[0]
        );
    }

    public function testRegisterResourceLoaderObject()
    {
        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', $resourceLoader);

        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders('test');
        $this->assertCount(1, $resourceLoaders);
        $this->assertSame($resourceLoader, $resourceLoaders[0]);
    }

    public function testRegisterResourceLoaderArray()
    {
        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', ['test.yml', $resourceLoader]);

        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders('test');
        $this->assertCount(2, $resourceLoaders);
        $this->assertInstanceOf(
            'Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader',
            $resourceLoaders[0]
        );
        $this->assertSame($resourceLoader, $resourceLoaders[1]);
    }

    public function testRegisterResourceLoaderWithSameName()
    {
        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', 'test.yml')
            ->registerResource('test', $resourceLoader);

        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders('test');
        $this->assertCount(2, $resourceLoaders);
        $this->assertInstanceOf(
            'Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader',
            $resourceLoaders[0]
        );
        $this->assertSame($resourceLoader, $resourceLoaders[1]);
    }

    public function testIsFreshShouldBeCachedIfTimestampWasNotChanged()
    {
        $bundle = new TestBundle();

        $resourceLoader = $this
            ->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderWithFreshChecker');

        $resourceLoader->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue('res'));
        $resourceLoader->expects($this->once())
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle->getName() => get_class($bundle)])
            ->registerResource('test', $resourceLoader);

        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 100));
    }

    public function testIsFreshShouldBeRecheckedIfTimestampChanged()
    {
        $bundle = new TestBundle();

        $resourceLoader = $this
            ->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderWithFreshChecker');

        $resourceLoader->expects($this->exactly(2))
            ->method('getResource')
            ->will($this->returnValue('res'));
        $resourceLoader->expects($this->exactly(2))
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true, true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle->getName() => get_class($bundle)])
            ->registerResource('test', $resourceLoader);

        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 200));
    }

    public function testIsFreshAllResourcesAreUpToDate()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle();

        $resourceLoader1 = $this
            ->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderWithFreshChecker');
        $resourceLoader2 = $this
            ->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderWithFreshChecker');

        $resourceLoader1->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue('res1'));
        $resourceLoader1->expects($this->exactly(2))
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true, true));

        $resourceLoader1->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue('res2'));
        $resourceLoader2->expects($this->exactly(2))
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true, true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)])
            ->registerResource('test1', $resourceLoader1)
            ->registerResource('test2', $resourceLoader2);

        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res1', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res2', 100));
    }

    public function testIsFreshResource1ForBundle1IsNotUpToDate()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle();

        $resourceLoader1 = $this
            ->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderWithFreshChecker');
        $resourceLoader2 = $this
            ->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderWithFreshChecker');

        $resourceLoader1->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue('res1'));
        $resourceLoader1->expects($this->once())
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(false));

        $resourceLoader1->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue('res2'));
        $resourceLoader2->expects($this->exactly(2))
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true, true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)])
            ->registerResource('test1', $resourceLoader1)
            ->registerResource('test2', $resourceLoader2);

        $this->assertFalse(CumulativeResourceManager::getInstance()->isFresh('res1', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res2', 100));
    }
}
