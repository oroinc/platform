<?php

namespace Oro\Bundle\WorkflowBundle\DataAudit;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;
use Oro\Bundle\DataAuditBundle\Service\ChangeSetToAuditFieldsConverterInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowStepRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\EventListener\SendWorkflowStepChangesToAuditListener;

/**
 * Adds the data audit field represents workflow step changing for an entity.
 */
class WorkflowStepToAuditFieldConverter implements ChangeSetToAuditFieldsConverterInterface
{
    /** @var ChangeSetToAuditFieldsConverterInterface */
    private $innerConverter;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param ChangeSetToAuditFieldsConverterInterface $innerConverter
     * @param DoctrineHelper                           $doctrineHelper
     */
    public function __construct(
        ChangeSetToAuditFieldsConverterInterface $innerConverter,
        DoctrineHelper $doctrineHelper
    ) {
        $this->innerConverter = $innerConverter;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(
        string $auditEntryClass,
        string $auditFieldClass,
        ClassMetadata $entityMetadata,
        array $changeSet
    ): array {
        $fields = $this->innerConverter->convert($auditEntryClass, $auditFieldClass, $entityMetadata, $changeSet);
        if (isset($changeSet[SendWorkflowStepChangesToAuditListener::FIELD_ALIAS])) {
            list($oldStepData, $newStepData) = $changeSet[SendWorkflowStepChangesToAuditListener::FIELD_ALIAS];
            $oldStepId = $oldStepData['entity_id'] ?? null;
            $newStepId = $newStepData['entity_id'] ?? null;
            $steps = $this->loadSteps($oldStepId, $newStepId);
            if (!empty($steps)) {
                $oldStep = $steps[$oldStepId] ?? null;
                $newStep = $steps[$newStepId] ?? null;
                $workflowName = null;
                if (null !== $newStep) {
                    $workflowName = $newStep->getDefinition()->getLabel();
                } elseif (null !== $oldStep) {
                    $workflowName = $oldStep->getDefinition()->getLabel();
                }

                $fields[SendWorkflowStepChangesToAuditListener::FIELD_ALIAS] = $this->createAuditFieldEntity(
                    $auditFieldClass,
                    $workflowName,
                    $newStep ? $newStep->getLabel() : null,
                    $oldStep ? $oldStep->getLabel() : null
                );
            }
        }

        return $fields;
    }

    /**
     * @param int|null $oldStepId
     * @param int|null $newStepId
     *
     * @return WorkflowStep[] [id => step, ...]
     */
    private function loadSteps($oldStepId, $newStepId)
    {
        $ids = [];
        if (null !== $oldStepId) {
            $ids[] = $oldStepId;
        }
        if (null !== $newStepId) {
            $ids[] = $newStepId;
        }

        /** @var WorkflowStepRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(WorkflowStep::class);

        return $repository->findByIds($ids);
    }

    /**
     * @param string $auditFieldClass
     * @param string $field
     * @param mixed  $newValue
     * @param mixed  $oldValue
     *
     * @return AbstractAuditField
     */
    private function createAuditFieldEntity(
        $auditFieldClass,
        $field,
        $newValue = null,
        $oldValue = null
    ) {
        /** @var AbstractAuditField $auditFieldEntity */
        $auditFieldEntity = new $auditFieldClass($field, 'string', $newValue, $oldValue);
        $auditFieldEntity->setTranslationDomain('workflows');

        return $auditFieldEntity;
    }
}
