<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Command\DebugWorkflowDefinitionsCommand;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionsWithGroups;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

class DebugWorkflowDefinitionsCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadWorkflowDefinitions::class,
            LoadWorkflowDefinitionsWithGroups::class,
        ]);
    }

    public function testExecuteWithoutArgument(): void
    {
        $workflows = $this->getWorkflowDefinitionRepository()->findAll();

        $result = self::runCommand(DebugWorkflowDefinitionsCommand::getDefaultName());

        /** @var TranslatorInterface $translator */
        $translator = self::getContainer()->get('translator');

        /** @var WorkflowDefinition $workflow */
        foreach ($workflows as $workflow) {
            self::assertStringContainsString($workflow->getName(), $result);
            self::assertStringContainsString(
                $translator->trans($workflow->getLabel(), [], WorkflowTranslationHelper::TRANSLATION_DOMAIN),
                $result
            );
            self::assertStringContainsString($workflow->getRelatedEntity(), $result);
        }
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithArgument(string $workflowName): void
    {
        /** @var WorkflowDefinition $initialWorkflow */
        $initialWorkflow = $this->getWorkflowDefinitionRepository()->findOneBy(['name' => $workflowName]);

        $result = self::runCommand(DebugWorkflowDefinitionsCommand::getDefaultName(), [$workflowName], false);

        self::assertStringNotContainsString('No workflow definitions found.', $result);

        $workflowConfiguration = Yaml::parse($result);
        $workflowConfiguration = self::getContainer()
            ->get('oro_workflow.configuration.config.workflow_list')
            ->processConfiguration($workflowConfiguration);

        $workflowDefinitions = self::getContainer()
            ->get('oro_workflow.configuration.builder.workflow_definition')
            ->buildFromConfiguration($workflowConfiguration);

        self::assertCount(1, $workflowDefinitions);

        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = reset($workflowDefinitions);

        $workflowConfiguration = reset($workflowConfiguration);

        self::assertEquals($initialWorkflow->getName(), $workflowDefinition->getName());
        self::assertEquals($initialWorkflow->getLabel(), $workflowDefinition->getLabel());
        self::assertEquals($initialWorkflow->getRelatedEntity(), $workflowDefinition->getRelatedEntity());

        if ($initialWorkflow->getStartStep()) {
            self::assertNotNull($workflowDefinition->getStartStep());
            self::assertEquals(
                $initialWorkflow->getStartStep()->getName(),
                $workflowDefinition->getStartStep()->getName()
            );
        }

        self::assertEquals($initialWorkflow->getConfiguration(), $workflowDefinition->getConfiguration());
        self::assertEquals(
            $initialWorkflow->isStepsDisplayOrdered(),
            $workflowDefinition->isStepsDisplayOrdered()
        );

        self::assertEquals($initialWorkflow->getPriority(), $workflowDefinition->getPriority());
        self::assertEquals($initialWorkflow->isActive(), $workflowDefinition->isActive());
        self::assertEquals(
            $initialWorkflow->getExclusiveActiveGroups(),
            $workflowDefinition->getExclusiveActiveGroups()
        );
        self::assertEquals(
            $initialWorkflow->getExclusiveRecordGroups(),
            $workflowDefinition->getExclusiveRecordGroups()
        );
        self::assertArrayNotHasKey(WorkflowConfiguration::NODE_INIT_ENTITIES, $workflowConfiguration);
        self::assertArrayNotHasKey(WorkflowConfiguration::NODE_INIT_ROUTES, $workflowConfiguration);
        self::assertArrayNotHasKey(WorkflowConfiguration::NODE_INIT_DATAGRIDS, $workflowConfiguration);
    }

    public function executeDataProvider(): array
    {
        return [
            [LoadWorkflowDefinitions::NO_START_STEP],
            [LoadWorkflowDefinitions::WITH_START_STEP],
            [LoadWorkflowDefinitionsWithGroups::WITH_GROUPS1],
            [LoadWorkflowDefinitionsWithGroups::WITH_GROUPS2],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteDumpNotContainsLabels(): void
    {
        $workflowName = 'test_flow_transition_with_condition';

        /** @var WorkflowDefinition $initialWorkflow */
        $initialWorkflow = $this->getWorkflowDefinitionRepository()->findOneBy(['name' => $workflowName]);

        $result = self::runCommand(DebugWorkflowDefinitionsCommand::getDefaultName(), [$workflowName], false);

        self::assertStringNotContainsString('No workflow definitions found.', $result);

        $workflowConfiguration = Yaml::parse($result);
        $workflowConfiguration = self::getContainer()
            ->get('oro_workflow.configuration.config.workflow_list')
            ->processConfiguration($workflowConfiguration);

        $workflowDefinitions = self::getContainer()
            ->get('oro_workflow.configuration.builder.workflow_definition')
            ->buildFromConfiguration($workflowConfiguration);

        self::assertCount(1, $workflowDefinitions);

        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = reset($workflowDefinitions);

        $workflowConfiguration = reset($workflowConfiguration);

        self::assertEquals($initialWorkflow->getName(), $workflowDefinition->getName());
        self::assertEquals($initialWorkflow->getLabel(), $workflowDefinition->getLabel());
        self::assertEquals($initialWorkflow->getRelatedEntity(), $workflowDefinition->getRelatedEntity());

        if ($initialWorkflow->getStartStep()) {
            self::assertNotNull($workflowDefinition->getStartStep());
            self::assertEquals(
                $initialWorkflow->getStartStep()->getName(),
                $workflowDefinition->getStartStep()->getName()
            );
        }

        self::assertEquals($initialWorkflow->getConfiguration(), $workflowDefinition->getConfiguration());
        self::assertEquals(
            $initialWorkflow->isStepsDisplayOrdered(),
            $workflowDefinition->isStepsDisplayOrdered()
        );

        self::assertEquals($initialWorkflow->getPriority(), $workflowDefinition->getPriority());
        self::assertEquals($initialWorkflow->isActive(), $workflowDefinition->isActive());
        self::assertEquals(
            $initialWorkflow->getExclusiveActiveGroups(),
            $workflowDefinition->getExclusiveActiveGroups()
        );
        self::assertEquals(
            $initialWorkflow->getExclusiveRecordGroups(),
            $workflowDefinition->getExclusiveRecordGroups()
        );

        self::assertArrayNotHasKey(WorkflowConfiguration::NODE_INIT_ENTITIES, $workflowConfiguration);
        self::assertArrayNotHasKey(WorkflowConfiguration::NODE_INIT_ROUTES, $workflowConfiguration);
        self::assertArrayNotHasKey(WorkflowConfiguration::NODE_INIT_DATAGRIDS, $workflowConfiguration);

        // Checks that workflow parameters containing translation keys are removed.
        self::assertArrayNotHasKey(
            'label',
            $workflowConfiguration[WorkflowConfiguration::NODE_STEPS]['starting_point']
        );
        self::assertArrayNotHasKey(
            'label',
            $workflowConfiguration[WorkflowConfiguration::NODE_ATTRIBUTES]['sample_attribute']
        );
        self::assertArrayNotHasKey(
            'label',
            $workflowConfiguration[WorkflowConfiguration::NODE_TRANSITIONS]['starting_point_transition']
        );
        self::assertArrayNotHasKey(
            'message',
            $workflowConfiguration[WorkflowConfiguration::NODE_TRANSITIONS]['starting_point_transition']
        );
        self::assertArrayNotHasKey(
            'button_label',
            $workflowConfiguration[WorkflowConfiguration::NODE_TRANSITIONS]['starting_point_transition']
        );
        self::assertArrayNotHasKey(
            'button_title',
            $workflowConfiguration[WorkflowConfiguration::NODE_TRANSITIONS]['starting_point_transition']
        );
        $variables =& $workflowConfiguration[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS];
        self::assertArrayNotHasKey(
            'label',
            $variables[WorkflowConfiguration::NODE_VARIABLES]['var1']
        );

        $transitionDefs =& $workflowConfiguration[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS];
        self::assertArrayHasKey(
            'message',
            $transitionDefs['second_point_transition_definition']['conditions']['@and'][0]['@equal']
        );
        self::assertEquals(
            'custom.test.message',
            $transitionDefs['second_point_transition_definition']['conditions']['@and'][0]['@equal']['message']
        );
    }

    public function testExecuteWithArgumentWhenWorkflowNotExists(): void
    {
        $result = self::runCommand(DebugWorkflowDefinitionsCommand::getDefaultName(), ['missing_workflow'], false);

        self::assertStringContainsString('No workflow definitions found.', $result);
    }

    private function getWorkflowDefinitionRepository(): WorkflowDefinitionRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(WorkflowDefinition::class);
    }
}
