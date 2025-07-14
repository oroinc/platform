<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverterContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;

class GroupingOrmQueryConverterContextTest extends TestCase
{
    private GroupingOrmQueryConverterContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new GroupingOrmQueryConverterContext();
    }

    public function testReset(): void
    {
        $initialContext = clone $this->context;

        $this->context->beginFilterGroup();
        $this->context->addFilter(['name' => 'filter1']);

        $this->context->reset();
        self::assertEquals($initialContext, $this->context);
    }

    public function testGetFiltersWhenNoFilters(): void
    {
        self::assertSame([], $this->context->getFilters());
    }

    public function testAddFilter(): void
    {
        $this->context->beginFilterGroup();
        $this->context->addFilter(['name' => 'filter1']);
        $this->context->endFilterGroup();

        self::assertSame([['name' => 'filter1']], $this->context->getFilters());
    }

    public function testAddFilterOperator(): void
    {
        $this->context->beginFilterGroup();
        $this->context->addFilter(['name' => 'filter1']);
        $this->context->addFilterOperator('AND');
        $this->context->addFilter(['name' => 'filter2']);
        $this->context->endFilterGroup();

        self::assertSame(
            [['name' => 'filter1'], 'AND', ['name' => 'filter2']],
            $this->context->getFilters()
        );
    }

    public function testFilterGroups(): void
    {
        $this->context->beginFilterGroup();
        $this->context->addFilter(['name' => 'filter1']);
        $this->context->beginFilterGroup();
        $this->context->addFilter(['name' => 'filter2']);
        $this->context->endFilterGroup();
        $this->context->endFilterGroup();

        self::assertSame(
            [
                ['name' => 'filter1'],
                [
                    ['name' => 'filter2']
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testAddFilterWhenNoFilterGroupOpen(): void
    {
        $this->expectException(InvalidPropertyPathException::class);

        $this->context->addFilter(['name' => 'filter1']);

        $this->context->getFilters();
    }

    public function testAddFilterWhenAllFilterGroupsClosed(): void
    {
        $this->expectException(InvalidPropertyPathException::class);

        $this->context->beginFilterGroup();
        $this->context->endFilterGroup();

        $this->context->addFilter(['name' => 'filter1']);

        $this->context->getFilters();
    }
}
