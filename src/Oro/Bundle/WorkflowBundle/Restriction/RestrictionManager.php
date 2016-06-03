<?php

namespace Oro\Bundle\WorkflowBundle\Restriction;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowRestrictionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestrictionIdentity;
use Oro\Bundle\WorkflowBundle\Exception\InvalidArgumentException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class RestrictionManager
{
    /**
     * @var array [$entityClass => WorkflowRestriction[], ...]
     */
    protected $restrictions;

    /**
     * @var array [$entityClass => [$workflowName => $workflowData, ...], ...]
     */
    protected $workflows;

    /**
     * @var WorkflowRestrictionRepository
     */
    protected $restrictionRepository;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     * @param DoctrineHelper  $doctrineHelper
     */
    public function __construct(WorkflowManager $workflowManager, DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper  = $doctrineHelper;
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param string $entityClass
     *
     * @param bool   $activeWorkflows
     *
     * @return bool
     */
    public function hasEntityClassRestrictions($entityClass, $activeWorkflows = true)
    {
        $this->loadClassRestrictions($entityClass);
        $restrictions = $this->restrictions[$entityClass];

        if ($activeWorkflows) {
            $restrictions = $this->filterByActiveWorkflows($restrictions, $entityClass);
        }

        return !empty($restrictions);
    }

    /**
     * @param string     $entityOrClass
     * @param array|null $entityIds
     *
     * @return array [['field' => $field, 'mode' => $mode, 'values' => $values, ?'ids' => $ids], ...]
     */
    public function getEntitiesRestrictions($entityOrClass, array $entityIds = null)
    {
        if (!$this->doctrineHelper->isManageableEntity($entityOrClass)) {
            return [];
        }
        if (is_object($entityOrClass)) {
            return $this->getSingleEntityRestrictions($entityOrClass);
        } else {
            if (empty($entityIds)) {
                throw new InvalidArgumentException(
                    'Entity object or entity class with identifiers array should be provided'
                );
            }

            return $this->getRestrictionsForEntityIds($entityOrClass, $entityIds);
        }
    }

    /**
     * @param WorkflowItem $workflowItem
     *
     * @return WorkflowItem
     * @throws WorkflowException
     */
    public function updateEntityRestrictions(WorkflowItem $workflowItem)
    {
        $definition      = $workflowItem->getDefinition();
        $currentStepName = $workflowItem->getCurrentStep()->getName();

        $restrictionIdentities = [];
        foreach ($definition->getRestrictions() as $restriction) {
            if ($restriction->getStep() && $restriction->getStep()->getName() === $currentStepName) {
                $attributeName = $restriction->getAttribute();
                $entity        = $workflowItem->getData()->get($attributeName);
                if (!$entity) {
                    continue;
                }

                if (!is_object($entity)) {
                    throw new WorkflowException(sprintf('Value of attribute "%s" must be an object', $attributeName));
                }

                $restrictionIdentity = new WorkflowRestrictionIdentity();
                $restrictionIdentity->setRestriction($restriction)
                    ->setEntityId($this->doctrineHelper->getSingleEntityIdentifier($entity));

                $restrictionIdentities[] = $restrictionIdentity;
            }
        }

        $workflowItem->setRestrictionIdentities($restrictionIdentities);

        return $workflowItem;
    }

    /**
     * @param $entity
     *
     * @return array
     */
    protected function getSingleEntityRestrictions($entity)
    {
        $class = ClassUtils::getClass($entity);
        $id    = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $this->loadClassRestrictions($class);
        
        return $id
            ? $this->getRestrictionsForEntityIds($class, [$id])
            : $this->filterNewEntityRestrictions($this->restrictions[$class]);

    }

    /**
     * @param string $entityClass
     * @param array  $entityIds
     *
     * @return array
     */
    protected function getRestrictionsForEntityIds($entityClass, array $entityIds)
    {
        $entitiesRestrictionsData = $this->getRestrictionRepository()->getEntitiesRestrictionsData($entityClass, $entityIds);

        return array_map(
            function ($item) {
                $item['ids'] = explode(',', $item['ids']);

                return $item;
            },
            $entitiesRestrictionsData
        );
    }

    /**
     * @param WorkflowRestriction[] $restrictions
     *
     * @return array
     */
    protected function filterNewEntityRestrictions(array $restrictions)
    {
        $filtered = [];
        foreach ($restrictions as $restriction) {
            if (!$restriction->getStep()) {
                $filtered[] = [
                    'field'  => $restriction->getField(),
                    'mode'   => $restriction->getMode(),
                    'values' => $restriction->getValues()
                ];
            };
        }

        return $filtered;
    }

    /**
     * @param WorkflowRestriction[] $restrictions
     *
     * @param string                $entityClass
     *
     * @return WorkflowRestriction[]
     */
    protected function filterByActiveWorkflows(array $restrictions, $entityClass)
    {
        $filtered = [];
        foreach ($restrictions as $restriction) {
            $workflowDefinition = $restriction->getDefinition();
            $workflowName       = $workflowDefinition->getName();
            if (!isset($this->workflows[$entityClass][$workflowName])) {
                $workflow = $this->workflowManager
                    ->getApplicableWorkflowByEntityClass(
                        $workflowDefinition->getRelatedEntity()
                    );

                $this->workflows[$entityClass][$workflowName] = ['is_active' => null !== $workflow];
            }
            if ($this->workflows[$entityClass][$workflowName]['is_active']) {
                $filtered[] = $restriction;
            }
        }

        return $filtered;
    }

    /**
     * @return WorkflowRestrictionRepository
     */
    protected function getRestrictionRepository()
    {
        if (null === $this->restrictionRepository) {
            $this->restrictionRepository = $this->doctrineHelper
                ->getEntityRepositoryForClass('OroWorkflowBundle:WorkflowRestriction');
        }

        return $this->restrictionRepository;
    }

    /**
     * @param string $entityClass
     */
    protected function loadClassRestrictions($entityClass)
    {
        if (!isset($this->restrictions[$entityClass])) {
            $this->restrictions[$entityClass] = $this->getRestrictionRepository()->getClassRestrictions($entityClass);
        }
    }
}
