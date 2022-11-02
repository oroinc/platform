<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager\Stub\Entity;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ApiEntityManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param  string                                   $class
     * @param  \PHPUnit\Framework\MockObject\MockObject $metadata
     * @param  \PHPUnit\Framework\MockObject\MockObject $objectManager
     * @return ApiEntityManager
     */
    private function createApiEntityManager($class, $metadata = null, $objectManager = null)
    {
        if (!$metadata) {
            $metadata = $this->createMock(ClassMetadata::class);
        }
        $metadata->expects($this->any())
            ->method('getName')
            ->willReturn($class);

        if (!$objectManager) {
            $objectManager = $this->createMock(EntityManager::class);
        }
        $objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($class)
            ->willReturn($metadata);

        return new ApiEntityManager($class, $objectManager);
    }

    public function testGetEntityId()
    {
        $className = Entity::class;

        $entity = new Entity();
        $entity->id = 1;
        $entity->name = 'entityName';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->willReturn(['id' => $entity->id]);

        $manager = $this->createApiEntityManager($className, $metadata);
        $this->assertEquals($entity->id, $manager->getEntityId($entity));
    }

    /**
     * Test getListQueryBuilder with criteria as an array
     */
    public function testGetSimpleFilteredList()
    {
        $className = Entity::class;

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $objectManager = $this->createMock(EntityManager::class);

        $criteria = ['gender' => 'male'];
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with($className)
            ->willReturn($repository);

        $queryBuilder->expects($this->once())
            ->method('addCriteria');

        $manager = $this->createApiEntityManager($className, $metadata, $objectManager);

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->once())
            ->method('dispatch');
        $manager->setEventDispatcher($eventDispatcher);

        $result = $manager->getListQueryBuilder(3, 1, $criteria);

        $this->assertSame($result, $queryBuilder);
    }

    /**
     * Test getListQueryBuilder with criteria as Criteria instance
     */
    public function testGetCriteriaFilteredList()
    {
        $className = Entity::class;

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $objectManager = $this->createMock(EntityManager::class);

        $criteria = new Criteria();
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with($className)
            ->willReturn($repository);

        $queryBuilder->expects($this->once())
            ->method('addCriteria')
            ->with($criteria);

        $manager = $this->createApiEntityManager($className, $metadata, $objectManager);

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->once())
            ->method('dispatch');
        $manager->setEventDispatcher($eventDispatcher);

        $result = $manager->getListQueryBuilder(3, 1, $criteria);
        $this->assertSame($result, $queryBuilder);
    }

    public function testGetEntityIdIncorrectInstance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instance of DateTime');

        $manager = $this->createApiEntityManager(\DateTime::class);
        $manager->getEntityId(new Entity());
    }
}
