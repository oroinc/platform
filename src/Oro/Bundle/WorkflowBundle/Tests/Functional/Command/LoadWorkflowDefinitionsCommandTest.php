<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class LoadWorkflowDefinitionsCommandTest extends WebTestCase
{
    const NAME = 'oro:workflow:definitions:load';

    /** @var WorkflowConfigFinderBuilder */
    protected $configFinderBuilder;

    protected function setUp()
    {
        $this->initClient();

        $this->configFinderBuilder = self::getContainer()
            ->get('oro_workflow.configuration.workflow_config_finder.builder');
        $this->configFinderBuilder->setSubDirectory('/Tests/Functional/Command/DataFixtures/WithTransitionTriggers');
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

        $this->configFinderBuilder->setSubDirectory('/Tests/Functional/Command/DataFixtures/WithoutTransitionTriggers');

        $this->assertCommandExecuted($expectedMessages);

        $this->assertCount(count($definitions), $repositoryWorkflow->findAll(), 'definitions count should match');
        $this->assertCount(count($triggersBefore), $repositoryTrigger->findAll(), 'triggers should match');
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
                    'Please run command \'oro:translation:load\' to load translations.'
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

    /**
     * @dataProvider invalidExecuteDataProvider
     *
     * @param $expectedMessages $messages
     * @param string $configDirectory
     */
    public function testExecuteErrors(array $expectedMessages, $configDirectory)
    {
        $this->configFinderBuilder->setSubDirectory($configDirectory);

        $this->assertCommandExecuted($expectedMessages);
    }

    /**
     * @return \Generator
     */
    public function invalidExecuteDataProvider()
    {
        yield 'invalid cron expression' => [
            'expectedMessages' => [
                'In WorkflowConfigurationProvider.php',
                'Invalid configuration for path "workflows.first_workflow',
                'invalid cron expression is not',
                'a valid CRON expression'
            ],
            'configDirectory' => '/Tests/Functional/Command/DataFixtures/InvalidCronExpression'
        ];

        yield 'invalid filter expression' => [
            'expectedMessages' => ['Expected =, <, <=, <>, >, >=, !=, got end of string'],
            'configDirectory' => '/Tests/Functional/Command/DataFixtures/InvalidFilterExpression'
        ];

        yield 'empty start step' => [
            'expectedMessages' => ['does not contains neither start step nor start transitions'],
            'configDirectory' => '/Tests/Functional/Command/DataFixtures/WithoutStartStep'
        ];
    }

    /**
     * @param array $messages
     */
    protected function assertCommandExecuted(array $messages)
    {
        $result = $this->runCommand(self::NAME);

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
