<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Component\Config\Resolver\SystemAwareResolver;
use Oro\Bundle\IntegrationBundle\Provider\SettingsProvider;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestService;

use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider configProvider
     *
     * @param array  $config
     * @param string $integrationType
     * @param array  $expectedFields
     * @param bool   $resolvedValue
     */
    public function testGetFormSettings($config, $integrationType, $expectedFields, $resolvedValue)
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $resolver  = new SystemAwareResolver();
        $resolver->setContainer($container);

        $service = new TestService($resolvedValue);
        $container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [['test.client', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $service]]
                )
            );

        $provider = new SettingsProvider($config, $resolver);
        $result   = $provider->getFormSettings('synchronization_settings', $integrationType);

        $this->assertEquals($expectedFields, array_keys($result));
    }

    /**
     * @return array
     */
    public function configProvider()
    {
        $regularConfig = [
            'form' => [
                'synchronization_settings' => [
                    'schedule'     => [
                        'type'       => 'schedule_form_type',
                        'applicable' => ['@test.client->testMethod()'],
                        'options'    => []
                    ],
                    'enabled'      => [
                        'type'       => 'choice',
                        'options'    => ['choices' => ['Enabled', 'Disabled']],
                        'priority'   => -200,
                        'applicable' => [],
                    ],
                    'some_setting' => [
                        'type'       => 'choice',
                        'applicable' => ['simple'],
                        'priority'   => 100,
                        'options'    => [],
                    ],
                ]
            ]
        ];

        return [
            'should return fields'      => [
                'config'          => $regularConfig,
                'given type'      => 'other',
                'fields expected' => ['enabled', 'schedule'],
                'resolved value'  => true
            ],
            'should use resolved value' => [
                'config'          => $regularConfig,
                'given type'      => 'simple',
                'fields expected' => ['enabled', 'some_setting'],
                'resolved value'  => false
            ]
        ];
    }
}
