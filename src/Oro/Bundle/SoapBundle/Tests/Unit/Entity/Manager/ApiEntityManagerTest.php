<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager\Stub\Entity;

class ApiEntityManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param  string                                   $class
     * @param  \PHPUnit\Framework\MockObject\MockObject $metadata
     * @param  \PHPUnit\Framework\MockObject\MockObject $objectManager
     * @return ApiEntityManager
     */
    protected function createApiEntityManager($class, $metadata = null, $objectManager = null)
    {
        if (!$metadata) {
            $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
                ->disableOriginalConstructor()
                ->setMethods(array('getName'))
                ->getMock();
        }
        $metadata->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($class));

        if (!$objectManager) {
            $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->setMethods(array('getClassMetadata'))
                ->getMock();
        }
        $objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($class)
            ->will($this->returnValue($metadata));

        return new ApiEntityManager($class, $objectManager);
    }

    public function testGetEntityId()
    {
        $className = 'Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager\Stub\Entity';

        $entity = new Entity();
        $entity->id = 1;
        $entity->name = 'entityName';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setConstructorArgs(array($className))
            ->setMethods(['getSingleIdentifierFieldName', 'getIdentifierValues', 'getName'])
            ->getMock();
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->will($this->returnValue(array('id' => $entity->id)));

        $manager = $this->createApiEntityManager($className, $metadata);
        $this->assertEquals($entity->id, $manager->getEntityId($entity));
    }

    /**
     * Test getListQueryBuilder with criteria as an array
     */
    public function testGetSimpleFilteredList()
    {
        $className = 'Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager\Stub\Entity';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setConstructorArgs(array($className))
            ->setMethods(array('getIdentifierFieldNames', 'getIdentifierValues', 'getName'))
            ->getMock();

        $metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));

        $queryBuilder = $this->getMockBuilder('\Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getClassMetadata', 'getRepository', 'getName'))
            ->getMock();

        $criteria = ['gender' => 'male'];
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo($className))
            ->will($this->returnValue($repository));

        $queryBuilder->expects($this->once())
            ->method('addCriteria');

        $manager = $this->createApiEntityManager($className, $metadata, $objectManager);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
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
        $className = 'Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager\Stub\Entity';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setConstructorArgs(array($className))
            ->setMethods(array('getIdentifierFieldNames', 'getIdentifierValues', 'getName'))
            ->getMock();

        $metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));

        $queryBuilder = $this->getMockBuilder('\Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getClassMetadata', 'getRepository'))
            ->getMock();

        $criteria = new Criteria();
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo($className))
            ->will($this->returnValue($repository));

        $queryBuilder->expects($this->once())
            ->method('addCriteria')
            ->with($this->equalTo($criteria));

        $manager = $this->createApiEntityManager($className, $metadata, $objectManager);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $eventDispatcher->expects($this->once())
            ->method('dispatch');
        $manager->setEventDispatcher($eventDispatcher);

        $result = $manager->getListQueryBuilder(3, 1, $criteria);
        $this->assertSame($result, $queryBuilder);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage xpected instance of \DateTime
     */
    public function testGetEntityIdIncorrectInstance()
    {
        $manager = $this->createApiEntityManager('\DateTime');
        $manager->getEntityId(new Entity());
    }
}
