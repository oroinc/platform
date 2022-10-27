<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionListConfiguration;

class ProcessDefinitionListConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessDefinitionListConfiguration */
    private $configuration;

    protected function setUp(): void
    {
        $this->configuration = new ProcessDefinitionListConfiguration(new ProcessDefinitionConfiguration());
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(array $input, array $expected)
    {
        $this->assertEquals($expected, $this->configuration->processConfiguration($input));
    }

    public function processDataProvider(): array
    {
        return [
            [
                'input' => [
                    'minimum_definition' => [
                        'label' => 'My Label',
                        'entity' => 'My\Entity',
                    ],
                    'maximum_definition' => [
                        'label' => 'My Label',
                        'enabled' => false,
                        'entity' => 'My\Entity',
                        'order' => 10,
                        'exclude_definitions'   => ['minimum_definition'],
                        'actions_configuration' => ['key' => 'value'],
                        'preconditions' => ['test' => []],
                    ],
                ],
                'expected' => [
                    'minimum_definition' => [
                        'label' => 'My Label',
                        'entity' => 'My\Entity',
                        'enabled' => true,
                        'order' => 0,
                        'exclude_definitions'   => [],
                        'actions_configuration' => [],
                        'preconditions' => []
                    ],
                    'maximum_definition' => [
                        'label' => 'My Label',
                        'enabled' => false,
                        'entity' => 'My\Entity',
                        'order' => 10,
                        'exclude_definitions'   => ['minimum_definition'],
                        'actions_configuration' => ['key' => 'value'],
                        'preconditions' => ['test' => []],
                    ]
                ],
            ]
        ];
    }
}
