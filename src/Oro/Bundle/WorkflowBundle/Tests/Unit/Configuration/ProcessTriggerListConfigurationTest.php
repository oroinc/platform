<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessPriority;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerListConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerListConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProcessTriggerListConfiguration
     */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new ProcessTriggerListConfiguration(new ProcessTriggerConfiguration());
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
            [
                'input' => [
                    'first_definition' => [
                        [
                            'event' => ProcessTrigger::EVENT_CREATE,
                        ],
                    ],
                    'second_definition' => [
                        [
                            'event'      => ProcessTrigger::EVENT_UPDATE,
                            'field'      => 'status',
                            'priority'   => ProcessPriority::PRIORITY_HIGH,
                            'queued'     => true,
                            'time_shift' => 12345
                        ],
                        [
                            'event'      => ProcessTrigger::EVENT_DELETE,
                            'queued'     => true,
                            'time_shift' => 'P1D'
                        ],
                        [
                            'cron'       => '1 2 3 4 5'
                        ],
                    ],
                ],
                'expected' => [
                    'first_definition' => [
                        [
                            'event'      => ProcessTrigger::EVENT_CREATE,
                            'field'      => null,
                            'priority'   => ProcessPriority::PRIORITY_DEFAULT,
                            'queued'     => false,
                            'time_shift' => null,
                            'cron'       => null
                        ],
                    ],
                    'second_definition' => [
                        [
                            'event'      => ProcessTrigger::EVENT_UPDATE,
                            'field'      => 'status',
                            'priority'   => ProcessPriority::PRIORITY_HIGH,
                            'queued'     => true,
                            'time_shift' => 12345,
                            'cron'       => null
                        ],
                        [
                            'event'      => ProcessTrigger::EVENT_DELETE,
                            'queued'     => true,
                            'time_shift' => 24 * 3600,
                            'field'      => null,
                            'priority'   => ProcessPriority::PRIORITY_DEFAULT,
                            'cron'       => null
                        ],
                        [
                            'event'      => null,
                            'field'      => null,
                            'priority'   => 0,
                            'queued'     => false,
                            'time_shift' => null,
                            'cron'       => '1 2 3 4 5'
                        ],
                    ],
                ],
            ]
        ];
    }
}
