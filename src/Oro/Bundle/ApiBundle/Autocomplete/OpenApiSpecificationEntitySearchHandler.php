<?php

namespace Oro\Bundle\ApiBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;

/**
 * The autocomplete handler to search entities for OpenAPI specification.
 */
class OpenApiSpecificationEntitySearchHandler implements SearchHandlerInterface
{
    private OpenApiSpecificationEntityProviderInterface $entityProvider;

    public function __construct(OpenApiSpecificationEntityProviderInterface $entityProvider)
    {
        $this->entityProvider = $entityProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        [$searchTerm, $view] = explode(';', $query, 2);
        $resultData = [];
        $more = false;
        if ($view) {
            $entities = $this->entityProvider->getEntities($view);
            if ($searchTerm) {
                $entities = $this->filterEntities($entities, $searchTerm);
            }
            $resultEntities = $entities;
            $resultEntities = array_splice($resultEntities, ($page - 1) * $perPage, $perPage);
            foreach ($resultEntities as $entity) {
                $resultData[] = $this->convertItem($entity);
            }
            $more = isset($entities[$page * $perPage]);
        }

        return ['results' => $resultData, 'more' => $more];
    }

    /**
     * {@inheritDoc}
     */
    public function convertItem($item)
    {
        if ($item instanceof OpenApiSpecificationEntity) {
            return ['id' => $item->getId(), 'name' => $item->getName()];
        }
        if (\is_string($item)) {
            return ['id' => $item];
        }
        throw new \UnexpectedValueException(
            'Expected argument of type "%s" or "string", "%s" given.',
            OpenApiSpecificationEntity::class,
            get_debug_type($item)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties()
    {
        return ['name'];
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityName()
    {
        return OpenApiSpecificationEntity::class;
    }

    /**
     * @param OpenApiSpecificationEntity[] $entities
     * @param string                       $searchTerm
     *
     * @return OpenApiSpecificationEntity[]
     */
    private function filterEntities(array $entities, string $searchTerm): array
    {
        $filteredEntities = [];
        $searchTerm = strtolower($searchTerm);
        foreach ($entities as $entity) {
            if (str_contains(strtolower($entity->getName()), $searchTerm)) {
                $filteredEntities[] = $entity;
            }
        }

        return $filteredEntities;
    }
}
