<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\UserBundle\Tests\Behat\Element\UserRoleViewForm;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * Example: And I append grid "test-grid" for active workflow "My Workflow Title"
     *
     * @Then /^(?:|I )append grid "(?P<gridName>[^"]*)" for active workflow "(?P<workflowTitle>[^"]*)"$/
     *
     * @param string $gridName
     * @param string $workflowTitle
     */
    public function iAppendGridForActiveWorkflow($gridName, $workflowTitle)
    {
        $workflow = $this->getWorkflowByTitle($workflowTitle);
        self::assertNotNull($workflow, sprintf('There is no workflow with title "%s"', $workflowTitle));

        $configuration = $workflow->getDefinition()->getConfiguration();
        $configuration[WorkflowDefinition::CONFIG_DATAGRIDS][] =  $gridName;
        $workflow->getDefinition()->setConfiguration($configuration);

        $this->getContainer()->get('doctrine')->getManagerForClass(WorkflowDefinition::class)->flush();
    }

    /**
     * Example: And I allow workflow "My Workflow Title" for "default" application
     *
     * @Then /^(?:|I )allow workflow "(?P<workflowTitle>[^"]*)" for "(?P<group>[^"]*)" application$/
     *
     * @param string $workflowTitle
     * @param string $group
     */
    public function iAllowWorkflowForApplication($workflowTitle, $group)
    {
        $workflow = $this->getWorkflowByTitle($workflowTitle);
        self::assertNotNull($workflow, sprintf('There is no workflow with title "%s"', $workflowTitle));

        $applications = $workflow->getDefinition()->getApplications();
        $applications[] = $group;

        $workflow->getDefinition()->setApplications($applications);

        $doctrine = $this->getContainer()->get('doctrine');
        $doctrine->getManagerForClass(WorkflowDefinition::class)->flush();
    }

    /**
     * @param string $title
     * @return Workflow|null
     */
    protected function getWorkflowByTitle($title)
    {
        /* @var $translationHelper WorkflowTranslationHelper */
        $translationHelper = $this->getContainer()->get('oro_workflow.helper.translation');

        /* @var $workflowRegistry WorkflowRegistry */
        $workflowRegistry = $this->getContainer()->get('oro_workflow.registry');

        $workflows = $workflowRegistry->getActiveWorkflows()->filter(
            function (Workflow $workflow) use ($translationHelper, $title) {
                return $title === $translationHelper->findTranslation($workflow->getLabel());
            }
        );

        return $workflows->isEmpty() ? null : $workflows->first();
    }

    /**
     * @param string $title
     *
     * @return WorkflowDefinition|null
     */
    private function getWorkflowDefinitionByTitle(string $title): WorkflowDefinition
    {
        /* @var $translationHelper WorkflowTranslationHelper */
        $translationHelper = $this->getContainer()->get('oro_workflow.helper.translation');

        /* @var $workflowRegistry WorkflowRegistry */
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $workflowDefinitionRepository = $doctrineHelper->getEntityRepositoryForClass(WorkflowDefinition::class);

        $workflowDefinitions = array_filter(
            $workflowDefinitionRepository->findAll(),
            function (WorkflowDefinition $workflow) use ($translationHelper, $title) {
                return $title === $translationHelper->findTranslation($workflow->getLabel());
            }
        );

        return $workflowDefinitions ? reset($workflowDefinitions) : null;
    }

    /**
     * Asserts that provided workflow permissions allowed on role view page
     *
     * Example: Then the role has following active workflow permissions:
     *            | Test Workflow | View Workflow:Global | Perform transitions:Global |
     *
     * @Then /^the role has following active workflow permissions:$/
     *
     * @param TableNode $table
     */
    public function iSeeFollowingWorkflowPermissions(TableNode $table)
    {
        /** @var UserRoleViewForm $userRoleForm */
        $userRoleForm = $this->elementFactory->createElement('User Role View Workflow Permissions');
        $permissionsArray = $userRoleForm->getPermissions();
        foreach ($table->getRows() as $row) {
            $workflowName = array_shift($row);

            foreach ($row as $cell) {
                list($role, $value) = explode(':', $cell);
                self::assertNotEmpty($permissionsArray[$workflowName][$role]);
                $expected = $permissionsArray[$workflowName][$role];
                self::assertEquals(
                    $expected,
                    $value,
                    "Failed asserting that workflow permission $expected equals $value for $workflowName"
                );
            }
        }
    }

    /**
     * @Given /^complete workflow fixture loading$/
     */
    public function completeWorkflowFixtureLoading()
    {
        $container = $this->getContainer();

        $cache = $container->get('oro_workflow.cache.entity_aware');
        $cache->invalidateActiveRelated();

        $provider = $container->get('oro_translation.provider.translation_domain');
        $provider->clearCache();

        $translator = $container->get('translator.default');
        $translator->rebuildCache();

        $dumper = $container->get('oro_translation.js_dumper');
        $dumper->dumpTranslations();
    }

    /**
     * @Given /^(?:I )?activate "(?P<workflowTitle>[^"]*)" workflow$/
     *
     * @param string $workflowTitle
     */
    public function activateWorkflow(string $workflowTitle): void
    {
        $workflowDefinition = $this->getWorkflowDefinitionByTitle($workflowTitle);

        self::assertNotNull($workflowDefinition, sprintf('Workflow %s was not found', $workflowTitle));

        $helper = $this->getContainer()->get('oro_workflow.helper.workflow_deactivation');

        try {
            $workflowsToDeactivate = $helper->getWorkflowsToDeactivation($workflowDefinition)
                ->map(
                    function (Workflow $workflow) {
                        return $workflow->getName();
                    }
                )->getValues();

            $this->deactivateWorkflows($workflowsToDeactivate);
        } catch (WorkflowException $e) {
            $workflowDefinition = null;
        }

        self::assertNotEmpty($workflowDefinition, sprintf('Workflow %s could not be activated', $workflowTitle));

        $this->getContainer()->get('oro_workflow.registry.workflow_manager')->getManager()
            ->activateWorkflow($workflowDefinition->getName());
    }

    /**
     * @param array $workflowNames
     *
     * @return array
     * @throws WorkflowException
     */
    private function deactivateWorkflows(array $workflowNames): void
    {
        $workflowManager = $this->getContainer()->get('oro_workflow.registry.workflow_manager')->getManager();

        foreach ($workflowNames as $workflowName) {
            if ($workflowName && $workflowManager->isActiveWorkflow($workflowName)) {
                $workflow = $workflowManager->getWorkflow($workflowName);

                $workflowManager->resetWorkflowData($workflow->getName());
                $workflowManager->deactivateWorkflow($workflow->getName());
            }
        }
    }
}
