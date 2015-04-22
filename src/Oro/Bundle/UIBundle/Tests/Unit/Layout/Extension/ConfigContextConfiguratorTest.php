<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Extension\ConfigContextConfigurator;

class ConfigContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var ConfigContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextConfigurator = new ConfigContextConfigurator(
            $this->configManager,
            ['alias1' => 'configKey1']
        );
        $this->contextConfigurator->addConfigVariable('alias2', 'configKey2');
        $this->contextConfigurator->addConfigVariables(['alias3' => 'configKey3']);
    }

    public function testConfigureContext()
    {
        $context = new LayoutContext();

        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['configKey1', false, false, 'val1'],
                        ['configKey2', false, false, 'val2'],
                        ['configKey3', false, false, 'val3']
                    ]
                )
            );

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals('val1', $context['alias1']);
        $this->assertEquals('val2', $context['alias2']);
        $this->assertEquals('val3', $context['alias3']);
    }

    public function testConfigureContextOverride()
    {
        $context = new LayoutContext();

        $context['alias1'] = 'Updated';

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['configKey2', false, false, 'val2'],
                        ['configKey3', false, false, 'val3']
                    ]
                )
            );

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals('Updated', $context['alias1']);
        $this->assertEquals('val2', $context['alias2']);
        $this->assertEquals('val3', $context['alias3']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The alias "alias3" cannot be used for variable "newConfigKey" because it is already associated with variable "configKey3".
     */
    // @codingStandardsIgnoreEnd
    public function testAddConfigVariableDuplicate()
    {
        $this->contextConfigurator->addConfigVariable('alias3', 'newConfigKey');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The alias "alias3" cannot be used for variable "newConfigKey" because it is already associated with variable "configKey3".
     */
    // @codingStandardsIgnoreEnd
    public function testAddConfigVariablesDuplicate()
    {
        $this->contextConfigurator->addConfigVariables(['alias3' => 'newConfigKey']);
    }
}
