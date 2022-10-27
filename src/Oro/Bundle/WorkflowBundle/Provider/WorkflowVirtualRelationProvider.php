<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The provider to get virtual relations for workflow items ans steps.
 */
class WorkflowVirtualRelationProvider implements VirtualRelationProviderInterface
{
    use WorkflowQueryTrait;

    public const ENTITIES_WITH_WORKFLOW = 'entities_with_workflow';
    public const ITEMS_RELATION_NAME = 'workflowItems_virtual';
    public const STEPS_RELATION_NAME = 'workflowSteps_virtual';

    private DoctrineHelper $doctrineHelper;
    private CacheInterface $entitiesWithWorkflowCache;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        CacheInterface $entitiesWithWorkflowCache
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entitiesWithWorkflowCache = $entitiesWithWorkflowCache;
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualRelation($className, $fieldName)
    {
        return
            (self::ITEMS_RELATION_NAME === $fieldName || self::STEPS_RELATION_NAME === $fieldName)
            && $this->hasEntityActiveWorkflow($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        if (!$this->hasEntityActiveWorkflow($className)) {
            return [];
        }

        return [
            self::ITEMS_RELATION_NAME => [
                'label' => 'oro.workflow.workflowitem.entity_label',
                'relation_type' => 'OneToMany',
                'related_entity_name' => WorkflowItem::class,
            ],
            self::STEPS_RELATION_NAME => [
                'label' => 'oro.workflow.workflowstep.entity_label',
                'relation_type' => 'OneToMany',
                'related_entity_name' => WorkflowStep::class,
            ]
        ];
    }

    /**
     * @param string $className
     * @return bool
     */
    private function hasEntityActiveWorkflow($className)
    {
        $className = ClassUtils::getRealClass($className);
        $entitiesWithWorkflow = $this->getEntitiesWithWorkflow();

        return !empty($entitiesWithWorkflow[$className]);
    }

    private function getEntitiesWithWorkflow(): ?array
    {
        return $this->entitiesWithWorkflowCache->get(self::ENTITIES_WITH_WORKFLOW, function () {
            $workflowDefinitionRepository = $this->doctrineHelper->getEntityRepository(WorkflowDefinition::class);
            $entityClasses = $workflowDefinitionRepository->getAllRelatedEntityClasses(true);
            $entitiesWithWorkflow = [];
            foreach ($entityClasses as $entityClass) {
                $entitiesWithWorkflow[$entityClass] = true;
            }
            return $entitiesWithWorkflow;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelationQuery($className, $fieldName)
    {
        if (!$this->isVirtualRelation($className, $fieldName)) {
            return [];
        }

        return $this->addDatagridQuery(
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
