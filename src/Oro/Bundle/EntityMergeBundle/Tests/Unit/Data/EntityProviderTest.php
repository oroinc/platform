<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Data;

use Oro\Bundle\EntityMergeBundle\Data\EntityProvider;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use PHPUnit_Framework_MockObject_MockObject;

class EntityProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityProvider $target
     */
    private $target;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject $fakeEntityManager
     */
    private $fakeEntityManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject $fakeEntityManager
     */
    private $fakeMetadata;

    private $fakeIdentifier;

    private $fakeEntityRepositoryName;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject $fakeEntityManager
     */
    private $fakeRepository;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject $fakeQueryBuilder
     */
    private $fakeQueryBuilder;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject $fakeQueryBuilder
     */
    private $fakeQuery;

    private $fakeEntities;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject $fakeQueryBuilder
     */
    private $fakeExpression;

    public function setUp()
    {
        $this->createFakeDependencies();

        $this->setUpFakeObjects();

        $this->setDefaultValues();

        $this->target = new EntityProvider($this->fakeEntityManager);
    }

    private function setDefaultValues()
    {
        $this->fakeEntityRepositoryName = 'testEntityName';
        $this->fakeIdentifier = 'id';
    }

    private function createFakeDependencies()
    {
        $this->fakeEntityManager = $this->getMock('\Doctrine\ORM\EntityManager', array(), array(), '', false);
        $this->fakeMetadata = $this->getMock('\Doctrine\ORM\Mapping\ClassMetadata', array(), array(), '', false);
        $this->fakeRepository = $this->getMock('\Doctrine\ORM\EntityRepository', array(), array(), '', false);
        $this->fakeQueryBuilder = $this->getMock('\Doctrine\ORM\QueryBuilder', array(), array(), '', false);
        $this->fakeQuery = $this->getMockForAbstractClass(
            '\Doctrine\ORM\AbstractQuery',
            array(),
            '',
            false,
            false,
            true,
            array('execute')
        );
        $this->fakeExpression = $this->getMock('\Doctrine\ORM\Query\Expr', array(), array(), '', false);
    }

    /**
     * @return array
     */
    private function setUpFakeObjects()
    {
        $fakeIdentifier = & $this->fakeIdentifier;

        $this->fakeMetadata->expects($this->any())->method('getSingleIdentifierFieldName')->will(
            $this->returnCallback(
                function () use (&$fakeIdentifier) {
                    return $fakeIdentifier;
                }
            )
        );
        $this->fakeEntityManager->expects($this->any())->method('getClassMetadata')->will(
            $this->returnValue($this->fakeMetadata)
        );

        $this->fakeEntityManager->expects($this->any())->method('getRepository')->will(
            $this->returnValue($this->fakeRepository)
        );
        $fakeEntityRepositoryName = & $this->fakeEntityRepositoryName;
        $this->fakeRepository->expects($this->any())->method('getClassName')->will(
            $this->returnCallback(
                function () use (&$fakeEntityRepositoryName) {
                    return $fakeEntityRepositoryName;
                }
            )
        );
        $this->fakeRepository->expects($this->any())->method('createQueryBuilder')->will(
            $this->returnValue($this->fakeQueryBuilder)
        );

        $this->queryMatcher = function () {
            return true;
        };
        $this->fakeQueryBuilder->expects($this->any())->method('add')->with(
            $this->equalTo('where')
        )->will($this->returnValue($this->fakeQueryBuilder));

        $this->fakeQueryBuilder->expects($this->any())->method('expr')->will($this->returnValue($this->fakeExpression));

        $this->fakeQuery->expects($this->any())->method('execute')->will($this->returnValue($this->fakeEntities));

        $this->fakeQueryBuilder->expects($this->any())->method('getQuery')->will($this->returnValue($this->fakeQuery));
    }

    public function testGetEntityIdentifierReturnCorrectData()
    {
        $this->fakeIdentifier = 'test_primary_key';

        $actual = $this->target->getEntityIdentifier('testClassName');

        $this->assertEquals('test_primary_key', $actual);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Incorrect repository returned
     */
    public function testGetRepositoryShouldGenerateExceptionIfRepositoryIsIncorrect()
    {
        $this->fakeEntityRepositoryName = 'notThisName';

        $this->target->getEntitiesByIds('testEntityName', array());
    }

    public function testGetRepositoryShouldNotGenerateExceptionIfRepositoryIsIncorrect()
    {
        $this->target->getEntitiesByIds('testEntityName', array());
    }

    public function testGetEntitiesByIdsMustTryToAddWhereInExpressionToQueryBuilder()
    {
        $this->fakeIdentifier = 'testIdentifier';
        $this->fakeExpression->expects($this->once())->method('in')->with(
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
