<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTestCase;
use Oro\Bundle\SegmentBundle\Query\FilterProcessor;

class FilterProcessorTest extends OrmQueryConverterTestCase
{
    public function testConvertQueryDesignerFilters()
    {
        $restrictionBuilder = $this->createMock(RestrictionBuilderInterface::class);
        $processor = new FilterProcessor(
            $this->getFunctionProvider(),
            $this->getVirtualFieldProvider(),
            $this->getVirtualRelationProvider(),
            $this->getDoctrineHelper(),
            $restrictionBuilder
        );
        $filters = [
            'filters' => [
                [
                    'columnName' => 'testColumn',
                    'criterion' => [
                        'filter' => 'string',
                        'data' => ['value' => 'a', 'type' => '1']
                    ]
                ]
            ]
        ];
        $builderFilters = [
            [
                [
                    'column'     => 'test.testColumn',
                    'filter'     => 'string',
                    'filterData' => [
                        'value' => 'a',
                        'type'  => '1'
                    ]
                ]
            ]
        ];
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $restrictionBuilder->expects($this->once())
            ->method('buildRestrictions')
            ->with($builderFilters, new GroupingOrmFilterDatasourceAdapter($qb));

        $processor->process($qb, 'TestEntityClass', $filters, 'test');
    }
}
