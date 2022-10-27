<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverterContext;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;

class GroupingOrmQueryConverterContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var GroupingOrmQueryConverterContext */
    private $context;

    protected function setUp(): void
    {
        $this->context = new GroupingOrmQueryConverterContext();
    }

    public function testReset()
    {
        $initialContext = clone $this->context;

        $this->context->beginFilterGroup();
        $this->context->addFilter(['name' => 'filter1']);

        $this->context->reset();
        self::assertEquals($initialContext, $this->context);
    }

    public function testGetFiltersWhenNoFilters()
    {
        self::assertSame([], $this->context->getFilters());
    }

    public function testAddFilter()
    {
        $this->context->beginFilterGroup();
        $this->context->addFilter(['name' => 'filter1']);
        $this->context->endFilterGroup();

        self::assertSame([['name' => 'filter1']], $this->context->getFilters());
    }

    public function testAddFilterOperator()
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

    public function testFilterGroups()
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

    public function testAddFilterWhenNoFilterGroupOpen()
    {
        $this->expectException(InvalidPropertyPathException::class);

        $this->context->addFilter(['name' => 'filter1']);

        $this->context->getFilters();
    }

    public function testAddFilterWhenAllFilterGroupsClosed()
    {
        $this->expectException(InvalidPropertyPathException::class);

        $this->context->beginFilterGroup();
        $this->context->endFilterGroup();

        $this->context->addFilter(['name' => 'filter1']);

        $this->context->getFilters();
    }
}
