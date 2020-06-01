<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Command\DebugWorkflowDefinitionsCommand;
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

    public function testExecuteWithoutArgument()
    {
        $workflows = $this->getWorkflowDefinitionRepository()->findAll();

        $result = $this->runCommand(DebugWorkflowDefinitionsCommand::getDefaultName());

        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        /** @var WorkflowDefinition $workflow */
        foreach ($workflows as $workflow) {
            static::assertStringContainsString($workflow->getName(), $result);
            static::assertStringContainsString(
                $translator->trans(
                    $workflow->getLabel(),
                    [],
                    WorkflowTranslationHelper::TRANSLATION_DOMAIN
                ),
                $result
            );
            static::assertStringContainsString($workflow->getRelatedEntity(), $result);
        }
    }

    /**
     * @param string $workflowName
     * @param bool $exists
     *
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithArgument($workflowName, $exists = true)
    {
        /** @var WorkflowDefinition $initialWorkflow */
        $initialWorkflow = $this->getWorkflowDefinitionRepository()->findOneBy(['name' => $workflowName]);

        $result = $this->runCommand(DebugWorkflowDefinitionsCommand::getDefaultName(), [$workflowName], false);

        if ($exists) {
            static::assertStringNotContainsString('No workflow definitions found.', $result);

            $workflowConfiguration = Yaml::parse($result);
            $workflowConfiguration = $this->getContainer()
                ->get('oro_workflow.configuration.config.workflow_list')
                ->processConfiguration($workflowConfiguration);

            $workflowDefinitions = $this->getContainer()
                ->get('oro_workflow.configuration.builder.workflow_definition')
                ->buildFromConfiguration($workflowConfiguration);

            $this->assertCount(1, $workflowDefinitions);

            /** @var WorkflowDefinition $workflowDefinition */
            $workflowDefinition = reset($workflowDefinitions);

            $this->assertEquals($initialWorkflow->getName(), $workflowDefinition->getName());
            $this->assertEquals($initialWorkflow->getLabel(), $workflowDefinition->getLabel());
            $this->assertEquals($initialWorkflow->getRelatedEntity(), $workflowDefinition->getRelatedEntity());

            if ($initialWorkflow->getStartStep()) {
                $this->assertNotNull($workflowDefinition->getStartStep());
                $this->assertEquals(
                    $initialWorkflow->getStartStep()->getName(),
                    $workflowDefinition->getStartStep()->getName()
                );
            }

            $this->assertEquals($initialWorkflow->getConfiguration(), $workflowDefinition->getConfiguration());
            $this->assertEquals(
                $initialWorkflow->isStepsDisplayOrdered(),
                $workflowDefinition->isStepsDisplayOrdered()
            );

            $this->assertEquals($initialWorkflow->getPriority(), $workflowDefinition->getPriority());
            $this->assertEquals($initialWorkflow->isActive(), $workflowDefinition->isActive());
            $this->assertEquals(
                $initialWorkflow->getExclusiveActiveGroups(),
                $workflowDefinition->getExclusiveActiveGroups()
            );
            $this->assertEquals(
                $initialWorkflow->getExclusiveRecordGroups(),
                $workflowDefinition->getExclusiveRecordGroups()
            );
        } else {
            static::assertStringContainsString('No workflow definitions found.', $result);
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [LoadWorkflowDefinitions::NO_START_STEP, true],
            [LoadWorkflowDefinitions::WITH_START_STEP, true],
            [uniqid(), false],
            [LoadWorkflowDefinitionsWithGroups::WITH_GROUPS1, true],
            [LoadWorkflowDefinitionsWithGroups::WITH_GROUPS2, true],
        ];
    }

    /**
     * @return WorkflowDefinitionRepository
     */
    protected function getWorkflowDefinitionRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(WorkflowDefinition::class);
    }
}
