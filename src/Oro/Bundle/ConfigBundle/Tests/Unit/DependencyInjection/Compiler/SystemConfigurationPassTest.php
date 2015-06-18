<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationPass;
use Oro\Bundle\ConfigBundle\Tests\Unit\Fixtures\TestBundle;

use Oro\Component\Config\CumulativeResourceManager;

class SystemConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var SystemConfigurationPass */
    protected $compiler;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    protected function setUp()
    {
        $this->compiler  = new SystemConfigurationPass();
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()->getMock();
    }

    protected function tearDown()
    {
        unset($this->compiler, $this->container);
    }

    public function testProcess()
    {
        $bundle  = new TestBundle();
        $bundles = [$bundle->getName() => get_class($bundle)];
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles);
        $this->container->expects($this->once())
            ->method('getExtensions')
            ->will($this->returnValue(['test_bundle' => null]));
        $this->container->expects($this->once())
            ->method('getExtensionConfig')
            ->with('test_bundle')
            ->will(
                $this->returnValue(
                    [
                        [
                            'settings' => [
                                SettingsBuilder::RESOLVED_KEY => true,
                                'some_field'                  => [
                                    'value' => 'some_val',
                                    'scope' => 'app'
                                ],
                                'some_another_field'          => [
                                    'value' => 'some_another_val'
                                ]
                            ]
                        ]
                    ]
                )
            );
        $bagServiceDef       = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $configBagServiceDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->will(
                $this->returnValue(
                    [
                        'first_scope_service'  => [
                            ['scope' => 'app', 'priority' => 100]
                        ],
                        'second_scope_service' => [
                            ['scope' => 'user', 'priority' => -100]
                        ]
                    ]
                )
            );
        $apiManagerServiceDef    = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $configManagerServiceDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->exactly(4))
            ->method('getDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [SystemConfigurationPass::CONFIG_DEFINITION_BAG_SERVICE, $bagServiceDef],
                        [SystemConfigurationPass::CONFIG_BAG_SERVICE, $configBagServiceDef],
                        [SystemConfigurationPass::API_MANAGER_SERVICE_ID, $apiManagerServiceDef],
                        [SystemConfigurationPass::MAIN_MANAGER_SERVICE_ID, $configManagerServiceDef],
                    ]
                )
            );
        $apiManagerServiceDef->expects($this->exactly(2))
            ->method('addMethodCall');

        $bagServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with($this->equalTo(0), $this->isType('array'));
        $configBagServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with($this->equalTo(0), $this->isType('array'));
        $this->compiler->process($this->container);
    }
}
