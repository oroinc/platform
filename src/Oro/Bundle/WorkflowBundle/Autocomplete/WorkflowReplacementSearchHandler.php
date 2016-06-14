<?php

namespace Oro\Bundle\WorkflowBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowReplacementSearchHandler extends SearchHandler
{
    const DELIMITER = ';';

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * {@inheritdoc}
     */
    protected function checkAllDependenciesInjected()
    {
        if (!$this->entityRepository || !$this->idFieldName || !$this->workflowManager) {
            throw new \RuntimeException('Search handler is not fully configured');
        }
    }

    /**
     * @param WorkflowManager $workflowManager
     */
    public function setWorkflowManager(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        if (strpos($search, self::DELIMITER) === false) {
            return [];
        }

        list($searchTerm, $entityId) = $this->explodeSearchTerm($search);

        /* @var $queryBuilder QueryBuilder */
        $queryBuilder = $this->entityRepository->createQueryBuilder('w');
        $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        if ($searchTerm) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->like('w.label', ':search'))
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        if ($entityId) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->notIn('w.' . $this->idFieldName, ':id'))
                ->setParameter('id', $entityId);
        }

        return array_filter($queryBuilder->getQuery()->getResult(), function (WorkflowDefinition $definition) {
            return $this->workflowManager->isActiveWorkflow($definition);
        });
    }

    /**
     * @param string $search
     * @return array
     */
    protected function explodeSearchTerm($search)
    {
        $delimiterPos = strrpos($search, self::DELIMITER);
        $searchTerm = substr($search, 0, $delimiterPos);
        $entityId = substr($search, $delimiterPos + 1);

        return [$searchTerm, $entityId === false ? '' : $entityId];
    }
}
