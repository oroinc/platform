<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\WorkflowBundle\Provider\WorkflowItemVirtualRelationProvider;

class WorkflowItemVirtualRelationProviderTest extends AbstractVirtualRelationProviderTest
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->provider = new WorkflowItemVirtualRelationProvider(
            $this->workflowManager,
            $this->doctrineHelper
        );
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return array
     */
    protected function getVirtualRelations($className, $fieldName)
    {
        return [
            $this->provider->getRelationName() => [
                'label' => 'oro.workflow.workflowitem.entity_label',
                'relation_type' => 'OneToMany',
                'related_entity_name' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
                'query' => $this->getVirtualRelationsQuery($className, $fieldName),
            ],
        ];
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return array
     */
    protected function getVirtualRelationsQuery($className, $fieldName)
    {
        return [
            'join' => [
                'left' => [
                    [
                        'join' => 'OroWorkflowBundle:WorkflowItem',
                        'alias' => $this->provider->getRelationName(),
                        'conditionType' => Join::WITH,
                        'condition' => sprintf(
                            'entity.%s = %s.entityId AND %s.entityClass = \'%s\'',
                            $fieldName,
                            $this->provider->getRelationName(),
                            $this->provider->getRelationName(),
                            $className
                        )
                    ]
                ]
            ]
        ];
    }
}
