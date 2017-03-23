<?php

namespace Oro\Bundle\WorkflowBundle\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\ActionBundle\Model\OperationRegistryFilterInterface;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowOperationFilter implements OperationRegistryFilterInterface
{
    const WILDCARD = 'break'; //reserved word never will be used as class name

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var array|null - not set if not initialized
     */
    private $disabledOperations;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(array $operations, OperationFindCriteria $findCriteria)
    {
        $this->loadOperationsToDisable();

        if (count($this->disabledOperations) === 0) {
            return $operations;
        }

        $entityClass = $findCriteria->getEntityClass();

        $filteredOperations = [];
        foreach ($operations as $operationName => $operation) {
            if (!isset($this->disabledOperations[$operationName][self::WILDCARD]) &&
                !isset($this->disabledOperations[$operationName][$entityClass])
            ) {
                $filteredOperations[$operationName] = $operation;
            }
        }

        return $filteredOperations;
    }

    private function loadOperationsToDisable()
    {
        if (is_array($this->disabledOperations)) {
            return;
        }

        $disabledOperations = [];

        foreach ($this->fetchDisabledOperationsConfigs() as $operationName => $entityClasses) {
            if (!isset($disabledOperations[$operationName])) {
                $disabledOperations[$operationName] = empty($entityClasses)
                    ? [self::WILDCARD => self::WILDCARD]
                    : (array)$entityClasses;
            } elseif (isset($disabledOperations[$operationName][self::WILDCARD])) {
                continue;
            } else {
                $disabledOperations[$operationName] = array_merge(
                    $disabledOperations[$operationName],
                    (array)$entityClasses
                );
            }
        }

        //flipping to make hashes as fastest structure to search
        $this->disabledOperations = array_map('array_flip', $disabledOperations);
    }

    /**
     * @return \Generator array(operationName => array(entityClass1,...))
     */
    private function fetchDisabledOperationsConfigs()
    {
        foreach ($this->getRepository()->findActive() as $workflowDefinition) {
            if ($workflowDefinition->hasDisabledOperations()) {
                foreach ($workflowDefinition->getDisabledOperations() as $operationName => $entityClasses) {
                    yield $operationName => $entityClasses;
                }
            }
        }
    }

    /**
     * @return WorkflowDefinitionRepository
     */
    private function getRepository()
    {
        return $this->registry->getManagerForClass(WorkflowDefinition::class)->getRepository(WorkflowDefinition::class);
    }
}
