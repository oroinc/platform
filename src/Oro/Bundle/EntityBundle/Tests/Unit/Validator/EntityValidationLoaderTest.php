<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Validator;

use Doctrine\ORM\Configuration as EntityManagerConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata as EntityClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory as EntityClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Tests\Unit\Stub\Entity1;
use Oro\Bundle\EntityBundle\Tests\Unit\Stub\Entity2;
use Oro\Bundle\EntityBundle\Tests\Unit\Stub\Entity3;
use Oro\Bundle\EntityBundle\Tests\Unit\Stub\Entity4;
use Oro\Bundle\EntityBundle\Tests\Unit\Stub\Entity5;
use Oro\Bundle\EntityBundle\Tests\Unit\Stub\Proxies\__CG__\Oro\Bundle\EntityBundle\Tests\Unit\Stub\Entity1 as En1Proxy;
use Oro\Bundle\EntityBundle\Validator\EntityValidationLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class EntityValidationLoaderTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityValidationLoader $loader;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->loader = new EntityValidationLoader($this->doctrine);
    }

    public function testLoadClassMetadata(): void
    {
        $this->doctrine->expects(self::never())
            ->method(self::anything());

        self::assertFalse($this->loader->loadClassMetadata($this->createMock(ClassMetadata::class)));
    }

    public function testGetMappedClassesWhenAutoGenerateProxyClassesDisabled(): void
    {
        $manager1 = $this->createMock(ObjectManager::class);
        $manager2 = $this->createMock(EntityManagerInterface::class);

        $this->doctrine->expects(self::once())
            ->method('getManagers')
            ->willReturn([$manager1, $manager2]);

        $manager1->expects(self::never())
            ->method(self::anything());

        $manager2Configuration = $this->createMock(EntityManagerConfiguration::class);
        $manager2->expects(self::exactly(2))
            ->method('getConfiguration')
            ->willReturn($manager2Configuration);
        $manager2->expects(self::once())
            ->method('getMetadataFactory')
            ->willReturn($this->initializeManagerMetadataFactory());
        $manager2Configuration->expects(self::once())
            ->method('getProxyNamespace')
            ->willReturn('Oro\Bundle\EntityBundle\Tests\Unit\Stub\Proxies');
        $manager2Configuration->expects(self::once())
            ->method('getAutoGenerateProxyClasses')
            ->willReturn(ProxyFactory::AUTOGENERATE_NEVER);

        self::assertEquals(
            [
                Entity1::class,
                En1Proxy::class,
                Entity2::class,
                Entity3::class,
                Entity4::class,
                Entity5::class,
                PersistentCollection::class,
                Proxy::class,
                \Doctrine\Persistence\Proxy::class,
                \Doctrine\Common\Proxy\Proxy::class
            ],
            $this->loader->getMappedClasses()
        );
    }

    public function testGetMappedClassesWhenAutoGenerateProxyClassesEnabled(): void
    {
        $manager1 = $this->createMock(ObjectManager::class);
        $manager2 = $this->createMock(EntityManagerInterface::class);

        $this->doctrine->expects(self::once())
            ->method('getManagers')
            ->willReturn([$manager1, $manager2]);

        $manager1->expects(self::never())
            ->method(self::anything());

        $manager2Configuration = $this->createMock(EntityManagerConfiguration::class);
        $manager2->expects(self::exactly(2))
            ->method('getConfiguration')
            ->willReturn($manager2Configuration);
        $manager2->expects(self::once())
            ->method('getMetadataFactory')
            ->willReturn($this->initializeManagerMetadataFactory());
        $manager2Configuration->expects(self::once())
            ->method('getProxyNamespace')
            ->willReturn('Oro\Bundle\EntityBundle\Tests\Unit\Stub\Proxies');
        $manager2Configuration->expects(self::once())
            ->method('getAutoGenerateProxyClasses')
            ->willReturn(ProxyFactory::AUTOGENERATE_ALWAYS);

        self::assertEquals(
            [
                Entity1::class,
                Entity2::class,
                Entity3::class,
                Entity4::class,
                Entity5::class,
                PersistentCollection::class,
                Proxy::class,
                \Doctrine\Persistence\Proxy::class,
                \Doctrine\Common\Proxy\Proxy::class
            ],
            $this->loader->getMappedClasses()
        );
    }

    private function initializeManagerMetadataFactory(): EntityClassMetadataFactory
    {
        // regular entity with proxy
        $entityMetadata1 = new EntityClassMetadata(Entity1::class);
        $entityMetadata1->isMappedSuperclass = false;
        $entityMetadata1->isEmbeddedClass = false;
        $entityMetadata1->reflClass = new \ReflectionClass($entityMetadata1->getName());
        // regular entity without proxy
        $entityMetadata2 = new EntityClassMetadata(Entity2::class);
        $entityMetadata2->isMappedSuperclass = false;
        $entityMetadata2->isEmbeddedClass = false;
        $entityMetadata2->reflClass = new \ReflectionClass($entityMetadata2->getName());
        // abstract class
        $entityMetadata3 = new EntityClassMetadata(Entity3::class);
        $entityMetadata3->isMappedSuperclass = false;
        $entityMetadata3->isEmbeddedClass = false;
        $entityMetadata3->reflClass = new \ReflectionClass($entityMetadata3->getName());
        // mapped superclass
        $entityMetadata4 = new EntityClassMetadata(Entity4::class);
        $entityMetadata4->isMappedSuperclass = true;
        $entityMetadata4->isEmbeddedClass = false;
        // embedded class
        $entityMetadata5 = new EntityClassMetadata(Entity5::class);
        $entityMetadata5->isMappedSuperclass = false;
        $entityMetadata5->isEmbeddedClass = true;

        $manager2MetadataFactory = $this->createMock(EntityClassMetadataFactory::class);
        $manager2MetadataFactory->expects(self::once())
            ->method('getAllMetadata')
            ->willReturn([
                $entityMetadata1,
                $entityMetadata2,
                $entityMetadata3,
                $entityMetadata4,
                $entityMetadata5
            ]);

        return $manager2MetadataFactory;
    }
}
