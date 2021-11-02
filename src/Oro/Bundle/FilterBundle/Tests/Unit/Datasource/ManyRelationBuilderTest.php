<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Datasource;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilder;
use Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilderInterface;

class ManyRelationBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManyRelationBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $childBuilder1;

    /** @var ManyRelationBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->childBuilder1 = $this->createMock(ManyRelationBuilderInterface::class);

        $this->builder = new ManyRelationBuilder();
        $this->builder->addBuilder($this->childBuilder1);
    }

    public function testBuildComparisonExpr()
    {
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $fieldName = 'o.testField';
        $parameterName = 'param1';
        $filterName = 'testFilter';
        $inverse = true;

        $this->childBuilder1->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($ds))
            ->willReturn(true);
        $this->childBuilder1->expects($this->once())
            ->method('buildComparisonExpr')
            ->with($this->identicalTo($ds), $fieldName, $parameterName, $filterName, $inverse);

        $this->builder->buildComparisonExpr($ds, $fieldName, $parameterName, $filterName, $inverse);
    }

    public function testBuildComparisonExprNoAppropriateChildBuilder()
    {
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $fieldName = 'o.testField';
        $parameterName = 'param1';
        $filterName = 'testFilter';
        $inverse = true;

        $this->childBuilder1->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($ds))
            ->willReturn(false);
        $this->childBuilder1->expects($this->never())
            ->method('buildComparisonExpr');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('The "%s" datasource adapter is not supported.', get_class($ds)));

        $this->builder->buildComparisonExpr($ds, $fieldName, $parameterName, $filterName, $inverse);
    }

    public function testBuildNullValueExpr()
    {
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $fieldName = 'o.testField';
        $filterName = 'testFilter';
        $inverse = true;

        $this->childBuilder1->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($ds))
            ->willReturn(true);
        $this->childBuilder1->expects($this->once())
            ->method('buildNullValueExpr')
            ->with($this->identicalTo($ds), $fieldName, $filterName, $inverse);

        $this->builder->buildNullValueExpr($ds, $fieldName, $filterName, $inverse);
    }

    public function testBuildNullValueExprNoAppropriateChildBuilder()
    {
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $fieldName = 'o.testField';
        $filterName = 'testFilter';
        $inverse = true;

        $this->childBuilder1->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($ds))
            ->willReturn(false);
        $this->childBuilder1->expects($this->never())
            ->method('buildNullValueExpr');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('The "%s" datasource adapter is not supported.', get_class($ds)));

        $this->builder->buildNullValueExpr($ds, $fieldName, $filterName, $inverse);
    }
}
