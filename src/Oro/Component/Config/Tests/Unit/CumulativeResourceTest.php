<?php

namespace Oro\Component\Config\Tests\Unit;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle2\TestBundle2;

class CumulativeResourceTest extends \PHPUnit\Framework\TestCase
{
    public function testResource()
    {
        $resource = new CumulativeResource('test', new CumulativeResourceLoaderCollection());
        $resource->addFound('bundle', 'path');
        $this->assertEquals('test', $resource->getResource());
        $this->assertEquals('test', $resource->__toString());
        $this->assertTrue($resource->isFound('bundle', 'path'));
        $this->assertFalse($resource->isFound('bundle', 'path1'));
        $this->assertFalse($resource->isFound('bundle1', 'path'));
    }

    public function testSetialization()
    {
        $resource = new CumulativeResource(
            'test',
            new CumulativeResourceLoaderCollection(
                [
                    new FolderingCumulativeFileLoader(
                        '{folder}',
                        '\w+',
                        [
                            new YamlCumulativeFileLoader('Resources/config/res1.yml'),
                            new YamlCumulativeFileLoader('Resources/config/res2.yml'),
                        ]
                    )
                ]
            )
        );
        $resource->addFound('bundle', 'path');
        $serializedData = $resource->serialize();
        $unserializedResource = new CumulativeResource('test1', new CumulativeResourceLoaderCollection());
        $unserializedResource->unserialize($serializedData);

        $this->assertEquals($resource, $unserializedResource);
    }

    public function testIsFreshShouldBeCachedIfTimestampWasNotChanged()
    {
        $bundle = new TestBundle1();

        $resourceLoader = $this->createMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        $resource = new CumulativeResource(
            'test',
            new CumulativeResourceLoaderCollection([$resourceLoader])
        );

        $resourceLoader->expects($this->once())
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)]);

        $this->assertTrue($resource->isFresh(100));
        $this->assertTrue($resource->isFresh(100));
    }

    public function testIsFreshShouldBeRecheckedIfTimestampChanged()
    {
        $bundle = new TestBundle1();

        $resourceLoader = $this->createMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        $resource = new CumulativeResource(
            'test',
            new CumulativeResourceLoaderCollection([$resourceLoader])
        );

        $resourceLoader->expects($this->exactly(2))
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true, true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)]);

        $this->assertTrue($resource->isFresh(100));
        $this->assertTrue($resource->isFresh(200));
    }

    public function testIsFreshAllResourcesAreUpToDate()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();

        $resourceLoader1 = $this->createMock('Oro\Component\Config\Loader\CumulativeResourceLoader');
        $resourceLoader2 = $this->createMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        $resource1 = new CumulativeResource(
            'test1',
            new CumulativeResourceLoaderCollection([$resourceLoader1])
        );
        $resource2 = new CumulativeResource(
            'test2',
            new CumulativeResourceLoaderCollection([$resourceLoader2])
        );

        $resourceLoader1->expects($this->exactly(2))
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true, true));

        $resourceLoader2->expects($this->exactly(2))
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true, true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle1), 'TestBundle2' => get_class($bundle2)]);

        $this->assertTrue($resource1->isFresh(100));
        $this->assertTrue($resource2->isFresh(100));
    }

    public function testIsFreshResource1ForBundle2IsNotUpToDate()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();

        $resourceLoader1 = $this->createMock('Oro\Component\Config\Loader\CumulativeResourceLoader');
        $resourceLoader2 = $this->createMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        $resource = new CumulativeResource(
            'test',
            new CumulativeResourceLoaderCollection([$resourceLoader1, $resourceLoader2])
        );

        $resourceLoader1->expects($this->exactly(2))
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true, false));

        $resourceLoader2->expects($this->once())
            ->method('isResourceFresh')
            ->will($this->onConsecutiveCalls(true));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle1), 'TestBundle2' => get_class($bundle2)]);

        $this->assertFalse($resource->isFresh(100));
    }
}
