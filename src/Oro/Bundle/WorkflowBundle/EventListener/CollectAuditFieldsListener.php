<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;
use Oro\Bundle\DataAuditBundle\Event\CollectAuditFieldsEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowStepRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\InvalidArgumentException;

class CollectAuditFieldsListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param CollectAuditFieldsEvent $event
     */
    public function onCollectAuditFields(CollectAuditFieldsEvent $event)
    {
        $changeSet = $event->getChangeSet();
        if (isset($changeSet[SendWorkflowStepChangesToAuditListener::FIELD_ALIAS])) {
            $oldStepData = reset($changeSet[SendWorkflowStepChangesToAuditListener::FIELD_ALIAS]);
            $newStepData = end($changeSet[SendWorkflowStepChangesToAuditListener::FIELD_ALIAS]);

            /** @var WorkflowStepRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository(WorkflowStep::class);
            $steps = $repository->findByIds([$oldStepData['entity_id'], $newStepData['entity_id']]);

            if (!array_key_exists($newStepData['entity_id'], $steps)) {
                throw new InvalidArgumentException(sprintf(
                    'WorkflowStep was not found by identifier: %s',
                    $newStepData['entity_id']
                ));
            }

            $oldStep = !empty($steps[$oldStepData['entity_id']]) ? $steps[$oldStepData['entity_id']] : null;
            $newStep = $steps[$newStepData['entity_id']];

            $auditFieldClass = $event->getAuditFieldClass();
            /** @var AbstractAuditField $auditFieldEntity */
            $auditFieldEntity = new $auditFieldClass(
                $newStep->getDefinition()->getLabel(),
                'string',
                $newStep->getLabel(),
                $oldStep ? $oldStep->getLabel() : null
            );
            $auditFieldEntity->setTranslationDomain('workflows');

            $event->addField(SendWorkflowStepChangesToAuditListener::FIELD_ALIAS, $auditFieldEntity);
        }
    }
}
