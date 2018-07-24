<?php

namespace Oro\Bundle\LoggerBundle\Bundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /** @var Configuration */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new Configuration();
    }

    protected function tearDown()
    {
        unset($this->configuration);
    }

    public function testGetConfigTreeBuilder()
    {
        $this->assertInstanceOf(
            'Symfony\Component\Config\Definition\Builder\TreeBuilder',
            $this->configuration->getConfigTreeBuilder()
        );
    }

    /**
     * @dataProvider processConfigurationDataProvider
     *
     * @param array $config
     * @param array $expected
     */
    public function testProcessConfiguration(array $config, array $expected)
    {
        $processor = new Processor();

        $this->assertEquals($expected, $processor->processConfiguration($this->configuration, $config));
    }

    /**
     * @return array
     */
    public function processConfigurationDataProvider()
    {
        return [
            [
                'config'  => [],
                'expected' => [
                    'settings' => [
                        'resolved' => true,
                        'detailed_logs_level' => ['value' => 'notice', 'scope' => 'app'],
                        'detailed_logs_end_timestamp' => ['value' => null, 'scope' => 'app'],
                        'email_notification_recipients' => ['value' => '', 'scope' => 'app'],
                        'email_notification_subject' => ['value' => 'An Error Occurred!', 'scope' => 'app']
                    ]
                ]
            ]
        ];
    }
}
