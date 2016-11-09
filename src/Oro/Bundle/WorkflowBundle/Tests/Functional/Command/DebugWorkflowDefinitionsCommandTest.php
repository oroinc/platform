<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\WorkflowBundle\Command\DebugWorkflowDefinitionsCommand;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @dbIsolation
 */
class DebugWorkflowDefinitionsCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions']);
    }

    public function testExecuteWithoutArgument()
    {
        $workflows = $this->getWorkflowDefinitionRepository()->findAll();

        $result = $this->runCommand(DebugWorkflowDefinitionsCommand::NAME, ['--no-ansi']);

        /** @var WorkflowDefinition $workflow */
        foreach ($workflows as $workflow) {
            $this->assertContains($workflow->getName(), $result);
            $this->assertContains($workflow->getLabel(), $result);
            $this->assertContains($workflow->getRelatedEntity(), $result);
        }
    }
    /**
     * @param string $workflowName
     *
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithArgument($workflowName)
    {
        /** @var WorkflowDefinition $initialWorkflow */
        $initialWorkflow = $this->getWorkflowDefinitionRepository()->findOneBy(['name' => $workflowName]);

        $result = $this->runCommand(DebugWorkflowDefinitionsCommand::NAME, [$workflowName, '--no-ansi']);

        if ($initialWorkflow) {
            $this->assertNotContains('No workflow definitions found.', $result);

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
        } else {
            $this->assertContains('No workflow definitions found.', $result);
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [LoadWorkflowDefinitions::NO_START_STEP],
            [LoadWorkflowDefinitions::START_TRANSITION],
            [LoadWorkflowDefinitions::WITH_START_STEP],
            [uniqid()],
        ];
    }

    /**
     * @return ObjectRepository
     */
    protected function getWorkflowDefinitionRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(WorkflowDefinition::class);
    }
}
