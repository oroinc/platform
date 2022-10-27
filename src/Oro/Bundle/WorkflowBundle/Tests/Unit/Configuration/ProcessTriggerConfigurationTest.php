<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessPriority;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ProcessTriggerConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessTriggerConfiguration */
    private $configuration;

    protected function setUp(): void
    {
        $this->configuration = new ProcessTriggerConfiguration();
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
            'minimum data' => [
                'input' => [
                    'event' => ProcessTrigger::EVENT_CREATE,
                ],
                'expected' => [
                    'event'      => ProcessTrigger::EVENT_CREATE,
                    'field'      => null,
                    'priority'   => ProcessPriority::PRIORITY_DEFAULT,
                    'queued'     => false,
                    'time_shift' => null,
                    'cron'       => null
                ]
            ],
            'integer time shift' => [
                'input' => [
                    'event'      => ProcessTrigger::EVENT_UPDATE,
                    'field'      => 'status',
                    'priority'   => ProcessPriority::PRIORITY_LOW,
                    'queued'     => true,
                    'time_shift' => 12345,
                    'cron'       => null
                ],
                'expected' => [
                    'event'      => ProcessTrigger::EVENT_UPDATE,
                    'field'      => 'status',
                    'priority'   => ProcessPriority::PRIORITY_LOW,
                    'queued'     => true,
                    'time_shift' => 12345,
                    'cron'       => null
                ]
            ],
            'date interval time shift' => [
                'input' => [
                    'event'      => ProcessTrigger::EVENT_DELETE,
                    'priority'   => ProcessPriority::PRIORITY_HIGH,
                    'queued'     => true,
                    'time_shift' => 'P1D',
                    'cron'       => null
                ],
                'expected' => [
                    'event'      => ProcessTrigger::EVENT_DELETE,
                    'priority'   => ProcessPriority::PRIORITY_HIGH,
                    'queued'     => true,
                    'time_shift' => 24 * 3600,
                    'field'      => null,
                    'cron'       => null
                ]
            ],
            'cron expression' => [
                'input' => [
                    'cron' => '0 * * * *'
                ],
                'expected' => [
                    'event'      => null,
                    'field'      => null,
                    'priority'   => ProcessPriority::PRIORITY_DEFAULT,
                    'queued'     => false,
                    'time_shift' => null,
                    'cron'       => '0 * * * *'
                ]
            ]
        ];
    }

    /**
     * @dataProvider processExceptionDataProvider
     *
     * @param array $config
     * @param string $exception
     * @param string $message
     */
    public function testProcessException(array $config, $exception, $message)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->configuration->processConfiguration($config);
    }

    public function processExceptionDataProvider(): array
    {
        return [
            'invalid time_shift' => [
                'config' => [
                    'event' => ProcessTrigger::EVENT_CREATE,
                    'time_shift' => 'qwerty'
                ],
                'exception' => InvalidConfigurationException::class,
                'message' => 'Invalid configuration for path "configuration.time_shift": Time shift "qwerty" is not ' .
                    'compatible with DateInterval'
            ],
            'field property for event create' => [
                'config' => [
                    'event' => ProcessTrigger::EVENT_CREATE,
                    'field' => 'status'
                ],
                'exception' => InvalidConfigurationException::class,
                'message' => 'Invalid configuration for path "configuration": Field is only allowed for update event'
            ],
            'not existing event' => [
                'config' => [
                    'event' => 'not_existing_event'
                ],
                'exception' => InvalidConfigurationException::class,
                'message' => 'The value "not_existing_event" is not allowed for path "configuration.event". ' .
                    'Permissible values: "create", "update", "delete"'
            ],
            'event and cron at the same time' => [
                'config' => [
                    'event' => 'update',
                    'cron' => '0 * * * *'
                ],
                'exception' => InvalidConfigurationException::class,
                'message' => 'Invalid configuration for path "configuration": Only one child node "event" or "cron" ' .
                    'must be configured'
            ],
            'invalid cron expression' => [
                'config' => [
                    'cron' => 'a b * * *'
                ],
                'exception' => InvalidConfigurationException::class,
                'message' => 'Invalid configuration for path "configuration.cron": Invalid CRON field value a at ' .
                    'position 0'
            ],
            'not allowed property "field" with cron node' => [
                'config' => [
                    'cron' => '0 * * * *',
                    'field' => 'test'
                ],
                'exception' => InvalidConfigurationException::class,
                'message' => 'Invalid configuration for path "configuration": Nodes "field", "queued" and ' .
                    '"time_shift" are only allowed with event node'
            ],
            'not allowed property "queued" with cron node' => [
                'config' => [
                    'cron' => '0 * * * *',
                    'queued' => true
                ],
                'exception' => InvalidConfigurationException::class,
                'message' => 'Invalid configuration for path "configuration": Nodes "field", "queued" and ' .
                    '"time_shift" are only allowed with event node'
            ],
            'not allowed property "time_shift" with cron node' => [
                'config' => [
                    'cron' => '0 * * * *',
                    'time_shift' => 10
                ],
                'exception' => InvalidConfigurationException::class,
                'message' => 'Invalid configuration for path "configuration": Nodes "field", "queued" and ' .
                    '"time_shift" are only allowed with event node'
            ],
        ];
    }
}
