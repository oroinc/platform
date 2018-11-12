<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\ManyToManyFilter;

class ManyToManyFilterTest extends \PHPUnit\Framework\TestCase
{
    protected $manyToManyfilter;

    public function setUp()
    {
        $formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');
        $filterUtility = $this->getMockBuilder('Oro\Bundle\FilterBundle\Filter\FilterUtility')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manyToManyfilter = new ManyToManyFilter($formFactory, $filterUtility);
    }

    /**
     * @expectedException LogicException
     */
    public function testApplyShouldThrowExceptionIfWrongDatasourceTypeIsGiven()
    {
        $ds = $this->createMock('Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface');
        $this->manyToManyfilter->apply($ds, ['type' => FilterUtility::TYPE_EMPTY]);
    }

    public function testApplyEmptyType()
    {
        $ds = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter')
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            'type' => FilterUtility::TYPE_EMPTY,
        ];

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(['id']));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->with('entity')
            ->will($this->returnValue($metadata));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));
        $qb->expects($this->any())
            ->method('getDqlPart')
            ->will($this->returnValue([]));
        $qb->expects($this->any())
            ->method('getRootAliases')
            ->will($this->returnValue([]));

        $expressionBuilder = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmExpressionBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $expressionBuilder->expects($this->once())
            ->method('isNull')
            ->with('alias.id')
            ->will($this->returnValue($expressionBuilder));

        $ds->expects($this->any())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));
        $ds->expects($this->once())
            ->method('expr')
            ->will($this->returnValue($expressionBuilder));
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($expressionBuilder);

        $this->manyToManyfilter->init('name', [
            FilterUtility::DATA_NAME_KEY => 'alias.entity',
        ]);

        $this->manyToManyfilter->apply($ds, $data);
    }

    public function testApplyNotEmptyType()
    {
        $ds = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter')
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            'type' => FilterUtility::TYPE_NOT_EMPTY,
        ];

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(['id']));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->with('entity')
            ->will($this->returnValue($metadata));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));
        $qb->expects($this->any())
            ->method('getDqlPart')
            ->will($this->returnValue([]));
        $qb->expects($this->any())
            ->method('getRootAliases')
            ->will($this->returnValue([]));

        $expressionBuilder = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmExpressionBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $expressionBuilder->expects($this->once())
            ->method('isNotNull')
            ->with('alias.id')
            ->will($this->returnValue($expressionBuilder));

        $ds->expects($this->any())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));
        $ds->expects($this->once())
            ->method('expr')
            ->will($this->returnValue($expressionBuilder));
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($expressionBuilder);

        $this->manyToManyfilter->init('name', [
            FilterUtility::DATA_NAME_KEY => 'alias.entity',
        ]);

        $this->manyToManyfilter->apply($ds, $data);
    }
}
