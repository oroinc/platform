<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class LoadWorkflowDefinitionsCommandTest extends WebTestCase
{
    private const NAME = 'oro:workflow:definitions:load';

    /** @var WorkflowConfigFinderBuilder */
    private $configFinderBuilder;

    protected function setUp(): void
    {
        $this->initClient();

        $this->configFinderBuilder = self::getContainer()
            ->get('oro_workflow.configuration.workflow_config_finder.builder');
        $this->configFinderBuilder->setSubDirectory('/Tests/Functional/Command/DataFixtures/WithTransitionTriggers');
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        array $expectedMessages,
        array $expectedDefinitions,
        array $expectedEventTriggers,
        array $expectedCronTriggers
    ) {
        $repositoryWorkflow = $this->getRepository(WorkflowDefinition::class);
        $repositoryProcess = $this->getRepository(ProcessDefinition::class);
        $repositoryTrigger = $this->getRepository(BaseTransitionTrigger::class);

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

    public function executeDataProvider(): array
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
     */
    public function testExecuteErrors(array $expectedMessages, string $configDirectory)
    {
        $this->configFinderBuilder->setSubDirectory($configDirectory);

        $this->assertCommandExecuted($expectedMessages);
    }

    public function invalidExecuteDataProvider(): array
    {
        return [
            'invalid cron expression' => [
                'expectedMessages' => [
                    'In WorkflowConfigurationProvider.php',
                    'Invalid configuration for path "workflows.first_workflow',
                    'invalid cron expression is not',
                    'a valid CRON expression'
                ],
                'configDirectory' => '/Tests/Functional/Command/DataFixtures/InvalidCronExpression'
            ],
            'invalid filter expression' => [
                'expectedMessages' => ['Unexpected query syntax error'],
                'configDirectory' => '/Tests/Functional/Command/DataFixtures/InvalidFilterExpression'
            ],
            'empty start step' => [
                'expectedMessages' => ['does not contains neither start step nor start transitions'],
                'configDirectory' => '/Tests/Functional/Command/DataFixtures/WithoutStartStep'
            ]
        ];
    }

    private function assertCommandExecuted(array $messages)
    {
        $result = $this->runCommand(self::NAME);

        $this->assertNotEmpty($result);
        foreach ($messages as $message) {
            self::assertStringContainsString($message, $result);
        }
    }

    /**
     * @param WorkflowDefinition[] $definitions
     * @param string $name
     */
    private function assertDefinitionLoaded(array $definitions, string $name): void
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
     * @param TransitionEventTrigger[] $triggers
     * @param string $event
     * @param string|null $field
     */
    private function assertTransitionEventTriggerLoaded(array $triggers, string $event, ?string $field): void
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
     * @param TransitionCronTrigger[] $triggers
     * @param string $cron
     */
    private function assertTransitionCronTriggerLoaded(array $triggers, string $cron): void
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

    private function getRepository(string $entityClass): EntityRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository($entityClass);
    }
}
