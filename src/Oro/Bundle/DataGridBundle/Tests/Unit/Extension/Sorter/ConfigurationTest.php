<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $configs, array $expected): void
    {
        $this->assertEquals($expected, (new Processor())->processConfiguration(new Configuration(), $configs));
    }

    public function processConfigurationDataProvider(): array
    {
        return [
            'empty'                => [
                'configs'  => [[]],
                'expected' => [
                    'columns' => [],
                    'default' => [],
                    'disable_not_selected_option' => false
                ],
            ],
            'with all options set' => [
                'configs'  => [[
                    'columns' => [],
                    'multiple_sorting' => true,
                    'default' => [],
                    'toolbar_sorting' => false,
                    'disable_default_sorting' => true,
                    'disable_not_selected_option' => true
                ]],
                'expected' => [
                    'columns' => [],
                    'multiple_sorting' => true,
                    'default' => [],
                    'toolbar_sorting' => false,
                    'disable_default_sorting' => true,
                    'disable_not_selected_option' => true
                ]
            ]
        ];
    }
}
