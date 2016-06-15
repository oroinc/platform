<?php

namespace Oro\Bundle\WorkflowBundle\Field;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;

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
     *
     * @deprecated
     */
    public function generateWorkflowFields($entityClass)
    {
        return;
        if ($this->entityConnector->isWorkflowAware($entityClass)) {
            return;
        }

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityConfig = $extendConfigProvider->getConfig($entityClass);
        if (!$entityConfig || !$entityConfig->is('is_extend')) {
            throw new WorkflowException(sprintf('Class %s can not be extended', $entityClass));
        }

        $workflowItemClass = 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem';
        $workflowStepClass = 'Oro\Bundle\WorkflowBundle\Entity\WorkflowStep';

        // add fields
        $hasWorkflowItemField = $this->configManager->hasConfig($entityClass, self::PROPERTY_WORKFLOW_ITEM);
        if (!$hasWorkflowItemField) {
            $this->addRelationField(
                $entityClass,
                self::PROPERTY_WORKFLOW_ITEM,
                ConfigHelper::getTranslationKey('entity', 'label', $workflowItemClass, 'related_entity'),
                ConfigHelper::getTranslationKey('entity', 'description', $workflowItemClass, 'related_entity'),
                $workflowItemClass,
                'id'
            );
        }
        $hasWorkflowStepField = $this->configManager->hasConfig($entityClass, self::PROPERTY_WORKFLOW_STEP);
        if (!$hasWorkflowStepField) {
            $this->addRelationField(
                $entityClass,
                self::PROPERTY_WORKFLOW_STEP,
                ConfigHelper::getTranslationKey('entity', 'label', $workflowStepClass, 'related_entity'),
                ConfigHelper::getTranslationKey('entity', 'description', $workflowStepClass, 'related_entity'),
                $workflowStepClass,
                'label'
            );
        }

        // update entity config
        $entityConfig->set('state', ExtendScope::STATE_UPDATE);
        $entityConfig->set('upgradeable', true);
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();

        // update database
        $this->entityProcessor->updateDatabase();

        // make workflowItem field hidden
        // TODO: Fields can be hidden only after schema update due to a bug in DoctrineSubscriber
        // TODO: I'm not sure that the bug in DoctrineSubscriber. It seems that the bug in
        // TODO: Doctrine\ORM\Tools\SchemaTool::getSchemaFromMetadata. See fix made in BAP-3621
        // TODO: If make fields hidden then these fields will be created only for the first extended entity
        // TODO: Should be fixed in scope of https://magecore.atlassian.net/browse/BAP-3632
        /*
        if (!$hasWorkflowItemField) {
            $this->hideRelationField($entityClass, self::PROPERTY_WORKFLOW_ITEM);
        }
        $this->configManager->flush();
        */
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
        $this->configManager->createConfigFieldModel($entityClass, $fieldName, RelationType::MANY_TO_ONE);

        $entityConfigProvider = $this->configManager->getProvider('entity');
        $entityFieldConfig = $entityConfigProvider->getConfig($entityClass, $fieldName);
        $entityFieldConfig->set('label', $label);
        $entityFieldConfig->set('description', $description);

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendFieldConfig = $extendConfigProvider->getConfig($entityClass, $fieldName);
        $extendFieldConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendFieldConfig->set('state', ExtendScope::STATE_NEW);
        $extendFieldConfig->set('is_extend', true);
        $extendFieldConfig->set('target_entity', $targetEntity);
        $extendFieldConfig->set('target_field', $targetField);
        $extendFieldConfig->set(
            'relation_key',
            ExtendHelper::buildRelationKey($entityClass, $targetField, RelationType::MANY_TO_ONE, $targetEntity)
        );

        $formConfigProvider = $this->configManager->getProvider('form');
        $formFieldConfig = $formConfigProvider->getConfig($entityClass, $fieldName);
        $formFieldConfig->set('is_enabled', false);

        $viewConfigProvider = $this->configManager->getProvider('view');
        $viewFieldConfig = $viewConfigProvider->getConfig($entityClass, $fieldName);
        $viewFieldConfig->set('is_displayable', false);

        $importExportConfigProvider = $this->configManager->getProvider('importexport');
        $importExportFieldConfig = $importExportConfigProvider->getConfig($entityClass, $fieldName);
        $importExportFieldConfig->set('excluded', true);
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     */
    protected function hideRelationField($entityClass, $fieldName)
    {
        $fieldModel = $this->configManager->getConfigFieldModel($entityClass, $fieldName);
        $fieldModel->setType(ConfigModel::MODE_HIDDEN);
    }
}
