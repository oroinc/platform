<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Doctrine\DoctrineHelper;

use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class DoctrineHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper $target
     */
    private $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeEntityManager
     */
    private $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeEntityManager
     */
    private $metadataFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeEntityManager
     */
    private $metadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $queryBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $query;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $expression;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()->getMock();

        $this->metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()->getMock();

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()
            ->getMock();

        $this->query = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()->setMethods(array('execute'))
            ->getMockForAbstractClass();

        $this->expression = $this->getMock('\Doctrine\ORM\Query\Expr', array(), array(), '', false);

        $this->doctrineHelper = new DoctrineHelper($this->entityManager);
    }

    public function testGetEntityRepository()
    {
        $entityName = 'TestEntity';

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with($entityName)
            ->will($this->returnValue($this->repository));

        $this->assertEquals($this->repository, $this->doctrineHelper->getEntityRepository($entityName));
    }

    public function testGetEntitiesByIds()
    {
        $entityIds = array(1, 2, 3);
        $entities = array(new \stdClass());
        $identifier = 'id';

        $className = 'TestEntity';

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with($className)
            ->will($this->returnValue($this->repository));

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($className)
            ->will($this->returnValue($this->metadata));

        $this->metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue($identifier));

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('entity')
            ->will($this->returnValue($this->queryBuilder));

        $this->queryBuilder->expects($this->once())
            ->method('expr')
            ->will($this->returnValue($this->expression));

        $inExpression = $this->getMockBuilder('Doctrine\ORM\Query\Expr\Func')
            ->disableOriginalConstructor()
            ->getMock();

        $this->expression->expects($this->once())
            ->method('in')
            ->with('entity.' . $identifier, $entityIds)
            ->will($this->returnValue($inExpression));

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with($inExpression);

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($this->query));

        $this->query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($entities));

        $this->assertEquals($entities, $this->doctrineHelper->getEntitiesByIds($className, $entityIds));
    }

    public function testGetSingleIdentifierFieldName()
    {
        $entityClass = 'stdClass';
        $identifier = 'id';

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->will($this->returnValue($this->metadata));

        $this->metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue($identifier));

        $this->assertEquals($identifier, $this->doctrineHelper->getSingleIdentifierFieldName($entityClass));
    }

    public function testGetEntityIdentifierValue()
    {
        $entityClass = 'stdClass';
        $entity = new \stdClass();
        $identifiers = array('id' => 1);

        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($this->metadataFactory));

        $this->metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($entityClass)
            ->will($this->returnValue($this->metadata));

        $this->metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->will($this->returnValue($identifiers));

        $this->assertEquals($identifiers['id'], $this->doctrineHelper->getEntityIdentifierValue($entity));
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Multiple id is not supported.
     */
    public function testGetEntityIdentifierValueFails()
    {
        $entityClass = 'stdClass';
        $entity = new \stdClass();
        $identifiers = array('id1' => 1, 'id2' => 2);

        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($this->metadataFactory));

        $this->metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($entityClass)
            ->will($this->returnValue($this->metadata));

        $this->metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->will($this->returnValue($identifiers));

        $this->doctrineHelper->getEntityIdentifierValue($entity);
    }

    public function testGetEntityIds()
    {
        $entityClass = 'stdClass';
        $fooEntity = new \stdClass();
        $fooEntity->id = 1;
        $barEntity = new \stdClass();
        $barEntity->id = 2;
        $entities = array($fooEntity, $barEntity);
        $expectedIdentifiers = array($fooEntity->id, $barEntity->id);

        $this->entityManager->expects($this->exactly(2))
            ->method('getMetadataFactory')
            ->will($this->returnValue($this->metadataFactory));

        $this->metadataFactory->expects($this->exactly(2))
            ->method('getMetadataFor')
            ->with($entityClass)
            ->will($this->returnValue($this->metadata));

        $this->metadata->expects($this->at(0))
            ->method('getIdentifierValues')
            ->with($fooEntity)
            ->will($this->returnValue(array('id' => $fooEntity->id)));

        $this->metadata->expects($this->at(1))
            ->method('getIdentifierValues')
            ->with($barEntity)
            ->will($this->returnValue(array('id' => $barEntity->id)));

        $this->assertEquals($expectedIdentifiers, $this->doctrineHelper->getEntityIds($entities));
    }

    /**
     * @dataProvider isEntityEqualDataProvider
     */
    public function testIsEntityEqualForSameClass($firstObject, $firstId, $secondObject, $secondId, $expected)
    {
        $this->entityManager->expects($this->exactly(2))
            ->method('getMetadataFactory')
            ->will($this->returnValue($this->metadataFactory));

        $this->metadataFactory->expects($this->at(0))
            ->method('getMetadataFor')
            ->with(get_class($firstObject))
            ->will($this->returnValue($this->metadata));

        $this->metadata->expects($this->at(0))
            ->method('getIdentifierValues')
            ->with($firstObject)
            ->will($this->returnValue(array('id' => $firstId)));

        $this->metadataFactory->expects($this->at(1))
            ->method('getMetadataFor')
            ->with(get_class($secondObject))
            ->will($this->returnValue($this->metadata));

        $this->metadata->expects($this->at(1))
            ->method('getIdentifierValues')
            ->with($secondObject)
            ->will($this->returnValue(array('id' => $secondId)));

        $this->assertEquals($expected, $this->doctrineHelper->isEntityEqual($firstObject, $secondObject));
    }

    public function isEntityEqualDataProvider()
    {
        return array(
            'equal_class_equal_id' => array(
                'firstObject' => new EntityStub(1),
                'firstId' => 1,
                'secondObject' => new EntityStub(2),
                'secondId' => 1,
                'expected' => true
            ),
            'equal_class_not_equal_id' => array(
                'firstObject' => new EntityStub(1),
                'firstId' => 1,
                'secondObject' => new EntityStub(2),
                'secondId' => 2,
                'expected' => false
            ),
        );
    }

    public function testIsEntityEqualForNotSameClass()
    {
        $this->assertFalse($this->doctrineHelper->isEntityEqual(new EntityStub(), new \stdClass()));
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage $entity argument must be an object, "string" given.
     */
    public function testIsEntityEqualFailsForFirstNotObject()
    {
        $this->doctrineHelper->isEntityEqual('scalar', new \stdClass());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage $other argument must be an object, "string" given.
     */
    public function testIsEntityEqualFailsForSecondNotObject()
    {
        $this->doctrineHelper->isEntityEqual(new \stdClass(), 'scalar');
    }

    public function testGetAllMetadata()
    {
        $className = 'TestEntity';
        $expectedResult = array($this->metadata);

        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($this->metadataFactory));

        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->doctrineHelper->getAllMetadata($className));
    }
}
