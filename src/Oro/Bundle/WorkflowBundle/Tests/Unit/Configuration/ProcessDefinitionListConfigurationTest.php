<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionListConfiguration;

class ProcessDefinitionListConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessDefinitionListConfiguration
     */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new ProcessDefinitionListConfiguration(new ProcessDefinitionConfiguration());
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
                    'minimum_definition' => array(
                        'label' => 'My Label',
                        'entity' => 'My\Entity',
                    ),
                    'maximum_definition' => array(
                        'label' => 'My Label',
                        'enabled' => false,
                        'entity' => 'My\Entity',
                        'order' => 10,
                        'exclude_definitions'   => array('minimum_definition'),
                        'actions_configuration' => array('key' => 'value'),
                        'pre_conditions' => array('test' => array())
                    ),
                ),
                'expected' => array(
                    'minimum_definition' => array(
                        'label' => 'My Label',
                        'entity' => 'My\Entity',
                        'enabled' => true,
                        'order' => 0,
                        'exclude_definitions'   => array(),
                        'actions_configuration' => array(),
                        'pre_conditions' => array()
                    ),
                    'maximum_definition' => array(
                        'label' => 'My Label',
                        'enabled' => false,
                        'entity' => 'My\Entity',
                        'order' => 10,
                        'exclude_definitions'   => array('minimum_definition'),
                        'actions_configuration' => array('key' => 'value'),
                        'pre_conditions' => array('test' => array())
                    )
                ),
            )
        );
    }
}
