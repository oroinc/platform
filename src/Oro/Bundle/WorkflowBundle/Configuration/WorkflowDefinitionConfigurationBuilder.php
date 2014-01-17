<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;

class WorkflowDefinitionConfigurationBuilder extends AbstractConfigurationBuilder
{
    /**
     * @var WorkflowAssembler
     */
    protected $workflowAssembler;

    /**
     * @param WorkflowAssembler $workflowAssembler
     */
    public function __construct(WorkflowAssembler $workflowAssembler)
    {
        $this->workflowAssembler = $workflowAssembler;
    }

    /**
     * @param array $configurationData
     * @return WorkflowDefinition[]
     */
    public function buildFromConfiguration($configurationData)
    {
        $workflowDefinitions = array();
        foreach ($configurationData as $workflowName => $workflowConfiguration) {
            $this->assertConfigurationOptions($workflowConfiguration, array('label', 'entity'));

            $enabled = $this->getConfigurationOption($workflowConfiguration, 'enabled', true);
            $startStepName = $this->getConfigurationOption($workflowConfiguration, 'start_step', null);
            $entityAttributeName = $this->getConfigurationOption(
                $workflowConfiguration,
                'entity_attribute',
                WorkflowConfiguration::DEFAULT_ENTITY_ATTRIBUTE
            );

            $workflowDefinition = new WorkflowDefinition();
            $workflowDefinition
                ->setName($workflowName)
                ->setLabel($workflowConfiguration['label'])
                ->setRelatedEntity($workflowConfiguration['entity'])
                ->setEnabled($enabled)
                ->setEntityAttributeName($entityAttributeName)
                ->setConfiguration($workflowConfiguration);

            $this->setWorkflowSteps($workflowDefinition);
            $workflowDefinition->setStartStep($workflowDefinition->getStepByName($startStepName));

            $workflowDefinitions[] = $workflowDefinition;
        }

        return $workflowDefinitions;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     */
    protected function setWorkflowSteps(WorkflowDefinition $workflowDefinition)
    {
        $workflow = $this->workflowAssembler->assemble($workflowDefinition);

        $workflowSteps = array();
        foreach ($workflow->getStepManager()->getSteps() as $step) {
            $workflowStep = new WorkflowStep();
            $workflowStep
                ->setName($step->getName())
                ->setLabel($step->getLabel())
                ->setStepOrder($step->getOrder());

            $workflowSteps[] = $workflowStep;
        }

        $workflowDefinition->setSteps($workflowSteps);
    }
}
