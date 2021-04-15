<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmExpressionBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\ManyToManyFilter;
use Symfony\Component\Form\FormFactoryInterface;

class ManyToManyFilterTest extends \PHPUnit\Framework\TestCase
{
    protected $manyToManyfilter;

    public function setUp()
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $filterUtility = $this->createMock(FilterUtility::class);

        $this->manyToManyfilter = new ManyToManyFilter($formFactory, $filterUtility);
    }

    public function testApplyShouldThrowExceptionIfWrongDatasourceTypeIsGiven()
    {
        $this->expectException(\LogicException::class);
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $this->manyToManyfilter->apply($ds, ['type' => FilterUtility::TYPE_EMPTY]);
    }

    public function testApplyEmptyType()
    {
        $ds = $this->createMock(OrmFilterDatasourceAdapter::class);

        $data = [
            'type' => FilterUtility::TYPE_EMPTY
        ];

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->with('entity')
            ->willReturn($metadata);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);
        $qb->expects($this->any())
            ->method('getDqlPart')
            ->willReturn([]);
        $qb->expects($this->any())
            ->method('getRootAliases')
            ->willReturn([]);
        $qb->expects($this->any())
            ->method('getRootEntities')
            ->willReturn([]);

        $expressionBuilder = $this->createMock(OrmExpressionBuilder::class);
        $expressionBuilder->expects($this->once())
            ->method('isNull')
            ->with('alias.id')
            ->willReturn($expressionBuilder);

        $ds->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $ds->expects($this->once())
            ->method('expr')
            ->willReturn($expressionBuilder);
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
        $ds = $this->createMock(OrmFilterDatasourceAdapter::class);
        $data = [
            'type' => FilterUtility::TYPE_NOT_EMPTY,
        ];

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->with('entity')
            ->willReturn($metadata);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);
        $qb->expects($this->any())
            ->method('getDqlPart')
            ->willReturn([]);
        $qb->expects($this->any())
            ->method('getRootAliases')
            ->willReturn([]);
        $qb->expects($this->any())
            ->method('getRootEntities')
            ->willReturn([]);

        $expressionBuilder = $this->createMock(OrmExpressionBuilder::class);
        $expressionBuilder->expects($this->once())
            ->method('isNotNull')
            ->with('alias.id')
            ->willReturn($expressionBuilder);

        $ds->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $ds->expects($this->once())
            ->method('expr')
            ->willReturn($expressionBuilder);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($expressionBuilder);

        $this->manyToManyfilter->init('name', [
            FilterUtility::DATA_NAME_KEY => 'alias.entity',
        ]);

        $this->manyToManyfilter->apply($ds, $data);
    }
}
