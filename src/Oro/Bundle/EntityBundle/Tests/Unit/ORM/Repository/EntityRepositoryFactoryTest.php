<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Repository;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\Repository\EntityRepositoryFactory;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\TestEntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;

class EntityRepositoryFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|MockObject */
    protected $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testGetDefaultRepositoryNoManagerNoRepositoryClass()
    {
        $entityName = 'TestEntity';

        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->customRepositoryClassName = null;

        $doctrineConfiguration = new Configuration();
        $doctrineConfiguration->setDefaultRepositoryClassName(TestEntityRepository::class);

        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->expects(static::any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);
        $entityManager->expects(static::any())
            ->method('getConfiguration')
            ->willReturn($doctrineConfiguration);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(static::any())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($entityManager);

        $this->container->expects(static::any())
            ->method('get')
            ->with('doctrine')
            ->willReturn($managerRegistry);

        $repositoryFactory = new EntityRepositoryFactory($this->container, []);
        /** @var TestEntityRepository $defaultRepository */
        $defaultRepository = $repositoryFactory->getDefaultRepository($entityName);

        static::assertInstanceOf(TestEntityRepository::class, $defaultRepository);
        static::assertEquals($entityName, $defaultRepository->getClassName());
        static::assertEquals($entityManager, $defaultRepository->getEm());
        static::assertEquals($classMetadata, $defaultRepository->getClass());
    }

    public function testGetDefaultRepositoryWithManagerWithRepositoryClass()
    {
        $entityName = 'TestEntity';
        $repositoryClass = TestEntityRepository::class;

        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->customRepositoryClassName = null;

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->expects(static::any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $repositoryFactory = new EntityRepositoryFactory($this->container, []);
        /** @var TestEntityRepository $defaultRepository */
        $defaultRepository = $repositoryFactory->getDefaultRepository($entityName, $repositoryClass, $entityManager);

        static::assertInstanceOf($repositoryClass, $defaultRepository);
        static::assertEquals($entityName, $defaultRepository->getClassName());
        static::assertEquals($entityManager, $defaultRepository->getEm());
        static::assertEquals($classMetadata, $defaultRepository->getClass());
    }

    public function testGetDefaultRepositoryNotManageableEntity()
    {
        $entityName = 'TestEntity';

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(static::any())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn(null);

        $this->container->expects(static::once())
            ->method('get')
            ->with('doctrine')
            ->willReturn($managerRegistry);

        $this->expectException(NotManageableEntityException::class);
        $this->expectExceptionMessage(
            sprintf('Entity class "%s" is not manageable.', $entityName)
        );

        $repositoryFactory = new EntityRepositoryFactory($this->container, []);
        $repositoryFactory->getDefaultRepository($entityName);
    }

    public function testGetRepositoryFromContainer()
    {
        $entityName = 'TestEntity';
        $repositoryService = 'test.entity.repository';

        $entityRepository = $this->getMockBuilder(TestEntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->customRepositoryClassName = TestEntityRepository::class;

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->expects(static::any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $this->container->expects(static::once())
            ->method('get')
            ->with($repositoryService)
            ->willReturn($entityRepository);

        $repositoryFactory = new EntityRepositoryFactory($this->container, [$entityName => $repositoryService]);

        // double check is used to make sure that object is stored in the internal cache
        static::assertEquals($entityRepository, $repositoryFactory->getRepository($entityManager, $entityName));
        static::assertEquals($entityRepository, $repositoryFactory->getRepository($entityManager, $entityName));
    }

    public function testGetRepositoryDefaultRepository()
    {
        $entityName = 'TestEntity';

        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->customRepositoryClassName = TestEntityRepository::class;

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->expects(static::exactly(4))
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $this->container->expects(static::never())
            ->method('get');

        $repositoryFactory = new EntityRepositoryFactory($this->container, []);

        // double check is used to make sure that object is stored in the internal cache
        /** @var TestEntityRepository $repository */
        $repository = $repositoryFactory->getRepository($entityManager, $entityName);
        static::assertEquals($repository, $repositoryFactory->getRepository($entityManager, $entityName));

        static::assertInstanceOf(TestEntityRepository::class, $repository);
        static::assertEquals($entityName, $repository->getClassName());
        static::assertEquals($entityManager, $repository->getEm());
        static::assertEquals($classMetadata, $repository->getClass());
    }

    public function testGetRepositoryFromContainerNotEntityRepository()
    {
        $entityName = 'TestEntity';
        $repositoryService = 'test.entity.repository';

        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->customRepositoryClassName = TestEntityRepository::class;

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->expects(static::any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $entityRepository = new \stdClass();

        $this->container->expects(static::once())
            ->method('get')
            ->with($repositoryService)
            ->willReturn($entityRepository);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Repository for class %s must be instance of EntityRepository', $entityName)
        );

        $repositoryFactory = new EntityRepositoryFactory($this->container, [$entityName => $repositoryService]);
        $repositoryFactory->getRepository($entityManager, $entityName);
    }

    public function testGetRepositoryFromContainerInvalidEntityRepository()
    {
        $entityName = 'TestEntity';
        $repositoryService = 'test.entity.repository';

        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->customRepositoryClassName = TestEntityRepository::class;

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->expects(static::any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $entityRepository = new EntityRepository($entityManager, $classMetadata);

        $this->container->expects(static::once())
            ->method('get')
            ->with($repositoryService)
            ->willReturn($entityRepository);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Repository for class %s must be instance of %s', $entityName, TestEntityRepository::class)
        );

        $repositoryFactory = new EntityRepositoryFactory($this->container, [$entityName => $repositoryService]);
        $repositoryFactory->getRepository($entityManager, $entityName);
    }

    public function testClear()
    {
        $entityName = 'TestEntity';
        $repositoryService = 'test.entity.repository';

        $entityRepository = $this->createMock(TestEntityRepository::class);

        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->customRepositoryClassName = TestEntityRepository::class;

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);
        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with($repositoryService)
            ->willReturn($entityRepository);

        $repositoryFactory = new EntityRepositoryFactory($this->container, [$entityName => $repositoryService]);
        self::assertSame($entityRepository, $repositoryFactory->getRepository($entityManager, $entityName));

        $repositoryFactory->clear();
        self::assertSame($entityRepository, $repositoryFactory->getRepository($entityManager, $entityName));
    }
}
