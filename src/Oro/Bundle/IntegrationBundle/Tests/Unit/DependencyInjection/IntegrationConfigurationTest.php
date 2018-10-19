<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\SettingsPass;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\Bundles\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;

class IntegrationConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $expected = [
            'form' => [
                'synchronization_settings' => [
                    'schedule'     => [
                        'type'       => 'schedule_form_type',
                        'applicable' => ['@test.client->test()'],
                        'options'    => []
                    ],
                    'enabled'      => [
                        'type'       => 'choice',
                        'options'    => ['choices' => ['Enabled' => 0, 'Disabled' => 1]],
                        'priority'   => -200,
                        'applicable' => [],
                    ],
                    'some_setting' => [
                        'type'       => 'choice',
                        'applicable' => ['simple'],
                        'options'    => [],
                    ],
                ]
            ]
        ];

        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)]);

        $settingsProviderDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $result              = null;

        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(SettingsPass::SETTINGS_PROVIDER_ID)
            ->will($this->returnValue($settingsProviderDef));
        $settingsProviderDef->expects($this->once())
            ->method('replaceArgument')
            ->will(
                $this->returnCallback(
                    function ($index, $argument) use (&$result) {
                        $result = $argument;
                    }
                )
            );

        $compiler = new SettingsPass();
        $compiler->process($container);
        $this->assertEquals($expected, $result);
    }
}
