<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\ShortClassMetadata;
use Oro\Bundle\EntityBundle\ORM\ShortMetadataProvider;

class ShortMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|AbstractClassMetadataFactory
     */
    private function getClassMetadataFactory()
    {
        return $this->getMockBuilder(AbstractClassMetadataFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCacheDriver', 'getAllMetadata'])
            ->getMockForAbstractClass();
    }

    public function testGetAllShortMetadataWithEmptyCache()
    {
        $manager = $this->createMock(ObjectManager::class);
        $metadataFactory = $this->getClassMetadataFactory();
        $cacheDriver = $this->createMock(Cache::class);

        $metadata1 = new ClassMetadata('Test\Entity1');
        $metadata2 = new ClassMetadata('Test\Entity2');
        $metadata2->isMappedSuperclass = true;
        $metadata3 = $this->createMock(ClassMetadataInterface::class);
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
            ->with('oro_entity.all_short_metadata')
            ->willReturn(false);
        $cacheDriver->expects($this->once())
            ->method('save')
            ->with('oro_entity.all_short_metadata', $expectedResult);
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
        $manager = $this->createMock(ObjectManager::class);
        $metadataFactory = $this->getClassMetadataFactory();
        $cacheDriver = $this->createMock(Cache::class);

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
            ->with('oro_entity.all_short_metadata')
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
        $manager = $this->createMock(ObjectManager::class);
        $metadataFactory = $this->getClassMetadataFactory();

        $metadata1 = new ClassMetadata('Test\Entity1');
        $metadata2 = new ClassMetadata('Test\Entity2');
        $metadata2->isMappedSuperclass = true;
        $metadata3 = $this->createMock(ClassMetadataInterface::class);
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

    public function testGetAllShortMetadataWhenLoadMetadataThrowsExceptionIgnoreExceptionsIsRequested()
    {
        $manager = $this->createMock(ObjectManager::class);
        $metadataFactory = $this->getClassMetadataFactory();
        $cacheDriver = $this->createMock(Cache::class);

        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getCacheDriver')
            ->willReturn($cacheDriver);
        $cacheDriver->expects($this->once())
            ->method('fetch')
            ->with('oro_entity.all_short_metadata')
            ->willReturn(false);
        $cacheDriver->expects($this->never())
            ->method('save');
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willThrowException(new \ReflectionException());

        $provider = new ShortMetadataProvider();

        $this->assertSame(
            [],
            $provider->getAllShortMetadata($manager, false)
        );
    }

    public function testGetAllShortMetadataWhenLoadMetadataThrowsExceptionIgnoreExceptionsIsNotRequested()
    {
        $manager = $this->createMock(ObjectManager::class);
        $metadataFactory = $this->getClassMetadataFactory();
        $cacheDriver = $this->createMock(Cache::class);

        $exception = new \ReflectionException('some exception');
        $this->expectException(get_class($exception));

        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getCacheDriver')
            ->willReturn($cacheDriver);
        $cacheDriver->expects($this->once())
            ->method('fetch')
            ->with('oro_entity.all_short_metadata')
            ->willReturn(false);
        $cacheDriver->expects($this->never())
            ->method('save');
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willThrowException($exception);

        $provider = new ShortMetadataProvider();
        $provider->getAllShortMetadata($manager);
    }
}
