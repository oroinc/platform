<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
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
}
