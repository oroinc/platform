<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerListConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerConfiguration;

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
        $this->assertSame($expected, $this->configuration->processConfiguration($input));
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
                            'event' => ProcessTrigger::EVENT_UPDATE,
                            'field' => 'status',
                            'time_shift' => 12345
                        ),
                        array(
                            'event' => ProcessTrigger::EVENT_DELETE,
                            'time_shift' => 'P1D'
                        ),
                    ),
                ),
                'expected' => array(
                    'first_definition' => array(
                        array(
                            'event' => ProcessTrigger::EVENT_CREATE,
                            'field' => null,
                            'time_shift' => null,
                        ),
                    ),
                    'second_definition' => array(
                        array(
                            'event' => ProcessTrigger::EVENT_UPDATE,
                            'field' => 'status',
                            'time_shift' => 12345
                        ),
                        array(
                            'event' => ProcessTrigger::EVENT_DELETE,
                            'time_shift' => 24 * 3600,
                            'field' => null,
                        ),
                    ),
                ),
            )
        );
    }
}
