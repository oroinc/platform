<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Layout\Extension\DatagridConfigContextConfigurator;
use Oro\Component\Layout\LayoutContext;

class DatagridConfigContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridConfigContextConfigurator */
    protected $contextConfigurator;

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
    protected $dataGridManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->dataGridManager = $this->getMockBuilder(Manager::class)->disableOriginalConstructor()->getMock();
        $this->contextConfigurator = new DatagridConfigContextConfigurator($this->dataGridManager);
    }

    public function testConfigureContext()
    {
        $context = new LayoutContext();
        $context['grid_config'] = ['grid_name'];

        /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject $config */
        $config = $this->getMockBuilder(DatagridConfiguration::class)->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('toArray')
            ->willReturn(['config']);

        $this->dataGridManager
            ->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with('grid_name')
            ->willReturn($config);

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertTrue($context->has('grid_config'));
        $this->assertEquals(['grid_name' => ['config']], $context->get('grid_config'));
    }

    public function testConfigureContextEmptyGridConfig()
    {
        $context = new LayoutContext();
        $context['grid_config'] = [];
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->dataGridManager
            ->expects($this->never())
            ->method('getConfigurationForGrid');

        $this->assertEquals([], $context->get('grid_config'));
    }

    public function testConfigureContextNoGridConfig()
    {
        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context->has('grid_config'));
    }

    public function testConfigureContextIfGridConfigNotArray()
    {
        $context = new LayoutContext();
        $context['grid_config'] = 123;
        $this->contextConfigurator->configureContext($context);

        $this->expectException(\LogicException::class);
        $expectedMessage = 'Failed to resolve the context variables. Reason: The option "grid_config" with value 123';
        $this->expectExceptionMessage($expectedMessage);

        $context->resolve();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "grid_config" value must be a string, but "array" given.
     */
    public function testConfigureContextIfInvalidDataArray()
    {
        $context = new LayoutContext();
        $context['grid_config'] = [['grid_name']];
        $this->contextConfigurator->configureContext($context);
    }
}
