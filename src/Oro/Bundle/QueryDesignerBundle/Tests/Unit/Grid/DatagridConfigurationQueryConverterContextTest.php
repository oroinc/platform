<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationQueryConverterContext;

class DatagridConfigurationQueryConverterContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridConfigurationQueryConverterContext */
    private $context;

    protected function setUp(): void
    {
        $this->context = new DatagridConfigurationQueryConverterContext();
    }

    public function testReset()
    {
        $initialContext = clone $this->context;

        $this->context->setConfig($this->createMock(DatagridConfiguration::class));
        $this->context->addSelectColumn('column1');
        $this->context->addGroupingColumn('column1');
        $this->context->addFrom('Test\Entity', 'alias');
        $this->context->addInnerJoin('Test\Entity', 'alias');
        $this->context->addLeftJoin('Test\Entity', 'alias');

        $this->context->reset();
        self::assertEquals($initialContext, $this->context);
    }

    public function testConfig()
    {
        $config = $this->createMock(DatagridConfiguration::class);
        $this->context->setConfig($config);
        self::assertSame($config, $this->context->getConfig());
    }

    public function testGetConfigWhenItWasNotSet()
    {
        $this->expectException(\TypeError::class);
        $this->context->getConfig();
    }

    public function testSelectColumns()
    {
        self::assertSame([], $this->context->getSelectColumns());

        $this->context->addSelectColumn('column1');
        $this->context->addSelectColumn('column2');
        self::assertSame(['column1', 'column2'], $this->context->getSelectColumns());
    }

    public function testGroupingColumns()
    {
        self::assertSame([], $this->context->getGroupingColumns());

        $this->context->addGroupingColumn('column1');
        $this->context->addGroupingColumn('column2');
        self::assertSame(['column1', 'column2'], $this->context->getGroupingColumns());
    }

    public function testFrom()
    {
        self::assertSame([], $this->context->getFrom());

        $this->context->addFrom('Test\Entity1', 'alias1');
        $this->context->addFrom('Test\Entity2', 'alias2');
        self::assertSame(
            [
                ['table' => 'Test\Entity1', 'alias' => 'alias1'],
                ['table' => 'Test\Entity2', 'alias' => 'alias2']
            ],
            $this->context->getFrom()
        );
    }

    public function testInnerJoins()
    {
        self::assertSame([], $this->context->getInnerJoins());

        $this->context->addInnerJoin('Test\Entity1', 'a1');
        $this->context->addInnerJoin('Test\Entity2', 'a2', 'WITH', 'a2.id = 1');
        self::assertSame(
            [
                ['join' => 'Test\Entity1', 'alias' => 'a1'],
                ['join' => 'Test\Entity2', 'alias' => 'a2', 'conditionType' => 'WITH', 'condition' => 'a2.id = 1']
            ],
            $this->context->getInnerJoins()
        );
    }

    public function testLeftJoins()
    {
        self::assertSame([], $this->context->getLeftJoins());

        $this->context->addLeftJoin('Test\Entity1', 'a1');
        $this->context->addLeftJoin('Test\Entity2', 'a2', 'WITH', 'a2.id = 1');
        self::assertSame(
            [
                ['join' => 'Test\Entity1', 'alias' => 'a1'],
                ['join' => 'Test\Entity2', 'alias' => 'a2', 'conditionType' => 'WITH', 'condition' => 'a2.id = 1']
            ],
            $this->context->getLeftJoins()
        );
    }
}
