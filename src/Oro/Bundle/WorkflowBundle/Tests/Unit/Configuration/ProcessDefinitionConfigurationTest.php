<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Symfony\Component\Yaml\Yaml;

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
                    'label' => 'My Label',
                    'entity' => 'My\Entity',
                ),
                'expected' => array(
                    'label' => 'My Label',
                    'entity' => 'My\Entity',
                    'enabled' => true,
                    'order' => 0,
                    'execution_required' => false,
                    'actions_configuration' => array(),
                ),
            ),
            'maximum data' => array(
                'input' => array(
                    'name' => 'my_definition',
                    'label' => 'My Label',
                    'enabled' => false,
                    'entity' => 'My\Entity',
                    'order' => 10,
                    'execution_required' => true,
                    'actions_configuration' => array('key' => 'value'),
                ),
                'expected' => array(
                    'name' => 'my_definition',
                    'label' => 'My Label',
                    'enabled' => false,
                    'entity' => 'My\Entity',
                    'order' => 10,
                    'execution_required' => true,
                    'actions_configuration' => array('key' => 'value'),
                ),
            ),
        );
    }
}
