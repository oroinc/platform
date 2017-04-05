<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

abstract class AbstractParentEntitySearchHandler extends SearchHandler
{
    const DELIMITER = ';';

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        if ($searchById && strpos($query, self::DELIMITER) !== false) {
            list($query) = $this->explodeSearchTerm($query);
        }

        return parent::search($query, $page, $perPage, $searchById);
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

        $entityIds = $this->searchIds($searchTerm, $firstResult, $maxResults);

        if ($entityId) {
            $entity = $this->entityRepository->find($entityId);
            $childrenIds = $this->getChildrenIds($entity);
            $entityIds = array_diff($entityIds, array_merge($childrenIds, [$entityId]));
        }

        return $entityIds ? $this->getEntitiesByIds($entityIds) : [];
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

        return [$searchTerm, $entityId === false ? '' : (int)$entityId];
    }

    /**
     * @param object $entity
     * @return array
     */
    abstract protected function getChildrenIds($entity);
}
