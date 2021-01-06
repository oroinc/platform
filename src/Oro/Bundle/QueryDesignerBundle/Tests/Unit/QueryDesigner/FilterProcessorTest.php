<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FilterProcessor;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTest;

class FilterProcessorTest extends OrmQueryConverterTest
{
    /** @var RestrictionBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $restrictionBuilder;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->restrictionBuilder = $this->createMock(RestrictionBuilderInterface::class);
    }

    public function testConvertQueryDesignerFilters()
    {
        $processor = new FilterProcessor(
            $this->getFunctionProvider(),
            $this->getVirtualFieldProvider(),
            $this->getDoctrine(),
            $this->restrictionBuilder
        );
        $filters   = [
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
        $qb = new QueryBuilder($this->em);

        $this->restrictionBuilder->expects($this->once())
            ->method('buildRestrictions')
            ->with($builderFilters, new GroupingOrmFilterDatasourceAdapter($qb));

        $processor->process($qb, 'TestEntityClass', $filters, 'test');

        $reflection = new \ReflectionObject($processor);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($processor);

        $this->assertEquals($builderFilters, $filters);
    }
}
