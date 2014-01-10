<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Symfony\Component\Yaml\Yaml;

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
                    'minimum_definition' => array(
                        'label' => 'My Label',
                        'entity' => 'My\Entity',
                    ),
                    'maximum_definition' => array(
                        'label' => 'My Label',
                        'enabled' => false,
                        'entity' => 'My\Entity',
                        'order' => 10,
                        'execution_required' => true,
                        'actions_configuration' => array('key' => 'value'),
                    ),
                ),
                'expected' => array(
                    'minimum_definition' => array(
                        'label' => 'My Label',
                        'entity' => 'My\Entity',
                        'enabled' => true,
                        'order' => 0,
                        'execution_required' => false,
                        'actions_configuration' => array(),
                    ),
                    'maximum_definition' => array(
                        'label' => 'My Label',
                        'enabled' => false,
                        'entity' => 'My\Entity',
                        'order' => 10,
                        'execution_required' => true,
                        'actions_configuration' => array('key' => 'value'),
                    )
                ),
            )
        );
    }
}
