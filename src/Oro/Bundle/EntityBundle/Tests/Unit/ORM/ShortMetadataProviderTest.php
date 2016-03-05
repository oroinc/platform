<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\ShortClassMetadata;
use Oro\Bundle\EntityBundle\ORM\ShortMetadataProvider;

class ShortMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAllShortMetadataWithEmptyCache()
    {
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $metadataFactory = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory')
            ->disableOriginalConstructor()
            ->setMethods(['getCacheDriver', 'getAllMetadata'])
            ->getMockForAbstractClass();
        $cacheDriver = $this->getMock('Doctrine\Common\Cache\Cache');

        $metadata1 = new ClassMetadata('Test\Entity1');
        $metadata2 = new ClassMetadata('Test\Entity2');
        $metadata2->isMappedSuperclass = true;
        $metadata3 = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata3->expects($this->once())->method('getName')->willReturn('Test\Entity3');

        $expectedResult = [
            new ShortClassMetadata('Test\Entity1'),
            new ShortClassMetadata('Test\Entity2', true),
            new ShortClassMetadata('Test\Entity3'),
        ];

        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getCacheDriver')
            ->willReturn($cacheDriver);
        $cacheDriver->expects($this->once())
            ->method('fetch')
            ->with(ShortMetadataProvider::ALL_SHORT_METADATA_CACHE_KEY)
            ->willReturn(false);
        $cacheDriver->expects($this->once())
            ->method('save')
            ->with(ShortMetadataProvider::ALL_SHORT_METADATA_CACHE_KEY, $expectedResult);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([$metadata1, $metadata2, $metadata3]);

        $provider = new ShortMetadataProvider();

        $this->assertEquals(
            $expectedResult,
            $provider->getAllShortMetadata($manager)
        );
        // test that the result is cached locally
        $this->assertEquals(
            [
                new ShortClassMetadata('Test\Entity1'),
                new ShortClassMetadata('Test\Entity2', true),
                new ShortClassMetadata('Test\Entity3'),
            ],
            $provider->getAllShortMetadata($manager)
        );
    }

    public function testGetAllShortMetadataWhenDataExistInCache()
    {
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $metadataFactory = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory')
            ->disableOriginalConstructor()
            ->setMethods(['getCacheDriver', 'getAllMetadata'])
            ->getMockForAbstractClass();
        $cacheDriver = $this->getMock('Doctrine\Common\Cache\Cache');

        $expectedResult = [
            new ShortClassMetadata('Test\Entity1'),
            new ShortClassMetadata('Test\Entity2', true),
            new ShortClassMetadata('Test\Entity3'),
        ];

        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getCacheDriver')
            ->willReturn($cacheDriver);
        $cacheDriver->expects($this->once())
            ->method('fetch')
            ->with(ShortMetadataProvider::ALL_SHORT_METADATA_CACHE_KEY)
            ->willReturn($expectedResult);
        $cacheDriver->expects($this->never())
            ->method('save');
        $metadataFactory->expects($this->never())
            ->method('getAllMetadata');

        $provider = new ShortMetadataProvider();

        $this->assertEquals(
            $expectedResult,
            $provider->getAllShortMetadata($manager)
        );
        // test that the result is cached locally
        $this->assertEquals(
            [
                new ShortClassMetadata('Test\Entity1'),
                new ShortClassMetadata('Test\Entity2', true),
                new ShortClassMetadata('Test\Entity3'),
            ],
            $provider->getAllShortMetadata($manager)
        );
    }

    public function testGetAllShortMetadataWithoutCache()
    {
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $metadataFactory = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory')
            ->disableOriginalConstructor()
            ->setMethods(['getCacheDriver', 'getAllMetadata'])
            ->getMockForAbstractClass();

        $metadata1 = new ClassMetadata('Test\Entity1');
        $metadata2 = new ClassMetadata('Test\Entity2');
        $metadata2->isMappedSuperclass = true;
        $metadata3 = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata3->expects($this->once())->method('getName')->willReturn('Test\Entity3');

        $expectedResult = [
            new ShortClassMetadata('Test\Entity1'),
            new ShortClassMetadata('Test\Entity2', true),
            new ShortClassMetadata('Test\Entity3'),
        ];

        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getCacheDriver')
            ->willReturn(null);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([$metadata1, $metadata2, $metadata3]);

        $provider = new ShortMetadataProvider();

        $this->assertEquals(
            [
                new ShortClassMetadata('Test\Entity1'),
                new ShortClassMetadata('Test\Entity2', true),
                new ShortClassMetadata('Test\Entity3'),
            ],
            $provider->getAllShortMetadata($manager)
        );
        // test that the result is cached locally
        $this->assertEquals(
            $expectedResult,
            $provider->getAllShortMetadata($manager)
        );
    }

    public function testGetAllShortMetadataWithoutCacheAndIgnoreExceptionsRequested()
    {
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $metadataFactory = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory')
            ->disableOriginalConstructor()
            ->setMethods(['getCacheDriver', 'getAllMetadata'])
            ->getMockForAbstractClass();

        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getCacheDriver')
            ->willReturn(null);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willThrowException(new \ReflectionException());

        $provider = new ShortMetadataProvider();

        $this->assertEquals(
            [],
            $provider->getAllShortMetadata($manager, false)
        );
    }
}
