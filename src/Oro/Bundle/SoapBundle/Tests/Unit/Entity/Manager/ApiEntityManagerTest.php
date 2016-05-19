<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager\Stub\Entity;

class ApiEntityManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param  string                                   $class
     * @param  \PHPUnit_Framework_MockObject_MockObject $metadata
     * @param  \PHPUnit_Framework_MockObject_MockObject $objectManager
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
     * Test getList with criteria as an array
     */
    public function testGetSimpleFilteredList()
    {
        $className = 'Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager\Stub\Entity';
        $entity = new Entity();
        $entity->id = 1;
        $entity->name = 'entityName';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setConstructorArgs(array($className))
            ->setMethods(array('getIdentifierFieldNames', 'getIdentifierValues', 'getName'))
            ->getMock();

        $metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));

        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getClassMetadata', 'getRepository', 'getName'))
            ->getMock();

        $criteria = ['gender' => 'male'];
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository
            ->expects($this->once())
            ->method('matching')
            ->will($this->returnValue(new ArrayCollection([$entity])));

        $objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo($className))
            ->will($this->returnValue($repository));

        $manager = $this->createApiEntityManager($className, $metadata, $objectManager);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $eventDispatcher->expects($this->once())
            ->method('dispatch');
        $manager->setEventDispatcher($eventDispatcher);

        $result = $manager->getList(3, 1, $criteria);

        $this->assertSame($result[0], $entity);
    }

    /**
     * Test getList with criteria as Criteria instance
     */
    public function testGetCriteriaFilteredList()
    {
        $className = 'Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager\Stub\Entity';
        $entity = new Entity();
        $entity->id = 1;
        $entity->name = 'entityName';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setConstructorArgs(array($className))
            ->setMethods(array('getIdentifierFieldNames', 'getIdentifierValues', 'getName'))
            ->getMock();

        $metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));

        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getClassMetadata', 'getRepository'))
            ->getMock();

        $criteria = new Criteria();
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository
            ->expects($this->once())
            ->method('matching')
            ->with($this->equalTo($criteria))
            ->will($this->returnValue(new ArrayCollection([$entity])));

        $objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo($className))
            ->will($this->returnValue($repository));

        $manager = $this->createApiEntityManager($className, $metadata, $objectManager);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $eventDispatcher->expects($this->once())
            ->method('dispatch');
        $manager->setEventDispatcher($eventDispatcher);

        $result = $manager->getList(3, 1, $criteria);
        $this->assertSame($result[0], $entity);
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
