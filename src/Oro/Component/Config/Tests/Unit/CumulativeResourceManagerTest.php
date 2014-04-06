<?php

namespace Oro\Component\Config\Tests\Unit;

use Oro\Component\Config\CumulativeResourceManager;
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
            ->addResourceLoader('test', $resourceLoader);

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
            ->addResourceLoader('test', $resourceLoader);

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
            ->addResourceLoader('test1', $resourceLoader1)
            ->addResourceLoader('test2', $resourceLoader2);

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
            ->addResourceLoader('test1', $resourceLoader1)
            ->addResourceLoader('test2', $resourceLoader2);

        $this->assertFalse(CumulativeResourceManager::getInstance()->isFresh('res1', 100));
        $this->assertTrue(CumulativeResourceManager::getInstance()->isFresh('res2', 100));
    }
}
