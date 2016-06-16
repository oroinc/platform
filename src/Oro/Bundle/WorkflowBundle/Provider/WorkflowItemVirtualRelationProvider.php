<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;

class WorkflowItemVirtualRelationProvider extends AbstractVirtualRelationProvider
{
    const RELATION_NAME = 'workflowItems_virtual';

    /**
     * {@inheritdoc}
     */
    public function getRelationName()
    {
        return self::RELATION_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationDefinition($className, $idField)
    {
        return [
            'label' => 'oro.workflow.workflowitem.entity_label',
            'relation_type' => 'OneToMany',
            'related_entity_name' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
            'query' => [
                'join' => [
                    'left' => [
                        [
                            'join' => 'OroWorkflowBundle:WorkflowItem',
                            'alias' => self::RELATION_NAME,
                            'conditionType' => Join::WITH,
                            'condition' => sprintf(
                                'entity.%s = %s.entityId AND %s.entityClass = \'%s\'',
                                $idField,
                                self::RELATION_NAME,
                                self::RELATION_NAME,
                                $className
                            )
                        ]
                    ]
                ]
            ]
        ];
    }
}
