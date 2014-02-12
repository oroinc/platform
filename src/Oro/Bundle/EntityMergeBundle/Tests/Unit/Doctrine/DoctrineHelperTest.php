<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Doctrine\DoctrineHelper;

use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;

class DoctrineHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper $target
     */
    private $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeEntityManager
     */
    private $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeEntityManager
     */
    private $metadata;

    private $identifier = 'id';

    private $entityRepositoryName = 'testEntityName';

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

    public function setUp()
    {
        $this->createFakeDependencies();

        $this->setUpFakeObjects();

        $this->target = new DoctrineHelper($this->entityManager);
    }


    private function createFakeDependencies()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor(
        )->getMock();
        $this->metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor(
        )->getMock();
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor(
        )->getMock();
        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()
            ->getMock();
        $this->query = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')->disableOriginalConstructor()->setMethods(
            array('execute')
        )->getMockForAbstractClass();
        $this->expression = $this->getMock('\Doctrine\ORM\Query\Expr', array(), array(), '', false);
    }

    /**
     * @return array
     */
    private function setUpFakeObjects()
    {
        $fakeIdentifier = & $this->identifier;

        $this->metadata->expects($this->any())->method('getSingleIdentifierFieldName')->will(
            $this->returnCallback(
                function () use (&$fakeIdentifier) {
                    return $fakeIdentifier;
                }
            )
        );
        $this->entityManager->expects($this->any())->method('getClassMetadata')->will(
            $this->returnValue($this->metadata)
        );

        $this->entityManager->expects($this->any())->method('getRepository')->will(
            $this->returnValue($this->repository)
        );
        $fakeEntityRepositoryName = & $this->entityRepositoryName;
        $this->repository->expects($this->any())->method('getClassName')->will(
            $this->returnCallback(
                function () use (&$fakeEntityRepositoryName) {
                    return $fakeEntityRepositoryName;
                }
            )
        );
        $this->repository->expects($this->any())->method('createQueryBuilder')->will(
            $this->returnValue($this->queryBuilder)
        );

        $this->queryMatcher = function () {
            return true;
        };
        $this->queryBuilder->expects($this->any())->method('add')->with(
            $this->equalTo('where')
        )->will($this->returnValue($this->queryBuilder));

        $this->queryBuilder->expects($this->any())->method('expr')->will($this->returnValue($this->expression));

        $this->query->expects($this->any())->method('execute')->will($this->returnValue(array()));

        $this->queryBuilder->expects($this->any())->method('getQuery')->will($this->returnValue($this->query));
    }

    public function testGetEntityIdentifierReturnCorrectData()
    {
        $this->identifier = 'test_primary_key';

        $actual = $this->target->getEntityIdentifier('testClassName');

        $this->assertEquals('test_primary_key', $actual);
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Incorrect repository returned
     */
    public function testGetRepositoryShouldGenerateExceptionIfRepositoryIsIncorrect()
    {
        $this->entityRepositoryName = 'notThisName';

        $this->target->getEntitiesByIds('testEntityName', array());
    }

    public function testGetRepositoryShouldNotGenerateExceptionIfRepositoryIsIncorrect()
    {
        $this->target->getEntitiesByIds('testEntityName', array());
    }

    public function testGetEntitiesByIdsMustTryToAddWhereInExpressionToQueryBuilder()
    {
        $this->identifier = 'testIdentifier';
        $this->expression->expects($this->once())->method('in')->with(
            $this->equalTo('entity.testIdentifier'),
            $this->callback(
                function ($params) {
                    return $params[0] == 12 && $params[1] == 33 && $params[2] == 55;
                }
            )
        );

        $this->target->getEntitiesByIds('testEntityName', array('12', '33', '55'));
    }
}
