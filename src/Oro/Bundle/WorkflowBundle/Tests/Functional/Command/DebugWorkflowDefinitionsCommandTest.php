<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionsWithGroups;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Command\DebugWorkflowDefinitionsCommand;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @dbIsolation
 */
class DebugWorkflowDefinitionsCommandTest extends WebTestCase
{
    /**
     * @var Container
     */
    protected $container;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions',
            'Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionsWithGroups',
        ]);

        $this->container = $this->getContainer();
    }

    public function testExecuteWithoutArgument()
    {
        $workflows = $this->getContainer()
            ->get('doctrine')
            ->getRepository('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->findAll();

        $result = $this->runCommand(DebugWorkflowDefinitionsCommand::NAME, ['--no-ansi']);

        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        /** @var WorkflowDefinition $workflow */
        foreach ($workflows as $workflow) {
            $this->assertContains($workflow->getName(), $result);
            $this->assertContains(
                $translator->trans(
                    $workflow->getLabel(),
                    [],
                    WorkflowTranslationHelper::TRANSLATION_DOMAIN
                ),
                $result
            );
            $this->assertContains($workflow->getRelatedEntity(), $result);
        }
    }

    /**
     * @param string $workflowName
     *
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithArgument($workflowName, $exists = true)
    {
        /** @var WorkflowDefinition $initialWorkflow */
        $initialWorkflow = $this->getContainer()
            ->get('doctrine')
            ->getRepository('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->findOneBy(['name' => $workflowName]);

        $result = $this->runCommand(DebugWorkflowDefinitionsCommand::NAME, [$workflowName, '--no-ansi']);

        if ($exists) {
            $this->assertNotContains('No workflow definitions found.', $result);
            $listConfiguration = $this->container->get('oro_workflow.configuration.config.workflow_list');
            $configurationBuilder = $this->container->get('oro_workflow.configuration.builder.workflow_definition');

            $workflowConfiguration = Yaml::parse($result);
            $workflowConfiguration = $listConfiguration->processConfiguration($workflowConfiguration);
            $workflowDefinitions = $configurationBuilder->buildFromConfiguration($workflowConfiguration);

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
            $this->assertContains('No workflow definitions found.', $result);
        }
    }

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
}
