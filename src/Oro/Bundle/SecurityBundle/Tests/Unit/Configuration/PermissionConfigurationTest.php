<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Oro\Bundle\SecurityBundle\Configuration\PermissionConfiguration;
use Symfony\Component\Config\Definition\Processor;

class PermissionConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private function processConfiguration(array $configs): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(new PermissionConfiguration(), $configs);
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(array $input, array $expected): void
    {
        $this->assertEquals($expected, $this->processConfiguration([$input]));
    }

    public function processDataProvider(): array
    {
        return [
            'permissions list 1' => [
                'input' => [
                    'permission1' => [],
                    'permission2' => [
                        'label' => 'My Label',
                        'group_names' => ['default', 'frontend', '', ''],
                    ],
                    'permission3' => [
                        'label' => 'Test Label',
                        'apply_to_all' => false,
                        'group_names' => 'frontend',
                        'exclude_entities' => ['Entity1'],
                        'apply_to_entities' => ['Entity2'],
                        'apply_to_interfaces' => ['Interface1'],
                        'description' => 'Test Description',
                    ],
                ],
                'expected' => [
                    'permission1' => [
                        'label' => 'permission1',
                        'apply_to_all' => true,
                        'group_names' => ['default'],
                        'exclude_entities' => [],
                        'apply_to_entities' => [],
                        'apply_to_interfaces' => [],
                    ],
                    'permission2' => [
                        'label' => 'My Label',
                        'apply_to_all' => true,
                        'group_names' => ['default', 'frontend'],
                        'exclude_entities' => [],
                        'apply_to_entities' => [],
                        'apply_to_interfaces' => [],
                    ],
                    'permission3' => [
                        'label' => 'Test Label',
                        'apply_to_all' => false,
                        'group_names' => ['frontend'],
                        'exclude_entities' => ['Entity1'],
                        'apply_to_entities' => ['Entity2'],
                        'description' => 'Test Description',
                        'apply_to_interfaces' => ['Interface1'],
                    ],
                ],
            ]
        ];
    }
}
