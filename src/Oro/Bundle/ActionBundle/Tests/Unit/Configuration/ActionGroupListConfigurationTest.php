<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Configuration\ActionGroupListConfiguration;

class ActionGroupListConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionGroupListConfiguration
     */
    protected $configuration;

    public function setUp()
    {
        $this->configuration = new ActionGroupListConfiguration();
    }

    /**
     * @param array $config
     * @param array $expected
     *
     * @dataProvider processValidConfigurationProvider
     */
    public function testProcessValidConfiguration(array $config, array $expected)
    {
        $this->assertEquals($expected, $this->configuration->processConfiguration($config));
    }

    /**
     * @return array
     */
    public function processValidConfigurationProvider()
    {
        return [
            'empty configuration' => [
                'config' => [],
                'expected' => []
            ],
            'min valid configuration' => [
                'config' => [

                ],
                'expected' => [

                ]
            ]
        ];
    }
}
