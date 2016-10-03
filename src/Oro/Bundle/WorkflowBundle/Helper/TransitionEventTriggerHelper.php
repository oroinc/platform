<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class TransitionEventTriggerHelper
{
    const TRIGGER_ENTITY = 'entity';
    const TRIGGER_WORKFLOW_ENTITY = 'mainEntity';
    const TRIGGER_WORKFLOW_DEFINITION = 'wd';
    const TRIGGER_WORKFLOW_ITEM = 'wi';

    /** @var WorkflowManager */
    private $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @param object $entity
     * @return bool
     */
    public function isRequirePass(TransitionEventTrigger $trigger, $entity)
    {
        $require = $trigger->getRequire();
        if (!$require) {
            return true;
        }

        $expressionLanguage = new ExpressionLanguage();

        $result = $expressionLanguage->evaluate(
            implode(
                ' and ',
                [
                    self::TRIGGER_WORKFLOW_ENTITY . '!== null',
                    $require
                ]
            ),
            $this->getExpressionValues($trigger, $entity)
        );

        return !empty($result);
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @param object $entity
     * @return object|null
     */
    public function getMainEntity(TransitionEventTrigger $trigger, $entity)
    {
        $relation = $trigger->getRelation();
        if ($relation) {
            $propertyAccessor = new PropertyAccessor();

            $mainEntity = $propertyAccessor->getValue($entity, $trigger->getRelation());
        } else {
            $mainEntity = $entity;
        }

        $mainEntityClass = $trigger->getWorkflowDefinition()->getRelatedEntity();
        if (!$mainEntity instanceof $mainEntityClass) {
            $mainEntity = null;
        }

        return $mainEntity;
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @param object $entity
     * @return array
     */
    private function getExpressionValues(TransitionEventTrigger $trigger, $entity)
    {
        $mainEntity = $this->getMainEntity($trigger, $entity);
        $workflowDefinition = $trigger->getWorkflowDefinition();

        $workflowItem = $this->workflowManager->getWorkflowItem($mainEntity, $workflowDefinition->getName());

        return self::buildContextValues($workflowDefinition, $entity, $mainEntity, $workflowItem);
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param object $entity
     * @param object $mainEntity
     * @param WorkflowItem $item
     * @return array
     */
    public static function buildContextValues(
        WorkflowDefinition $workflowDefinition = null,
        $entity = null,
        $mainEntity = null,
        WorkflowItem $item = null
    ) {
        return [
            self::TRIGGER_WORKFLOW_DEFINITION => $workflowDefinition,
            self::TRIGGER_WORKFLOW_ITEM => $item,
            self::TRIGGER_ENTITY => $entity,
            self::TRIGGER_WORKFLOW_ENTITY => $mainEntity
        ];
    }
}
