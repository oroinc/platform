<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinitionEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
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
            $this->assertConfigurationOptions($workflowConfiguration, array('label', 'type'));

            $type = $this->getConfigurationOption($workflowConfiguration, 'type', Workflow::TYPE_ENTITY);
            $enabled = $this->getConfigurationOption($workflowConfiguration, 'enabled', true);
            $startStepName = $this->getConfigurationOption($workflowConfiguration, 'start_step', null);

            $managedEntityClasses = $this->getManagedEntityClasses($workflowConfiguration);
            $definitionEntities = $this->buildDefinitionEntities($managedEntityClasses);

            $workflowDefinition = new WorkflowDefinition();
            $workflowDefinition
                ->setName($workflowName)
                ->setLabel($workflowConfiguration['label'])
                ->setType($type)
                ->setEnabled($enabled)
                ->setConfiguration($workflowConfiguration)
                ->setWorkflowDefinitionEntities($definitionEntities);

            $this->setWorkflowSteps($workflowDefinition);
            $workflowDefinition->setStartStep($workflowDefinition->getStepByName($startStepName));

            $workflowDefinitions[] = $workflowDefinition;
        }

        return $workflowDefinitions;
    }

    /**
     * @param array $managedEntityClasses
     * @return WorkflowDefinitionEntity[]
     */
    protected function buildDefinitionEntities(array $managedEntityClasses)
    {
        $definitionEntities = array();

        foreach ($managedEntityClasses as $entityClass) {
            $definitionEntity = new WorkflowDefinitionEntity();
            $definitionEntity->setClassName($entityClass);

            $definitionEntities[] = $definitionEntity;
        }

        return $definitionEntities;
    }

    /**
     * @param array $workflowConfiguration
     * @return array
     */
    protected function getManagedEntityClasses(array $workflowConfiguration)
    {
        $managedEntityClasses = array();

        $attributesData = $this->getConfigurationOption(
            $workflowConfiguration,
            WorkflowConfiguration::NODE_ATTRIBUTES,
            array()
        );

        foreach ($attributesData as $attributeData) {
            $type = $this->getConfigurationOption($attributeData, 'type', null);

            if ($type == 'entity') {
                $options = $this->getConfigurationOption($attributeData, 'options', array());
                $this->assertConfigurationOptions($options, array('class'));

                if (!empty($options['managed_entity'])) {
                    $managedEntityClasses[] = $this->getConfigurationOption($options, 'class', null);
                }
            }
        }

        return $managedEntityClasses;
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
