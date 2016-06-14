<?php

namespace Oro\Bundle\WorkflowBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

class WorkflowReplacementSearchHandler extends SearchHandler
{
    const DELIMITER = ';';

    /**
     * {@inheritdoc}
     */
    protected function checkAllDependenciesInjected()
    {
        if (!$this->entityRepository || !$this->idFieldName) {
            throw new \RuntimeException('Search handler is not fully configured');
        }
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

        return $queryBuilder->getQuery()->getResult();
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
