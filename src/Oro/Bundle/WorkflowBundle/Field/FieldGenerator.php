<?php

namespace Oro\Bundle\WorkflowBundle\Field;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor;
use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;

class FieldGenerator
{
    const PROPERTY_WORKFLOW_ITEM = 'workflowItem';
    const PROPERTY_WORKFLOW_STEP = 'workflowStep';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

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
     * @param TranslatorInterface $translator
     * @param ConfigManager $configManager
     * @param EntityProcessor $entityProcessor
     * @param EntityConnector $entityConnector
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $configManager,
        EntityProcessor $entityProcessor,
        EntityConnector $entityConnector
    ) {
        $this->translator = $translator;
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
        if (!$this->configManager->hasConfigFieldModel($entityClass, self::PROPERTY_WORKFLOW_ITEM)) {
            $this->addRelationField(
                $entityClass,
                self::PROPERTY_WORKFLOW_ITEM,
                'oro.workflow.workflowitem.entity_label',
                'oro.workflow.workflowitem.entity_description',
                'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
                'id'
            );
        }
        if (!$this->configManager->hasConfigFieldModel($entityClass, self::PROPERTY_WORKFLOW_STEP)) {
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
        $entityConfig->set('state', ExtendManager::STATE_UPDATED);
        $entityConfig->set('upgradeable', true);

        $this->configManager->persist($entityConfig);
        $this->configManager->flush();

        // update database
        $this->entityProcessor->updateDatabase();
    }

    protected function addRelationField($entityClass, $fieldName, $label, $description, $targetEntity, $targetField)
    {
        $this->configManager->createConfigFieldModel($entityClass, $fieldName, 'manyToOne');

        $entityConfigProvider = $this->configManager->getProvider('entity');
        $entityFieldConfig = $entityConfigProvider->getConfig($entityClass, $fieldName);
        $entityFieldConfig->set('label', $this->translator->trans($label));
        $entityFieldConfig->set('description', $this->translator->trans($description));

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendFieldConfig = $extendConfigProvider->getConfig($entityClass, $fieldName);
        $extendFieldConfig->set('owner', ExtendManager::OWNER_CUSTOM);
        $extendFieldConfig->set('state', ExtendManager::STATE_NEW);
        $extendFieldConfig->set('extend', true);
        $extendFieldConfig->set('target_entity', $targetEntity);
        $extendFieldConfig->set('target_field', $targetField);

        $formConfigProvider = $this->configManager->getProvider('form');
        $formFieldConfig = $formConfigProvider->getConfig($entityClass, $fieldName);
        $formFieldConfig->set('is_enabled', false);

        $viewConfigProvider = $this->configManager->getProvider('view');
        $viewFieldConfig = $viewConfigProvider->getConfig($entityClass, $fieldName);
        $viewFieldConfig->set('is_displayable', false);
    }
}
