<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\ShortClassMetadata;
use Oro\Bundle\EntityBundle\ORM\ShortMetadataProvider;

class ShortMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAllShortMetadataWithEmptyCache()
    {
        $manager = $this->createMock(ObjectManager::class);
        $metadataFactory = $this->createMock(AbstractClassMetadataFactory::class);

        $metadata1 = new ClassMetadata('Test\Entity1');
        $metadata2 = new ClassMetadata('Test\Entity2');
        $metadata2->isMappedSuperclass = true;
        $metadata3 = $this->createMock(ClassMetadataInterface::class);
        $metadata3->expects($this->once())
            ->method('getName')
            ->willReturn('Test\Entity3');
        $metadata11 = new ClassMetadata('Test\Entity11');

        $expectedResult = [
            new ShortClassMetadata('Test\Entity1'),
            new ShortClassMetadata('Test\Entity11'),
            new ShortClassMetadata('Test\Entity2', true),
            new ShortClassMetadata('Test\Entity3'),
        ];

        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([$metadata1, $metadata2, $metadata3, $metadata11]);

        $provider = new ShortMetadataProvider();

        $this->assertEquals(
            $expectedResult,
            $provider->getAllShortMetadata($manager)
        );
        // test that the result is cached locally
        $this->assertEquals(
            [
                new ShortClassMetadata('Test\Entity1'),
                new ShortClassMetadata('Test\Entity11'),
                new ShortClassMetadata('Test\Entity2', true),
                new ShortClassMetadata('Test\Entity3'),
            ],
            $provider->getAllShortMetadata($manager)
        );
    }

    public function testGetAllShortMetadataWithoutCache()
    {
        $manager = $this->createMock(ObjectManager::class);
        $metadataFactory = $this->createMock(AbstractClassMetadataFactory::class);

        $metadata1 = new ClassMetadata('Test\Entity1');
        $metadata2 = new ClassMetadata('Test\Entity2');
        $metadata2->isMappedSuperclass = true;
        $metadata3 = $this->createMock(ClassMetadataInterface::class);
        $metadata3->expects($this->once())
            ->method('getName')
            ->willReturn('Test\Entity3');
        $metadata11 = new ClassMetadata('Test\Entity11');

        $expectedResult = [
            new ShortClassMetadata('Test\Entity1'),
            new ShortClassMetadata('Test\Entity11'),
            new ShortClassMetadata('Test\Entity2', true),
            new ShortClassMetadata('Test\Entity3'),
        ];

        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([$metadata1, $metadata2, $metadata3, $metadata11]);

        $provider = new ShortMetadataProvider();

        $this->assertEquals(
            $expectedResult,
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
        $metadataFactory = $this->createMock(AbstractClassMetadataFactory::class);

        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
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
        $metadataFactory = $this->createMock(AbstractClassMetadataFactory::class);

        $exception = new \ReflectionException('some exception');
        $this->expectException(get_class($exception));

        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willThrowException($exception);

        $provider = new ShortMetadataProvider();
        $provider->getAllShortMetadata($manager);
    }
}
