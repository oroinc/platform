<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionConfiguration;

class ProcessDefinitionConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProcessDefinitionConfiguration
     */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new ProcessDefinitionConfiguration();
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
                'input' => [
                    'label' => 'My Label',
                    'entity' => 'My\Entity',
                ],
                'expected' => [
                    'label' => 'My Label',
                    'entity' => 'My\Entity',
                    'enabled' => true,
                    'order' => 0,
                    'exclude_definitions'   => [],
                    'actions_configuration' => [],
                    'preconditions' => []
                ],
            ],
            'maximum data' => [
                'input' => [
                    'name' => 'my_definition',
                    'label' => 'My Label',
                    'enabled' => false,
                    'entity' => 'My\Entity',
                    'order' => 10,
                    'exclude_definitions'   => [],
                    'actions_configuration' => ['key' => 'value'],
                    'preconditions' => ['test'],
                    'pre_conditions' => ['test2'],
                ],
                'expected' => [
                    'name' => 'my_definition',
                    'label' => 'My Label',
                    'enabled' => false,
                    'entity' => 'My\Entity',
                    'order' => 10,
                    'exclude_definitions'   => [],
                    'actions_configuration' => ['key' => 'value'],
                    'preconditions' => ['test', 'test2'],
                ],
            ],
        ];
    }
}
