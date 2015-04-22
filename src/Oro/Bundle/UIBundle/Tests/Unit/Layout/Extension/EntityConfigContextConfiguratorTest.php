<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Extension\EntityConfigContextConfigurator;

class EntityConfigContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var EntityConfigContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextConfigurator = new EntityConfigContextConfigurator(
            $this->configManager,
            ['alias1' => ['scope1', 'code1']]
        );
        $this->contextConfigurator->addConfigVariable('alias2', ['scope1', 'code2']);
        $this->contextConfigurator->addConfigVariables(['alias3' => ['scope3', 'code1']]);
    }

    public function testConfigureContext()
    {
        $entityClass = 'Test\Entity';
        $context     = new LayoutContext();

        $context['entity_class'] = $entityClass;

        $configProvider1 = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $config1         = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['scope1', $configProvider1],
                        ['scope3', null]
                    ]
                )
            );
        $configProvider1->expects($this->at(0))
            ->method('hasConfig')
            ->with($entityClass)
            ->will($this->returnValue(true));
        $configProvider1->expects($this->at(2))
            ->method('hasConfig')
            ->with($entityClass)
            ->will($this->returnValue(false));
        $configProvider1->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->will($this->returnValue($config1));
        $config1->expects($this->once())
            ->method('get')
            ->with('code1')
            ->will($this->returnValue('val1'));

        $this->contextConfigurator->configureContext($context);
        $context->getResolver()->setDefaults(['entity_class' => null]);
        $context->resolve();

        $this->assertEquals('val1', $context['alias1']);
        $this->assertNull($context['alias2']);
        $this->assertNull($context['alias3']);
    }

    public function testConfigureContextOverride()
    {
        $entityClass = 'Test\Entity';
        $context     = new LayoutContext();

        $context['entity_class'] = $entityClass;
        $context['alias1']       = 'Updated';

        $configProvider1 = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider3 = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $config1         = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config3         = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['scope1', $configProvider1],
                        ['scope3', $configProvider3]
                    ]
                )
            );
        $configProvider1->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->will($this->returnValue(true));
        $configProvider1->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->will($this->returnValue($config1));
        $configProvider3->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->will($this->returnValue(true));
        $configProvider3->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->will($this->returnValue($config3));
        $config1->expects($this->once())
            ->method('get')
            ->with('code2')
            ->will($this->returnValue('val2'));
        $config3->expects($this->once())
            ->method('get')
            ->with('code1')
            ->will($this->returnValue('val3'));

        $this->contextConfigurator->configureContext($context);
        $context->getResolver()->setDefaults(['entity_class' => null]);
        $context->resolve();

        $this->assertEquals('Updated', $context['alias1']);
        $this->assertEquals('val2', $context['alias2']);
        $this->assertEquals('val3', $context['alias3']);
    }

    public function testConfigureContextWithoutEntityClass()
    {
        $context = new LayoutContext();

        $this->configManager->expects($this->never())
            ->method('getProvider');

        $this->contextConfigurator->configureContext($context);
        $context->getResolver()->setDefaults(['entity_class' => null]);
        $context->resolve();

        $this->assertNull($context['alias1']);
        $this->assertNull($context['alias2']);
        $this->assertNull($context['alias3']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The alias "alias3" cannot be used for variable "scope.code" because it is already associated with variable "scope3.code1".
     */
    // @codingStandardsIgnoreEnd
    public function testAddConfigVariableDuplicate()
    {
        $this->contextConfigurator->addConfigVariable('alias3', ['scope', 'code']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The alias "alias3" cannot be used for variable "scope.code" because it is already associated with variable "scope3.code1".
     */
    // @codingStandardsIgnoreEnd
    public function testAddConfigVariablesDuplicate()
    {
        $this->contextConfigurator->addConfigVariables(['alias3' => ['scope', 'code']]);
    }
}
