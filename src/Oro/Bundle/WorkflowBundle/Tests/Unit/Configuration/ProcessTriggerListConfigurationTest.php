<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerListConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerListConfigurationTest extends \PHPUnit_Framework_TestCase
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
        return array(
            array(
                'input' => array(
                    'first_definition' => array(
                        array(
                            'event' => ProcessTrigger::EVENT_CREATE,
                        ),
                    ),
                    'second_definition' => array(
                        array(
                            'event'      => ProcessTrigger::EVENT_UPDATE,
                            'field'      => 'status',
                            'priority'   => Job::PRIORITY_HIGH,
                            'queued'     => true,
                            'time_shift' => 12345
                        ),
                        array(
                            'event'      => ProcessTrigger::EVENT_DELETE,
                            'queued'     => true,
                            'time_shift' => 'P1D'
                        ),
                        array(
                            'cron'       => '1 2 3 4 5'
                        ),
                    ),
                ),
                'expected' => array(
                    'first_definition' => array(
                        array(
                            'event'      => ProcessTrigger::EVENT_CREATE,
                            'field'      => null,
                            'priority'   => Job::PRIORITY_DEFAULT,
                            'queued'     => false,
                            'time_shift' => null,
                            'cron'       => null
                        ),
                    ),
                    'second_definition' => array(
                        array(
                            'event'      => ProcessTrigger::EVENT_UPDATE,
                            'field'      => 'status',
                            'priority'   => Job::PRIORITY_HIGH,
                            'queued'     => true,
                            'time_shift' => 12345,
                            'cron'       => null
                        ),
                        array(
                            'event'      => ProcessTrigger::EVENT_DELETE,
                            'queued'     => true,
                            'time_shift' => 24 * 3600,
                            'field'      => null,
                            'priority'   => Job::PRIORITY_DEFAULT,
                            'cron'       => null
                        ),
                        array(
                            'event'      => null,
                            'field'      => null,
                            'priority'   => 0,
                            'queued'     => false,
                            'time_shift' => null,
                            'cron'       => '1 2 3 4 5'
                        ),
                    ),
                ),
            )
        );
    }
}
