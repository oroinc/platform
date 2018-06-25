<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\Repository\EntityRepositoryFactory;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\TestEntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;

class EntityRepositoryFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    protected function setUp()
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

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);
        $entityManager->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($doctrineConfiguration);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($entityManager);

        $this->container->expects($this->any())
            ->method('get')
            ->with('doctrine')
            ->willReturn($managerRegistry);

        $repositoryFactory = new EntityRepositoryFactory($this->container, []);
        /** @var TestEntityRepository $defaultRepository */
        $defaultRepository = $repositoryFactory->getDefaultRepository($entityName);

        $this->assertInstanceOf(TestEntityRepository::class, $defaultRepository);
        $this->assertEquals($entityName, $defaultRepository->getClassName());
        $this->assertAttributeEquals($entityManager, '_em', $defaultRepository);
        $this->assertAttributeEquals($classMetadata, '_class', $defaultRepository);
    }

    public function testGetDefaultRepositoryWithManagerWithRepositoryClass()
    {
        $entityName = 'TestEntity';
        $repositoryClass = TestEntityRepository::class;

        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->customRepositoryClassName = null;

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $repositoryFactory = new EntityRepositoryFactory($this->container, []);
        /** @var TestEntityRepository $defaultRepository */
        $defaultRepository = $repositoryFactory->getDefaultRepository($entityName, $repositoryClass, $entityManager);

        $this->assertInstanceOf($repositoryClass, $defaultRepository);
        $this->assertEquals($entityName, $defaultRepository->getClassName());
        $this->assertAttributeEquals($entityManager, '_em', $defaultRepository);
        $this->assertAttributeEquals($classMetadata, '_class', $defaultRepository);
    }

    public function testGetDefaultRepositoryNotManageableEntity()
    {
        $entityName = 'TestEntity';

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn(null);

        $this->container->expects($this->once())
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

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $this->container->expects($this->once())
            ->method('get')
            ->with($repositoryService)
            ->willReturn($entityRepository);

        $repositoryFactory = new EntityRepositoryFactory($this->container, [$entityName => $repositoryService]);

        // double check is used to make sure that object is stored in the internal cache
        $this->assertEquals($entityRepository, $repositoryFactory->getRepository($entityManager, $entityName));
        $this->assertEquals($entityRepository, $repositoryFactory->getRepository($entityManager, $entityName));
    }

    public function testGetRepositoryDefaultRepository()
    {
        $entityName = 'TestEntity';

        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->customRepositoryClassName = TestEntityRepository::class;

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(4))
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $this->container->expects($this->never())
            ->method('get');

        $repositoryFactory = new EntityRepositoryFactory($this->container, []);

        // double check is used to make sure that object is stored in the internal cache
        $repository = $repositoryFactory->getRepository($entityManager, $entityName);
        $this->assertEquals($repository, $repositoryFactory->getRepository($entityManager, $entityName));

        $this->assertInstanceOf(TestEntityRepository::class, $repository);
        $this->assertEquals($entityName, $repository->getClassName());
        $this->assertAttributeEquals($entityManager, '_em', $repository);
        $this->assertAttributeEquals($classMetadata, '_class', $repository);
    }

    public function testGetRepositoryFromContainerNotEntityRepository()
    {
        $entityName = 'TestEntity';
        $repositoryService = 'test.entity.repository';

        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->customRepositoryClassName = TestEntityRepository::class;

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $entityRepository = new \stdClass();

        $this->container->expects($this->once())
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

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $entityRepository = new EntityRepository($entityManager, $classMetadata);

        $this->container->expects($this->once())
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
