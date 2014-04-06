<?php

namespace Oro\Component\Config\Tests\Unit;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\CumulativeResourceLoaderResolver;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle2\TestBundle2;

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

    public function testGetAndSetResourceLoaderResolver()
    {
        CumulativeResourceManager::getInstance()->clear();

        $this->assertEquals(
            new CumulativeResourceLoaderResolver(),
            CumulativeResourceManager::getInstance()->getResourceLoaderResolver()
        );

        $resolver = $this->getMockBuilder('Oro\Component\Config\Loader\CumulativeResourceLoaderResolver')
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
            'Oro\Component\Config\Loader\CumulativeResourceLoader',
            $resourceLoaders[0]
        );
    }

    public function testRegisterResourceLoaderObject()
    {
        $resourceLoader = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', $resourceLoader);

        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders('test');
        $this->assertCount(1, $resourceLoaders);
        $this->assertSame($resourceLoader, $resourceLoaders[0]);
    }

    public function testRegisterResourceLoaderArray()
    {
        $resourceLoader = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', ['test.yml', $resourceLoader]);

        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders('test');
        $this->assertCount(2, $resourceLoaders);
        $this->assertInstanceOf(
            'Oro\Component\Config\Loader\CumulativeResourceLoader',
            $resourceLoaders[0]
        );
        $this->assertSame($resourceLoader, $resourceLoaders[1]);
    }

    public function testRegisterResourceLoaderWithSameName()
    {
        $resourceLoader = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test', 'test.yml')
            ->registerResource('test', $resourceLoader);

        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders('test');
        $this->assertCount(2, $resourceLoaders);
        $this->assertInstanceOf(
            'Oro\Component\Config\Loader\CumulativeResourceLoader',
            $resourceLoaders[0]
        );
        $this->assertSame($resourceLoader, $resourceLoaders[1]);
    }

    public function testIsFreshShouldBeCachedIfTimestampWasNotChanged()
    {
        $bundle = new TestBundle1();

        $resourceLoader = $this
            ->getMock('Oro\Component\Config\Loader\CumulativeResourceLoaderWithFreshChecker');

        $resourceLoader->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue('res'));
        $resourceLoader->expects($this->once())
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)])
            ->registerResource('test', $resourceLoader);

        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 100));
    }

    public function testIsFreshShouldBeRecheckedIfTimestampChanged()
    {
        $bundle = new TestBundle1();

        $resourceLoader = $this
            ->getMock('Oro\Component\Config\Loader\CumulativeResourceLoaderWithFreshChecker');

        $resourceLoader->expects($this->exactly(2))
            ->method('getResource')
            ->will($this->returnValue('res'));
        $resourceLoader->expects($this->exactly(2))
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true, true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)])
            ->registerResource('test', $resourceLoader);

        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res', 200));
    }

    public function testIsFreshAllResourcesAreUpToDate()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();

        $resourceLoader1 = $this
            ->getMock('Oro\Component\Config\Loader\CumulativeResourceLoaderWithFreshChecker');
        $resourceLoader2 = $this
            ->getMock('Oro\Component\Config\Loader\CumulativeResourceLoaderWithFreshChecker');

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
            ->setBundles(['TestBundle1' => get_class($bundle1), 'TestBundle2' => get_class($bundle2)])
            ->registerResource('test1', $resourceLoader1)
            ->registerResource('test2', $resourceLoader2);

        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res1', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res2', 100));
    }

    public function testIsFreshResource1ForBundle1IsNotUpToDate()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();

        $resourceLoader1 = $this
            ->getMock('Oro\Component\Config\Loader\CumulativeResourceLoaderWithFreshChecker');
        $resourceLoader2 = $this
            ->getMock('Oro\Component\Config\Loader\CumulativeResourceLoaderWithFreshChecker');

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
            ->setBundles(['TestBundle1' => get_class($bundle1), 'TestBundle2' => get_class($bundle2)])
            ->registerResource('test1', $resourceLoader1)
            ->registerResource('test2', $resourceLoader2);

        $this->assertFalse(CumulativeResourceManager::getInstance()->isFresh('res1', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res2', 100));
    }
}
