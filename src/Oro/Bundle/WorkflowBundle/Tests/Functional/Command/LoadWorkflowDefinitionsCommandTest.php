<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * @dbIsolation
 */
class LoadWorkflowDefinitionsCommandTest extends WebTestCase
{
    const NAME = 'oro:workflow:definitions:load';

    /** @var WorkflowConfigurationProvider */
    protected $provider;

    /** @var \ReflectionProperty */
    protected $reflectionProperty;

    protected function setUp()
    {
        $this->initClient();

        $this->provider = $this->getContainer()->get('oro_workflow.configuration.provider.workflow_config');

        $reflectionClass = new \ReflectionClass(WorkflowConfigurationProvider::class);

        $this->reflectionProperty = $reflectionClass->getProperty('configDirectory');
        $this->reflectionProperty->setAccessible(true);
        $this->reflectionProperty->setValue(
            $this->provider,
            '/Tests/Functional/Command/DataFixtures/WithTransitionTriggers'
        );
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $expectedMessages
     * @param array $expectedDefinitions
     * @param array $expectedEventTriggers
     * @param array $expectedCronTriggers
     */
    public function testExecute(
        array $expectedMessages,
        array $expectedDefinitions,
        array $expectedEventTriggers,
        array $expectedCronTriggers
    ) {
        $repositoryWorkflow = $this->getRepository('OroWorkflowBundle:WorkflowDefinition');
        $repositoryProcess = $this->getRepository('OroWorkflowBundle:ProcessDefinition');
        $repositoryTrigger = $this->getRepository('OroWorkflowBundle:BaseTransitionTrigger');

        $definitionsBefore = $repositoryWorkflow->findAll();
        $processesBefore = $repositoryProcess->findAll();
        $triggersBefore = $repositoryTrigger->findAll();

        $this->assertCommandExecuted($expectedMessages);

        $definitions = $repositoryWorkflow->findAll();
        $processes = $repositoryProcess->findAll();
        $triggers = $repositoryTrigger->findAll();

        $this->assertCount(count($definitionsBefore) + count($expectedDefinitions), $definitions);
        $this->assertCount(count($processesBefore), $processes);
        foreach ($expectedDefinitions as $definitionName) {
            $this->assertDefinitionLoaded($definitions, $definitionName);
        }

        $this->assertCount(
            count($triggersBefore) + count($expectedEventTriggers) + count($expectedCronTriggers),
            $triggers
        );
        foreach ($expectedEventTriggers as $eventTrigger) {
            $this->assertTransitionEventTriggerLoaded($triggers, $eventTrigger['event'], $eventTrigger['field']);
        }

        foreach ($expectedCronTriggers as $cronTrigger) {
            $this->assertTransitionCronTriggerLoaded($triggers, $cronTrigger['cron']);
        }

        $this->reflectionProperty->setValue(
            $this->provider,
            '/Tests/Functional/Command/DataFixtures/WithoutTransitionTriggers'
        );

        $this->assertCommandExecuted($expectedMessages);

        $this->assertCount(count($definitions), $repositoryWorkflow->findAll());
        $this->assertCount(count($triggersBefore), $repositoryTrigger->findAll());
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'expectedMessages' => [
                    'Loading workflow definitions...',
                ],
                'expectedDefinitions' => [
                    'first_workflow',
                    'second_workflow'
                ],
                'expectedEventTriggers' => [
                    ['event' => EventTriggerInterface::EVENT_CREATE, 'field' => null],
                    ['event' => EventTriggerInterface::EVENT_UPDATE, 'field' => null],
                    ['event' => EventTriggerInterface::EVENT_UPDATE, 'field' => 'name'],
                    ['event' => EventTriggerInterface::EVENT_DELETE, 'field' => null]
                ],
                'expectedCronTriggers' => [
                    ['cron' => '*/1 * * * *']
                ]
            ]
        ];
    }

    public function testExecuteErrors()
    {
        $this->reflectionProperty->setValue(
            $this->provider,
            '/Tests/Functional/Command/DataFixtures/InvalidCronExpression'
        );

        $expectedMessages = [
            'Invalid configuration for path "workflows.first_workflow.transitions.second_transition.triggers.0.cron"' .
            ': invalid cron expression is not a valid CRON expression'
        ];

        $this->assertCommandExecuted($expectedMessages);

        $this->reflectionProperty->setValue(
            $this->provider,
            '/Tests/Functional/Command/DataFixtures/InvalidFilterExpression'
        );

        $expectedMessages = [
            'Expected =, <, <=, <>, >, >=, !=, got end of string'
        ];

        $this->assertCommandExecuted($expectedMessages);
    }

    /**
     * @param array $messages
     */
    protected function assertCommandExecuted(array $messages)
    {
        $result = $this->runCommand(self::NAME, ['--no-ansi']);

        $this->assertNotEmpty($result);
        foreach ($messages as $message) {
            $this->assertContains($message, $result);
        }
    }

    /**
     * @param array|WorkflowDefinition[] $definitions
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
     * @param array|ProcessDefinition[] $processDefinitions
     * @param string $name
     */
    protected function assertProcessLoaded(array $processDefinitions, $name)
    {
        $found = false;

        foreach ($processDefinitions as $definition) {
            if (strpos($definition->getName(), $name) !== false) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * @param array|TransitionEventTrigger[] $triggers
     * @param string $event
     * @param string $field
     */
    protected function assertTransitionEventTriggerLoaded(array $triggers, $event, $field)
    {
        $found = false;

        foreach ($triggers as $trigger) {
            if ($trigger instanceof TransitionEventTrigger &&
                $trigger->getEvent() === $event &&
                $trigger->getField() === $field
            ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * @param array|TransitionCronTrigger[] $triggers
     * @param string $cron
     */
    protected function assertTransitionCronTriggerLoaded(array $triggers, $cron)
    {
        $found = false;

        foreach ($triggers as $trigger) {
            if ($trigger instanceof TransitionCronTrigger && $trigger->getCron() === $cron) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * @param string $entityClass
     *
     * @return ObjectRepository
     */
    protected function getRepository($entityClass)
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($entityClass)
            ->getRepository($entityClass);
    }
}
