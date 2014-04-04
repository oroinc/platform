<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Config;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderResolver;

class CumulativeResourceManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAndSetBundles()
    {
        CumulativeResourceManager::getInstance()->clear();

        $this->assertCount(
            0,
            CumulativeResourceManager::getInstance()->getBundles()
        );

        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        CumulativeResourceManager::getInstance()->setBundles([$bundle]);
        $this->assertCount(
            1,
            CumulativeResourceManager::getInstance()->getBundles()
        );
        $this->assertSame(
            $bundle,
            CumulativeResourceManager::getInstance()->getBundles()[0]
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
     * @expectedExceptionMessage A loader for "unknown" was not found.
     */
    public function testGetLoaderUnknown()
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->getLoader('unknown');
    }

    public function testRegisterResourceString()
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', 'test.yml');

        $loader = CumulativeResourceManager::getInstance()->getLoader('test');
        $this->assertCount(1, $loader->getResourceLoaders());
        $this->assertInstanceOf(
            'Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader',
            $loader->getResourceLoaders()[0]
        );
    }

    public function testRegisterResourceLoaderObject()
    {
        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', $resourceLoader);

        $loader = CumulativeResourceManager::getInstance()->getLoader('test');
        $this->assertCount(1, $loader->getResourceLoaders());
        $this->assertSame($resourceLoader, $loader->getResourceLoaders()[0]);
    }

    public function testRegisterResourceLoaderArray()
    {
        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', ['test.yml', $resourceLoader]);

        $loader = CumulativeResourceManager::getInstance()->getLoader('test');
        $this->assertCount(2, $loader->getResourceLoaders());
        $this->assertInstanceOf(
            'Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader',
            $loader->getResourceLoaders()[0]
        );
        $this->assertSame($resourceLoader, $loader->getResourceLoaders()[1]);
    }

    public function testRegisterResourceLoaderWithSameName()
    {
        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', 'test.yml')
            ->registerResource('test', $resourceLoader);

        $loader = CumulativeResourceManager::getInstance()->getLoader('test');
        $this->assertCount(2, $loader->getResourceLoaders());
        $this->assertInstanceOf(
            'Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader',
            $loader->getResourceLoaders()[0]
        );
        $this->assertSame($resourceLoader, $loader->getResourceLoaders()[1]);
    }

    public function testIsFreshShouldBeCachedIfTimestampWasNotChanged()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');

        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');

        $resourceLoader->expects($this->exactly(1))
            ->method('getResource')
            ->will($this->returnValue('res'));
        $resourceLoader->expects($this->exactly(1))
            ->method('isFresh')
            ->will($this->onConsecutiveCalls(true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle])
            ->registerResource('test', $resourceLoader);

        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 100));
    }

    public function testIsFreshShouldBeRecheckedIfTimestampChanged()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');

        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');

        $resourceLoader->expects($this->exactly(2))
            ->method('getResource')
            ->will($this->returnValue('res'));
        $resourceLoader->expects($this->exactly(2))
            ->method('isFresh')
            ->will($this->onConsecutiveCalls(true, true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle])
            ->registerResource('test', $resourceLoader);

        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 200));
    }

    public function testIsFreshAllResourcesAreUpToDate()
    {
        $bundle1 = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle2 = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');

        $resourceLoader1 = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');
        $resourceLoader2 = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');

        $resourceLoader1->expects($this->exactly(2))
            ->method('getResource')
            ->will($this->returnValue('res1'));
        $resourceLoader1->expects($this->exactly(2))
            ->method('isFresh')
            ->will($this->onConsecutiveCalls(true, true));

        $resourceLoader1->expects($this->exactly(2))
            ->method('getResource')
            ->will($this->returnValue('res2'));
        $resourceLoader2->expects($this->exactly(2))
            ->method('isFresh')
            ->will($this->onConsecutiveCalls(true, true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1, $bundle2])
            ->registerResource('test1', $resourceLoader1)
            ->registerResource('test2', $resourceLoader2);

        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res1', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res2', 100));
    }

    public function testIsFreshResource1ForBundle1IsNotUpToDate()
    {
        $bundle1 = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle2 = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');

        $resourceLoader1 = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');
        $resourceLoader2 = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');

        $resourceLoader1->expects($this->exactly(2))
            ->method('getResource')
            ->will($this->returnValue('res1'));
        $resourceLoader1->expects($this->exactly(1))
            ->method('isFresh')
            ->will($this->onConsecutiveCalls(false));

        $resourceLoader1->expects($this->exactly(2))
            ->method('getResource')
            ->will($this->returnValue('res2'));
        $resourceLoader2->expects($this->exactly(2))
            ->method('isFresh')
            ->will($this->onConsecutiveCalls(true, true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1, $bundle2])
            ->registerResource('test1', $resourceLoader1)
            ->registerResource('test2', $resourceLoader2);

        $this->assertFalse(CumulativeResourceManager::getInstance()->isFresh('res1', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res2', 100));
    }
}
