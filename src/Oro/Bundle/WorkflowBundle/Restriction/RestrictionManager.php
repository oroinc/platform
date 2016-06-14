<?php

namespace Oro\Bundle\WorkflowBundle\Restriction;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowRestrictionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestrictionIdentity;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class RestrictionManager
{
    /**
     * @var array [$entityClass => WorkflowRestriction[], ...]
     */
    protected $restrictions = [];

    /**
     * @var array [$entityClass => [$workflowName => $workflowData, ...], ...]
     */
    protected $workflows = [];

    /**
     * @var array [id, ...]
     */
    protected $activeRestrictions = [];

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
     * @param bool   $activeWorkflows
     *
     * @return bool
     */
    public function hasEntityClassRestrictions($entityClass, $activeWorkflows = true)
    {
        $this->loadClassRestrictions($entityClass);
        $restrictions = $this->restrictions[$entityClass];

        if ($activeWorkflows) {
            $restrictions = $this->filterByActiveWorkflows($restrictions);
        }

        return !empty($restrictions);
    }

    /**
     * @param string $entityClass
     * @param array  $identifiers
     *
     * @return array [['field' => $field, 'mode' => $mode, 'values' => $values, 'ids' => $ids], ...]
     *
     */
    public function getRestrictionsByClassAndIdentifiers($entityClass, array $identifiers = [])
    {
        if (!$this->doctrineHelper->isManageableEntity($entityClass) || empty($identifiers)) {
            return [];
        }
        $this->loadClassRestrictions($entityClass);

        return $this->filterByActiveWorkflows(
            $this->getRestrictionsForEntityIds($entityClass, $identifiers)
        );
    }

    /**
     * @param object $entity
     *
     * @return array [['field' => $field, 'mode' => $mode, 'values' => $values, ?'ids' => $ids], ...]
     */
    public function getEntityRestrictions($entity)
    {
        if (!$this->doctrineHelper->isManageableEntity($entity)) {
            return [];
        }
        $class = ClassUtils::getClass($entity);
        $id    = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $this->loadClassRestrictions($class);

        $restrictions = $id
            ? $this->getRestrictionsForEntityIds($class, [$id])
            : $this->filterNewEntityRestrictions($this->restrictions[$class]);

        return $this->filterByActiveWorkflows($restrictions);
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
     * @param string $entityClass
     * @param array  $entityIds
     *
     * @return array
     */
    protected function getRestrictionsForEntityIds($entityClass, array $entityIds)
    {
        $entitiesRestrictionsData = $this->getRestrictionRepository()->getEntitiesRestrictionsData(
            $entityClass,
            $entityIds
        );

        return array_map(
            function ($item) {
                $item['ids'] = explode(',', $item['ids']);

                return $item;
            },
            $entitiesRestrictionsData
        );
    }

    /**
     * @param array $restrictions
     *
     * @return array
     */
    protected function filterNewEntityRestrictions(array $restrictions)
    {
        return array_filter(
            $restrictions,
            function (array $restriction) {
                return !$restriction['step'];
            }
        );

    }

    /**
     * @param WorkflowRestriction[] $restrictions
     *
     * @return WorkflowRestriction[]
     */
    protected function filterByActiveWorkflows(array $restrictions)
    {
        return array_filter(
            $restrictions,
            function (array $restriction) {
                return in_array($restriction['id'], $this->activeRestrictions);
            }
        );
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
            $classRestrictions = $this->getRestrictionRepository()->getClassRestrictions($entityClass);
            foreach ($classRestrictions as $classRestriction) {
                $workflowName = $classRestriction['workflowName'];
                if (!isset($this->workflows[$entityClass][$workflowName])) {
                    $workflow = $this->workflowManager
                        ->getApplicableWorkflowByEntityClass(
                            $classRestriction['relatedEntity']
                        );
                    if (null !== $workflow) {
                        if ($workflow->getName() === $workflowName) {
                            $this->workflows[$entityClass][$workflowName] = ['is_active' => true];
                        } else {
                            $this->workflows[$entityClass][$workflowName] = ['is_active' => false];
                            $this->workflows[$entityClass][$workflow->getName()] = ['is_active' => true];
                        }
                    }
                }
                if (!empty($this->workflows[$entityClass][$workflowName]['is_active'])) {
                    $this->activeRestrictions[] = $classRestriction['id'];
                }
            }
            $this->restrictions[$entityClass] = $classRestrictions;
        }
    }
}
