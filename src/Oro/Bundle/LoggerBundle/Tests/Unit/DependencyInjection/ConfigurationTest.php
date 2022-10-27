<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /** @var Configuration */
    private $configuration;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
    }

    public function testGetConfigTreeBuilder()
    {
        $this->assertInstanceOf(TreeBuilder::class, $this->configuration->getConfigTreeBuilder());
    }

    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $config, array $expected)
    {
        $processor = new Processor();

        $this->assertEquals($expected, $processor->processConfiguration($this->configuration, $config));
    }

    public function processConfigurationDataProvider(): array
    {
        return [
            [
                'config'  => [],
                'expected' => [
                    'settings' => [
                        'resolved' => true,
                        'detailed_logs_level' => ['value' => 'error', 'scope' => 'app'],
                        'detailed_logs_end_timestamp' => ['value' => null, 'scope' => 'app'],
                        'email_notification_recipients' => ['value' => '', 'scope' => 'app'],
                        'email_notification_subject' => ['value' => 'An Error Occurred!', 'scope' => 'app']
                    ]
                ]
            ]
        ];
    }
}
