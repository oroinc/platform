<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerConfiguration;

class ProcessTriggerConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessTriggerConfiguration
     */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new ProcessTriggerConfiguration();
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
            'minimum data' => array(
                'input' => array(
                    'event' => ProcessTrigger::EVENT_CREATE,
                ),
                'expected' => array(
                    'event' => ProcessTrigger::EVENT_CREATE,
                    'field' => null,
                    'time_shift' => null,
                ),
            ),
            'integer time shift' => array(
                'input' => array(
                    'event' => ProcessTrigger::EVENT_UPDATE,
                    'field' => 'status',
                    'time_shift' => 12345
                ),
                'expected' => array(
                    'event' => ProcessTrigger::EVENT_UPDATE,
                    'field' => 'status',
                    'time_shift' => 12345
                ),
            ),
            'date interval time shift' => array(
                'input' => array(
                    'event' => ProcessTrigger::EVENT_DELETE,
                    'time_shift' => 'P1D'
                ),
                'expected' => array(
                    'event' => ProcessTrigger::EVENT_DELETE,
                    'time_shift' => 24 * 3600,
                    'field' => null,
                ),
            ),
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "configuration.time_shift": Time shift "qwerty" is not compatible with DateInterval
     */
    //@codingStandardsIgnoreEnd
    public function testProcessInvalidTimeShift()
    {
        $this->configuration->processConfiguration(
            array(
                'event' => ProcessTrigger::EVENT_CREATE,
                'time_shift' => 'qwerty',
            )
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "configuration": Field is only allowed for update event
     */
    public function testProcessInvalidField()
    {
        $this->configuration->processConfiguration(
            array(
                'event' => ProcessTrigger::EVENT_CREATE,
                'field' => 'status',
            )
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The value "not_existing_event" is not allowed for path "configuration.event". Permissible values: "create", "update", "delete"
     */
    //@codingStandardsIgnoreEnd
    public function testProcessInvalidEvent()
    {
        $this->configuration->processConfiguration(
            array(
                'event' => 'not_existing_event',
            )
        );
    }
}
