<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Oro\Bundle\SecurityBundle\Configuration\PermissionConfiguration;

class PermissionConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PermissionConfiguration
     */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new PermissionConfiguration();
    }

    protected function tearDown()
    {
        unset($this->configuration);
    }

    /**
     * @param array $input
     * @param array $expected
     * @dataProvider processDataProvider
     */
    public function testProcess(array $input, array $expected)
    {
        $this->assertEquals($expected, $this->configuration->processConfiguration($input));
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            'minimum data' => [
                'configuration' => [
                    'permission1' => [
                        'label' => 'My Label',
                    ],
                ],
                'expected' => [
                    'label' => 'My Label',
                    'apply_to_all' => true,
                    'group_names' => ['default'],
                    'exclude_entities' => [],
                    'apply_to_entities' => [],
                ],
            ],
            'maximum data' => [
                'configuration' => [
                    'permission1' => [
                        'label' => 'My Label',
                        'apply_to_all' => false,
                        'group_names' => ['frontend'],
                        'exclude_entities' => ['Entity1'],
                        'apply_to_entities' => ['Entity2'],
                        'description' => 'Test description',
                    ]
                ],
                'expected' => [
                    'label' => 'My Label',
                    'apply_to_all' => false,
                    'group_names' => ['frontend'],
                    'exclude_entities' => ['Entity1'],
                    'apply_to_entities' => ['Entity2'],
                    'description' => 'Test description',
                ],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "label" at path "permission" must be configured.
     */
    public function testProcessNoLabel()
    {
        $this->configuration->processConfiguration(['permission1' => []]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The path "permission.label" cannot contain an empty value, but got "".
     */
    public function testProcessEmptyLabel()
    {
        $this->configuration->processConfiguration(['permission1' => ['label' => '']]);
    }
}
