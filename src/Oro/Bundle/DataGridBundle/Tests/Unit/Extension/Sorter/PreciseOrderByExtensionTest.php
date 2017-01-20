<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Sorter\PreciseOrderByExtension;

class PreciseOrderByExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var PreciseOrderByExtension */
    protected $extension;

    public function setUp()
    {
        $queryHintResolver = $this->getMockBuilder(QueryHintResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryHintResolver->expects(self::any())
            ->method('resolveHintName')
            ->with(PreciseOrderByExtension::HINT_PRECISE_ORDER_BY)
            ->willReturn('oro_entity.precise_order_by');

        $this->extension = new PreciseOrderByExtension($queryHintResolver);
    }

    public function testGetPriority()
    {
        self::assertSame(-261, $this->extension->getPriority());
    }

    public function testIsApplicableForNotOrmDatasource()
    {
        $config = DatagridConfiguration::create(['source' => ['type' => 'other']]);
        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableForOrmDatasource()
    {
        $config = DatagridConfiguration::create(['source' => ['type' => 'orm']]);
        self::assertTrue($this->extension->isApplicable($config));
    }

    public function testProcessConfigsWhenNoPreciseOrderByHint()
    {
        $config = DatagridConfiguration::create([]);
        $this->extension->processConfigs($config);
        self::assertEquals(
            ['HINT_PRECISE_ORDER_BY'],
            $config->offsetGetByPath('[source][hints]')
        );
    }

    public function testProcessConfigsWhenPreciseOrderByHintAlreadyExists()
    {
        $config = DatagridConfiguration::create(['source' => ['hints' => ['HINT_PRECISE_ORDER_BY']]]);
        $this->extension->processConfigs($config);
        self::assertEquals(
            ['HINT_PRECISE_ORDER_BY'],
            $config->offsetGetByPath('[source][hints]')
        );
    }

    public function testProcessConfigsWhenPreciseOrderByHintAlreadyExistsAndWasAddedByItsName()
    {
        $config = DatagridConfiguration::create(['source' => ['hints' => ['oro_entity.precise_order_by']]]);
        $this->extension->processConfigs($config);
        self::assertEquals(
            ['oro_entity.precise_order_by'],
            $config->offsetGetByPath('[source][hints]')
        );
    }

    public function testProcessConfigsWhenPreciseOrderByHintAlreadyExistsFullDeclaration()
    {
        $config = DatagridConfiguration::create(
            ['source' => ['hints' => [['name' => 'HINT_PRECISE_ORDER_BY', 'value' => true]]]]
        );
        $this->extension->processConfigs($config);
        self::assertEquals(
            [['name' => 'HINT_PRECISE_ORDER_BY', 'value' => true]],
            $config->offsetGetByPath('[source][hints]')
        );
    }

    public function testProcessConfigsWhenPreciseOrderByHintAlreadyExistsFullDeclarationAndWasAddedByItsName()
    {
        $config = DatagridConfiguration::create(
            ['source' => ['hints' => [['name' => 'oro_entity.precise_order_by', 'value' => true]]]]
        );
        $this->extension->processConfigs($config);
        self::assertEquals(
            [['name' => 'oro_entity.precise_order_by', 'value' => true]],
            $config->offsetGetByPath('[source][hints]')
        );
    }

    public function testProcessConfigsWhenPreciseOrderByHintDisabled()
    {
        $config = DatagridConfiguration::create(
            ['source' => ['hints' => [['name' => 'HINT_PRECISE_ORDER_BY', 'value' => false]]]]
        );
        $this->extension->processConfigs($config);
        self::assertEquals(
            [],
            $config->offsetGetByPath('[source][hints]')
        );
    }

    public function testProcessConfigsWhenPreciseOrderByHintDisabledByItsName()
    {
        $config = DatagridConfiguration::create(
            ['source' => ['hints' => [['name' => 'oro_entity.precise_order_by', 'value' => false]]]]
        );
        $this->extension->processConfigs($config);
        self::assertEquals(
            [],
            $config->offsetGetByPath('[source][hints]')
        );
    }
}
