<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Datasource;

use Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilder;

class ManyRelationBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManyRelationBuilder */
    protected $builder;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $childBuilder1;

    protected function setUp()
    {
        $this->childBuilder1 = $this->createMock('Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilderInterface');

        $this->builder = new ManyRelationBuilder();
        $this->builder->addBuilder($this->childBuilder1);
    }

    public function testBuildComparisonExpr()
    {
        $ds            = $this->createMock('Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface');
        $fieldName     = 'o.testField';
        $parameterName = 'param1';
        $filterName    = 'testFilter';
        $inverse       = true;

        $this->childBuilder1->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($ds))
            ->will($this->returnValue(true));
        $this->childBuilder1->expects($this->once())
            ->method('buildComparisonExpr')
            ->with($this->identicalTo($ds), $fieldName, $parameterName, $filterName, $inverse);

        $this->builder->buildComparisonExpr($ds, $fieldName, $parameterName, $filterName, $inverse);
    }

    public function testBuildComparisonExprNoAppropriateChildBuilder()
    {
        $ds            = $this->createMock('Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface');
        $fieldName     = 'o.testField';
        $parameterName = 'param1';
        $filterName    = 'testFilter';
        $inverse       = true;

        $this->childBuilder1->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($ds))
            ->will($this->returnValue(false));
        $this->childBuilder1->expects($this->never())
            ->method('buildComparisonExpr');

        $this->expectException('\RuntimeException');
        $this->expectExceptionMessage(sprintf('The "%s" datasource adapter is not supported.', get_class($ds)));

        $this->builder->buildComparisonExpr($ds, $fieldName, $parameterName, $filterName, $inverse);
    }

    public function testBuildNullValueExpr()
    {
        $ds            = $this->createMock('Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface');
        $fieldName     = 'o.testField';
        $filterName    = 'testFilter';
        $inverse       = true;

        $this->childBuilder1->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($ds))
            ->will($this->returnValue(true));
        $this->childBuilder1->expects($this->once())
            ->method('buildNullValueExpr')
            ->with($this->identicalTo($ds), $fieldName, $filterName, $inverse);

        $this->builder->buildNullValueExpr($ds, $fieldName, $filterName, $inverse);
    }

    public function testBuildNullValueExprNoAppropriateChildBuilder()
    {
        $ds            = $this->createMock('Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface');
        $fieldName     = 'o.testField';
        $filterName    = 'testFilter';
        $inverse       = true;

        $this->childBuilder1->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($ds))
            ->will($this->returnValue(false));
        $this->childBuilder1->expects($this->never())
            ->method('buildNullValueExpr');

        $this->expectException('\RuntimeException');
        $this->expectExceptionMessage(sprintf('The "%s" datasource adapter is not supported.', get_class($ds)));

        $this->builder->buildNullValueExpr($ds, $fieldName, $filterName, $inverse);
    }
}
