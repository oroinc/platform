<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

/**
 * Base class for autocomplete search handlers that filter parent entities.
 *
 * Extends {@see SearchHandler} to prevent circular hierarchies by excluding an entity and its children
 * from search results. Uses a delimiter-separated format to pass both search terms and entity IDs.
 * Subclasses must implement {@see getChildrenIds()} to provide entity-specific child retrieval logic.
 */
abstract class AbstractParentEntitySearchHandler extends SearchHandler
{
    public const DELIMITER = ';';

    #[\Override]
    public function search($query, $page, $perPage, $searchById = false)
    {
        if ($searchById && str_contains($query, self::DELIMITER)) {
            [$query] = $this->explodeSearchTerm($query);
        }

        return parent::search($query, $page, $perPage, $searchById);
    }

    #[\Override]
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        if (!str_contains($search, self::DELIMITER)) {
            return [];
        }

        [$searchTerm, $entityId] = $this->explodeSearchTerm($search);

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
