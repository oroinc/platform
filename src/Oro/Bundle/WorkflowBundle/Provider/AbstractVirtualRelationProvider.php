<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

abstract class AbstractVirtualRelationProvider implements VirtualRelationProviderInterface
{
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
     * @return string
     */
    abstract protected function getRelationName();

    /**
     * @param string $className
     * @param string $idField
     * @return array
     */
    abstract protected function getRelationDefinition($className, $idField);

    /**
     * @param string $className
     * @return string
     */
    protected function getEntityIdentifier($className)
    {
        return $this->doctrineHelper->getSingleEntityIdentifierFieldName($className);
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualRelation($className, $fieldName)
    {
        return $fieldName === $this->getRelationName()
            && $this->workflowManager->hasApplicableWorkflowsByEntityClass($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        if ($this->workflowManager->hasApplicableWorkflowsByEntityClass($className)) {
            return [
                $this->getRelationName() => $this->getRelationDefinition(
                    $className,
                    $this->getEntityIdentifier($className)
                )
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelationQuery($className, $fieldName)
    {
        $relations = $this->getVirtualRelations($className);
        if (array_key_exists($fieldName, $relations)) {
            return $relations[$fieldName]['query'];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null)
    {
        return $this->getRelationName();
    }
}
