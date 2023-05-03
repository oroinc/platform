<?php
declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Command\OroTranslationLoadCommand;
use Oro\Bundle\WorkflowBundle\Command\LoadWorkflowDefinitionsCommand;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Component\Testing\Command\CommandOutputNormalizer;

class LoadWorkflowDefinitionsCommandTest extends WebTestCase
{
    private WorkflowConfigFinderBuilder $configFinderBuilder;

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
                    \sprintf(
                        "Please run command '%s' to load translations.",
                        OroTranslationLoadCommand::getDefaultName()
                    )
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

    public function testOutputVerbosityNormal()
    {
        $this->configFinderBuilder->setSubDirectory(
            '/Tests/Functional/Command/DataFixtures/ValidDefinitionsVerbosityNormal'
        );

        $output = $this->runCommand(LoadWorkflowDefinitionsCommand::getDefaultName(), []);

        static::assertStringContainsString('Loading workflow definitions...', $output);
        static::assertStringContainsString('Done.', $output);
        static::assertStringNotContainsString(
            'Processed 1 workflow definitions: updated 0 existing workflows, created 1 new workflows',
            $output
        );
        static::assertStringNotContainsString('> workflow_verbosity_normal', $output);
        static::assertStringNotContainsString(
            $this->getWorkflowConfigurationDump('workflow_verbosity_normal'),
            $output,
            \sprintf(
                '"%s -v" should not dump workflow configuration to output.',
                LoadWorkflowDefinitionsCommand::getDefaultName()
            )
        );
    }

    public function testOutputVerbosityVerbose()
    {
        $this->configFinderBuilder->setSubDirectory('/Tests/Functional/Command/DataFixtures/ValidDefinitionsVerbose');

        $output = $this->runCommand(LoadWorkflowDefinitionsCommand::getDefaultName(), ['-v']);

        static::assertStringContainsString('Loading workflow definitions...', $output);
        static::assertStringContainsString('Done.', $output);
        static::assertStringContainsString(
            'Processed 1 workflow definitions: updated 0 existing workflows, created 1 new workflows',
            $output
        );
        static::assertStringNotContainsString('> workflow_verbose', $output);
        static::assertStringNotContainsString(
            $this->getWorkflowConfigurationDump('workflow_verbose'),
            $output,
            \sprintf(
                '"%s -v" should not dump workflow configuration to output.',
                LoadWorkflowDefinitionsCommand::getDefaultName()
            )
        );
    }

    public function testOutputVerbosityVeryVerbose()
    {
        $this->configFinderBuilder->setSubDirectory(
            '/Tests/Functional/Command/DataFixtures/ValidDefinitionsVeryVerbose'
        );

        $output = $this->runCommand(LoadWorkflowDefinitionsCommand::getDefaultName(), ['-vv']);

        static::assertStringContainsString('Loading workflow definitions...', $output);
        static::assertStringContainsString('Done.', $output);
        static::assertStringContainsString(
            'Processed 1 workflow definitions: updated 0 existing workflows, created 1 new workflows',
            $output
        );
        static::assertStringContainsString('> workflow_very_verbose', $output);
        static::assertStringNotContainsString(
            $this->getWorkflowConfigurationDump('workflow_very_verbose'),
            $output,
            \sprintf(
                '"%s -vv" should not dump workflow configuration to output.',
                LoadWorkflowDefinitionsCommand::getDefaultName()
            )
        );
    }

    public function testOutputVerbosityDebug()
    {
        $this->configFinderBuilder->setSubDirectory('/Tests/Functional/Command/DataFixtures/ValidDefinitionsDebug');

        $output = $this->runCommand(LoadWorkflowDefinitionsCommand::getDefaultName(), ['-vvv']);

        static::assertStringContainsString('Loading workflow definitions...', $output);
        static::assertStringContainsString('Done.', $output);
        static::assertStringContainsString(
            'Processed 1 workflow definitions: updated 0 existing workflows, created 1 new workflows',
            $output
        );
        static::assertStringContainsString('> workflow_debug', $output);
        static::assertStringContainsString(
            $this->getWorkflowConfigurationDump('workflow_debug'),
            $output,
            \sprintf(
                '"%s -vvv" should output workflow configuration dump.',
                LoadWorkflowDefinitionsCommand::getDefaultName()
            )
        );
    }

    public function getWorkflowConfigurationDump(string $workflow): string
    {
        return \str_replace(
            '%WORKFLOW%',
            $workflow,
            CommandOutputNormalizer::toSingleLine(
                \file_get_contents(__DIR__ . '/DataFixtures/valid-workflow-dump.yml')
            )
        );
    }

    private function assertCommandExecuted(array $messages): string
    {
        $output = $this->runCommand(LoadWorkflowDefinitionsCommand::getDefaultName());

        $this->assertNotEmpty($output);
        foreach ($messages as $message) {
            self::assertStringContainsString($message, $output);
        }

        return $output;
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
