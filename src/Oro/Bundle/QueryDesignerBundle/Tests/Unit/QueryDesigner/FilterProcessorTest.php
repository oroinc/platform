<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FilterProcessor;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTest;

class FilterProcessorTest extends OrmQueryConverterTest
{
    /** @var RestrictionBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $restrictionBuilder;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    protected function setUp()
    {
        $this->em = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->restrictionBuilder = $this
            ->getMockBuilder('Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests that filterProcessor converts AbstractQueryConverter's filters to
     * RestrictionBuilder filters configs.
     */
    public function testProcess()
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

        $this->restrictionBuilder
            ->expects($this->once())
            ->method('buildRestrictions')
            ->with($builderFilters, new GroupingOrmFilterDatasourceAdapter($qb));

        $processor->process($qb, 'TestEntityClass', $filters, 'test');

        $reflection = new \ReflectionObject($processor);
        $property   = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($processor);

        $this->assertEquals($builderFilters, $filters);
    }
}
