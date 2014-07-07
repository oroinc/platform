<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\TrackingBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder       = $configuration->getConfigTreeBuilder();

        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);
    }

    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration($configs, $expected)
    {
        $configuration = new Configuration();
        $processor     = new Processor();
        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    public function processConfigurationDataProvider()
    {
        return [
            'empty' => [
                'configs'  => [[]],
                'expected' => [
                    'settings' => [
                        'resolved'                 => 1,
                        'dynamic_tracking_enabled' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'log_rotate_interval'      => [
                            'value' => 60,
                            'scope' => 'app'
                        ],
                        'piwik_host'               => [
                            'value' => null,
                            'scope' => 'app'
                        ],
                        'piwik_token_auth'         => [
                            'value' => null,
                            'scope' => 'app'
                        ]
                    ]
                ]
            ]
        ];
    }
}
