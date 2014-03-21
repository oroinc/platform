<?php

namespace Oro\Bundle\WorkflowBundle\Field;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class FieldGenerator
{
    const PROPERTY_WORKFLOW_ITEM = 'workflowItem';
    const PROPERTY_WORKFLOW_STEP = 'workflowStep';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var EntityProcessor
     */
    protected $entityProcessor;

    /**
     * @var EntityConnector
     */
    protected $entityConnector;

    /**
     * @param ConfigManager $configManager
     * @param EntityProcessor $entityProcessor
     * @param EntityConnector $entityConnector
     */
    public function __construct(
        ConfigManager $configManager,
        EntityProcessor $entityProcessor,
        EntityConnector $entityConnector
    ) {
        $this->configManager = $configManager;
        $this->entityProcessor = $entityProcessor;
        $this->entityConnector = $entityConnector;
    }

    /**
     * @param string $entityClass
     * @throws WorkflowException
     */
    public function generateWorkflowFields($entityClass)
    {
        if ($this->entityConnector->isWorkflowAware($entityClass)) {
            return;
        }

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityConfig = $extendConfigProvider->getConfig($entityClass);
        if (!$entityConfig || !$entityConfig->is('is_extend')) {
            throw new WorkflowException(sprintf('Class %s can not be extended', $entityClass));
        }

        // add fields
        $hasWorkflowItemField = $this->configManager->hasConfigFieldModel($entityClass, self::PROPERTY_WORKFLOW_ITEM);
        if (!$hasWorkflowItemField) {
            $this->addRelationField(
                $entityClass,
                self::PROPERTY_WORKFLOW_ITEM,
                'oro.workflow.workflowitem.entity_label',
                'oro.workflow.workflowitem.entity_description',
                'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
                'id'
            );
        }
        $hasWorkflowStepField = $this->configManager->hasConfigFieldModel($entityClass, self::PROPERTY_WORKFLOW_STEP);
        if (!$hasWorkflowStepField) {
            $this->addRelationField(
                $entityClass,
                self::PROPERTY_WORKFLOW_STEP,
                'oro.workflow.workflowstep.entity_label',
                'oro.workflow.workflowstep.entity_description',
                'Oro\Bundle\WorkflowBundle\Entity\WorkflowStep',
                'label'
            );
        }

        // update entity config
        $entityConfig->set('state', ExtendScope::STATE_UPDATED);
        $entityConfig->set('upgradeable', true);
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();

        // update database
        $this->entityProcessor->updateDatabase();

        // make fields hidden
        if (!$hasWorkflowItemField) {
            $this->hideRelationField($entityClass, self::PROPERTY_WORKFLOW_ITEM);
        }
        if (!$hasWorkflowStepField) {
            $this->hideRelationField($entityClass, self::PROPERTY_WORKFLOW_STEP);
        }
        $this->configManager->flush();
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     * @param string $label
     * @param string $description
     * @param string $targetEntity
     * @param string $targetField
     */
    protected function addRelationField($entityClass, $fieldName, $label, $description, $targetEntity, $targetField)
    {
        $this->configManager->createConfigFieldModel($entityClass, $fieldName, 'manyToOne');

        $entityConfigProvider = $this->configManager->getProvider('entity');
        $entityFieldConfig = $entityConfigProvider->getConfig($entityClass, $fieldName);
        $entityFieldConfig->set('label', $label);
        $entityFieldConfig->set('description', $description);

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendFieldConfig = $extendConfigProvider->getConfig($entityClass, $fieldName);
        $extendFieldConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendFieldConfig->set('state', ExtendScope::STATE_NEW);
        $extendFieldConfig->set('extend', true);
        $extendFieldConfig->set('target_entity', $targetEntity);
        $extendFieldConfig->set('target_field', $targetField);
        $extendFieldConfig->set(
            'relation_key',
            ExtendHelper::buildRelationKey($entityClass, $targetField, 'manyToOne', $targetEntity)
        );

        $formConfigProvider = $this->configManager->getProvider('form');
        $formFieldConfig = $formConfigProvider->getConfig($entityClass, $fieldName);
        $formFieldConfig->set('is_enabled', false);

        $viewConfigProvider = $this->configManager->getProvider('view');
        $viewFieldConfig = $viewConfigProvider->getConfig($entityClass, $fieldName);
        $viewFieldConfig->set('is_displayable', false);
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     */
    protected function hideRelationField($entityClass, $fieldName)
    {
        $fieldModel = $this->configManager->getConfigFieldModel($entityClass, $fieldName);
        $fieldModel->setType(ConfigModelManager::MODE_HIDDEN);
    }
}
