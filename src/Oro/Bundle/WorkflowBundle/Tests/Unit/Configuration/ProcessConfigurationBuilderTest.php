<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessConfigurationBuilderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_DEFINITION_NAME = 'test_definition';

    /**
     * @var ProcessConfigurationBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new ProcessConfigurationBuilder();
    }

    protected function tearDown()
    {
        unset($this->builder);
    }

    /**
     * @param array $expected
     * @param ProcessDefinition $definition
     */
    protected function assertDefinitionConfiguration(array $expected, ProcessDefinition $definition)
    {
        $this->assertEquals($expected['label'], $definition->getLabel());
        $this->assertEquals($expected['entity'], $definition->getRelatedEntity());
        $this->assertEquals($expected['enabled'], $definition->isEnabled());
        $this->assertEquals($expected['order'], $definition->getExecutionOrder());
        $this->assertEquals($expected['execution_required'], $definition->isExecutionRequired());
        $this->assertEquals($expected['actions_configuration'], $definition->getActionsConfiguration());
    }

    /**
     * @param array $expected
     * @param ProcessDefinition $definition
     * @param ProcessTrigger $trigger
     */
    protected function assertProcessTrigger(
        array $expected,
        ProcessDefinition $definition,
        ProcessTrigger $trigger
    ) {
        $this->assertEquals($expected['event'], $trigger->getEvent());
        $this->assertEquals($expected['field'], $trigger->getField());
        $this->assertSame($expected['time_shift'], $trigger->getTimeShift());
        $this->assertSame($definition, $trigger->getDefinition());
    }

    /**
     * @param string $name
     * @param array $configuration
     * @param array $expected
     * @dataProvider buildProcessDefinitionDataProvider
     */
    public function testBuildProcessDefinition($name, array $configuration, array $expected)
    {
        $definition = $this->builder->buildProcessDefinition($name, $configuration);

        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition', $definition);
        $this->assertEquals($name, $definition->getName());
        $this->assertDefinitionConfiguration($expected, $definition);
    }

    /**
     * @return array
     */
    public function buildProcessDefinitionDataProvider()
    {
        return array(
            'minimum data' => array(
                'name' => 'minimum_name',
                'configuration' => array(
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
                'name' => 'maximum_name',
                'configuration' => array(
                    'label' => 'My Label',
                    'enabled' => false,
                    'entity' => 'My\Entity',
                    'order' => 10,
                    'execution_required' => true,
                    'actions_configuration' => array('key' => 'value'),
                ),
                'expected' => array(
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

    /**
     * @param array $configuration
     * @param array $expected
     * @dataProvider buildProcessDefinitionsDataProvider
     */
    public function testBuildProcessDefinitions(array $configuration, array $expected)
    {
        $definitions = $this->builder->buildProcessDefinitions($configuration);

        $this->assertSameSize($expected, $definitions);
        foreach ($definitions as $definition) {
            $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition', $definition);
            $this->assertArrayHasKey($definition->getName(), $expected);
            $this->assertDefinitionConfiguration($expected[$definition->getName()], $definition);
        }
    }

    /**
     * @return array
     */
    public function buildProcessDefinitionsDataProvider()
    {
        $basicDataProvider = $this->buildProcessDefinitionDataProvider();

        $configuration = array();
        $expected = array();
        foreach ($basicDataProvider as $dataSet) {
            $definitionName = $dataSet['name'];
            $configuration[$definitionName] = $dataSet['configuration'];
            $expected[$definitionName] = $dataSet['expected'];
        }

        return array(
            array(
                'configuration' => $configuration,
                'expected' => $expected,
            )
        );
    }

    /**
     * @param array $configuration
     * @param array $expected
     * @dataProvider buildProcessTriggerDataProvider
     */
    public function testBuildProcessTrigger(array $configuration, array $expected)
    {
        $triggerDefinition = new ProcessDefinition();
        $trigger = $this->builder->buildProcessTrigger($configuration, $triggerDefinition);
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger', $trigger);
        $this->assertProcessTrigger($expected, $triggerDefinition, $trigger);
    }

    /**
     * @return array
     */
    public function buildProcessTriggerDataProvider()
    {
        return array(
            'minimum data' => array(
                'configuration' => array(
                    'event' => ProcessTrigger::EVENT_CREATE,
                ),
                'expected' => array(
                    'event' => ProcessTrigger::EVENT_CREATE,
                    'field' => null,
                    'time_shift' => null,
                ),
            ),
            'integer time shift' => array(
                'configuration' => array(
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
                'configuration' => array(
                    'event' => ProcessTrigger::EVENT_DELETE,
                    'time_shift' => new \DateInterval('P1D')
                ),
                'expected' => array(
                    'event' => ProcessTrigger::EVENT_DELETE,
                    'time_shift' => 24 * 3600,
                    'field' => null,
                ),
            ),
        );
    }

    /**
     * @param array $configuration
     * @param string $exception
     * @param string $message
     * @dataProvider buildProcessTriggerExceptionDataProvider
     */
    public function testBuildProcessTriggerException(array $configuration, $exception, $message)
    {
        $this->setExpectedException($exception, $message);
        $this->builder->buildProcessTrigger($configuration, new ProcessDefinition());
    }

    /**
     * @return array
     */
    public function buildProcessTriggerExceptionDataProvider()
    {
        return array(
            'not allowed event' => array(
                'configuration' => array(
                    'event' => 'my_custom_event',
                ),
                'exception' => 'Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'message'   => 'Event "my_custom_event" is not allowed'
            ),
            'incorrect time shift' => array(
                'configuration' => array(
                    'event' => ProcessTrigger::EVENT_CREATE,
                    'time_shift' => 'invalid_value',
                ),
                'exception' => 'Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'message'   => 'Time shift parameter must be either integer or DateInterval'
            ),
            'field is not allowed' => array(
                'configuration' => array(
                    'event' => ProcessTrigger::EVENT_CREATE,
                    'field' => 'someField',
                ),
                'exception' => 'Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'message'   => 'Field is only allowed for update event'
            ),
        );
    }

    /**
     * @param array $configuration
     * @param array $expected
     * @dataProvider buildProcessTriggersDataProvider
     */
    public function testBuildProcessTriggers(array $configuration, array $expected)
    {
        $testDefinition = new ProcessDefinition();
        $testDefinition->setName(self::TEST_DEFINITION_NAME);
        $definitionsByName = array(self::TEST_DEFINITION_NAME => $testDefinition);

        $triggers = $this->builder->buildProcessTriggers($configuration, $definitionsByName);

        $expectedTriggers = array();

        $this->assertSameSize($expected, $configuration);
        foreach ($configuration as $definitionName => $configurationData) {
            $this->assertArrayHasKey($definitionName, $expected);
            $expectedData = $expected[$definitionName];
            $this->assertSameSize($expectedData, $configurationData);
            foreach ($expectedData as $expectedDataSet) {
                $expectedTriggers[] = $expectedDataSet;
            }
        }

        $this->assertSameSize($expectedTriggers, $triggers);
        while ($expectedTrigger = array_shift($expectedTriggers)) {
            /** @var ProcessTrigger $trigger */
            $trigger = array_shift($triggers);

            $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger', $trigger);
            $this->assertNotEmpty($trigger->getDefinition());
            $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition', $trigger->getDefinition());
            $definitionName = $trigger->getDefinition()->getName();
            $this->assertArrayHasKey($definitionName, $definitionsByName);
            $this->assertProcessTrigger($expectedTrigger, $definitionsByName[$definitionName], $trigger);
        }
    }

    /**
     * @return array
     */
    public function buildProcessTriggersDataProvider()
    {
        $definitionName = self::TEST_DEFINITION_NAME;
        $basicDataProvider = $this->buildProcessTriggerDataProvider();

        $configuration = array();
        $expected = array();
        foreach ($basicDataProvider as $dataSet) {
            $configuration[$definitionName][] = $dataSet['configuration'];
            $expected[$definitionName][] = $dataSet['expected'];
        }

        return array(
            array(
                'configuration' => $configuration,
                'expected' => $expected,
            )
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Process definition "not_existing_definition" not found
     */
    public function testBuildProcessTriggersException()
    {
        $this->builder->buildProcessTriggers(
            array('not_existing_definition' => array('triggers', 'configuration')),
            array('existing_definition' => new ProcessDefinition())
        );
    }
}
