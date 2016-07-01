<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;

use Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowVirtualRelationProvider implements VirtualRelationProviderInterface
{
    const ITEMS_RELATION_NAME = 'workflowItems_virtual';
    const STEPS_RELATION_NAME = 'workflowSteps_virtual';

    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param WorkflowManager $workflowManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(WorkflowManager $workflowManager, DoctrineHelper $doctrineHelper)
    {
        $this->workflowManager = $workflowManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualRelation($className, $fieldName)
    {
        return in_array($fieldName, [self::ITEMS_RELATION_NAME, self::STEPS_RELATION_NAME], true)
            && $this->workflowManager->hasApplicableWorkflows($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        if (!$this->workflowManager->hasApplicableWorkflows($className)) {
            return [];
        }

        return [
            self::ITEMS_RELATION_NAME => [
                'label' => 'oro.workflow.workflowitem.entity_label',
                'relation_type' => 'OneToMany',
                'related_entity_name' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
            ],
            self::STEPS_RELATION_NAME => [
                'label' => 'oro.workflow.workflowstep.entity_label',
                'relation_type' => 'OneToMany',
                'related_entity_name' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowStep',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelationQuery($className, $fieldName)
    {
        if (!$this->isVirtualRelation($className, $fieldName)) {
            return [];
        }

        return WorkflowQueryHelper::addDatagridQuery(
            [],
            'entity',
            $className,
            $this->getEntityIdentifier($className),
            self::STEPS_RELATION_NAME,
            self::ITEMS_RELATION_NAME
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null)
    {
        return $fieldName;
    }

    /**
     * @param string $className
     * @return string
     */
    protected function getEntityIdentifier($className)
    {
        return $this->doctrineHelper->getSingleEntityIdentifierFieldName($className);
    }
}
