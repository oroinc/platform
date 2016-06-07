<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Console\Tester\CommandTester;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Command\LoadProcessConfigurationCommand;

/**
 * @dbIsolation
 */
class LoadProcessConfigurationCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $provider = $this->getContainer()->get('oro_workflow.configuration.provider.process_config');

        $reflectionClass = new \ReflectionClass('Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider');

        $reflectionProperty = $reflectionClass->getProperty('configDirectory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($provider, '/Tests/Functional/Command/DataFixtures/');
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $expectedMessages
     * @param array $expectedDefinitions
     * @param array $expectedTriggers
     */
    public function testExecute(array $expectedMessages, array $expectedDefinitions, array $expectedTriggers)
    {
        $definitionsBefore = $this->getRepository('OroWorkflowBundle:ProcessDefinition')->findAll();
        $triggersBefore = $this->getRepository('OroWorkflowBundle:ProcessTrigger')->findAll();

        $result = $this->runCommand(LoadProcessConfigurationCommand::NAME);

        $this->assertNotEmpty($result);
        foreach ($expectedMessages as $message) {
            $this->assertContains($message, $result);
        }

        $definitions = $this->getRepository('OroWorkflowBundle:ProcessDefinition')->findAll();

        $this->assertCount(count($definitionsBefore) + 2, $definitions);
        foreach ($expectedDefinitions as $definition) {
            $this->assertDefinitionLoaded($definitions, $definition);
        }

        $triggers = $this->getRepository('OroWorkflowBundle:ProcessTrigger')->findAll();

        $this->assertCount(count($triggersBefore) + 4, $triggers);
        foreach ($expectedTriggers as $trigger) {
            $this->assertTriggerLoaded($triggers, $trigger['name'], $trigger['event'], $trigger['cron']);
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'expectedMessages' => [
                    '"test_definition" - created',
                    '"another_definition" - created',
                    'Process definitions configuration updates are stored into database',
                    'test_definition [update] - created',
                    'process trigger: test_definition [create] - created',
                    'process trigger: test_definition [delete] - created',
                    'process trigger: test_definition [cron:*/1 * * * *] - created',
                    'process triggers modifications stored in DB',
                    'process trigger cron schedule [*/1 * * * *]',
                    'process trigger schedule modification persisted.',
                ],
                'expectedDefinitions' => [
                    'test_definition',
                    'another_definition'
                ],
                'expectedTriggers' => [
                    ['name' => 'test_definition', 'event' => 'update', 'cron' => null],
                    ['name' => 'test_definition', 'event' => 'create', 'cron' => null],
                    ['name' => 'test_definition', 'event' => 'delete', 'cron' => null],
                    ['name' => 'test_definition', 'event' => null, 'cron' => '*/1 * * * *']
                ]
            ]
        ];
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className)->getRepository($className);
    }

    /**
     * @param array|ProcessDefinition[] $definitions
     * @param string $name
     */
    protected function assertDefinitionLoaded(array $definitions, $name)
    {
        $found = false;

        foreach ($definitions as $definition) {
            if ($definition->getName() === $name) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * @param array|ProcessTrigger[] $triggers
     * @param string $name
     * @param string $event
     * @param string $cron
     */
    protected function assertTriggerLoaded(array $triggers, $name, $event, $cron)
    {
        $found = false;

        foreach ($triggers as $trigger) {
            if ($trigger->getEvent() === $event &&
                $trigger->getCron() === $cron &&
                $trigger->getDefinition()->getName() === $name
            ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }
}
