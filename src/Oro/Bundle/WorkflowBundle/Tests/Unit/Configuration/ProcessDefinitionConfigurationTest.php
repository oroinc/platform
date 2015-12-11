<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionConfiguration;

class ProcessDefinitionConfigurationTest extends \PHPUnit_Framework_TestCase
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
        return array(
            'minimum data' => array(
                'input' => array(
                    'label' => 'My Label',
                    'entity' => 'My\Entity',
                ),
                'expected' => array(
                    'label' => 'My Label',
                    'entity' => 'My\Entity',
                    'enabled' => true,
                    'order' => 0,
                    'exclude_definitions'   => array(),
                    'actions_configuration' => array(),
                    'pre_conditions' => array()
                ),
            ),
            'maximum data' => array(
                'input' => array(
                    'name' => 'my_definition',
                    'label' => 'My Label',
                    'enabled' => false,
                    'entity' => 'My\Entity',
                    'order' => 10,
                    'exclude_definitions'   => array(),
                    'actions_configuration' => array('key' => 'value'),
                    'pre_conditions' => array('test')
                ),
                'expected' => array(
                    'name' => 'my_definition',
                    'label' => 'My Label',
                    'enabled' => false,
                    'entity' => 'My\Entity',
                    'order' => 10,
                    'exclude_definitions'   => array(),
                    'actions_configuration' => array('key' => 'value'),
                    'pre_conditions' => array('test')
                ),
            ),
        );
    }
}
