<?php

namespace Oro\Bundle\SearchBundle\Api;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

/**
 * A helper class to parse a value of a filter for the list of entities to search for.
 */
class SearchEntityListFilterHelper
{
    private SearchMappingProvider $searchMappingProvider;
    private Indexer $searchIndexer;
    private ResourcesProvider $resourcesProvider;
    private ValueNormalizer $valueNormalizer;

    public function __construct(
        SearchMappingProvider $searchMappingProvider,
        Indexer $searchIndexer,
        ResourcesProvider $resourcesProvider,
        ValueNormalizer $valueNormalizer
    ) {
        $this->searchMappingProvider = $searchMappingProvider;
        $this->searchIndexer = $searchIndexer;
        $this->resourcesProvider = $resourcesProvider;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * Gets the list of search aliases for an entities provided by the given filter.
     * When the filter value has some invalid data, the appropriate errors will be added to the context.
     *
     * @param Context $context
     * @param string  $filterName
     *
     * @return string[]
     */
    public function getEntities(Context $context, string $filterName): array
    {
        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $allowedSearchAliases = $this->getAllowedSearchAliases($version, $requestType);
        $filterValue = $context->getFilterValues()->get($filterName);
        if (null === $filterValue) {
            return array_values($allowedSearchAliases);
        }

        $entities = (array)$filterValue->getValue();
        if (!$entities) {
            return [];
        }

        $searchEntities = [];
        $searchAliases = $this->getSearchAliases($version, $requestType);
        $allowedSearchAliasesMap = array_flip($allowedSearchAliases);
        foreach ($entities as $entity) {
            $alias = $searchAliases[$entity] ?? null;
            if (!$alias) {
                $context->addError(
                    $this->createInvalidFilterValueKeyError(
                        $filterValue->getSourceKey(),
                        $this->getInvalidEntityValidationMessage(
                            $entity,
                            array_keys($this->getSearchAliases($version, $requestType))
                        )
                    )
                );
            } elseif (isset($allowedSearchAliasesMap[$alias])) {
                $searchEntities[] = $alias;
            }
        }

        return $searchEntities;
    }

    /**
     * Gets all search entity aliases for all entities available in both API and search index.
     *
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array [API alias of an entity => search alias of an entity, ...]
     */
    private function getSearchAliases(string $version, RequestType $requestType): array
    {
        return $this->buildSearchAliases(
            $this->searchMappingProvider->getEntitiesListAliases(),
            $version,
            $requestType
        );
    }

    /**
     * Gets all search entity aliases for all entities available in both API and search index
     * and allowed for the current logged in user.
     *
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array [API alias of an entity => search alias of an entity, ...]
     */
    private function getAllowedSearchAliases(string $version, RequestType $requestType): array
    {
        return $this->buildSearchAliases(
            $this->searchIndexer->getAllowedEntitiesListAliases(),
            $version,
            $requestType
        );
    }

    private function buildSearchAliases(array $searchAliases, string $version, RequestType $requestType): array
    {
        $result = [];
        foreach ($searchAliases as $entityClass => $searchAlias) {
            if (!$this->resourcesProvider->isResourceAccessible($entityClass, $version, $requestType)) {
                continue;
            }
            $entityType = ValueNormalizerUtil::tryConvertToEntityType(
                $this->valueNormalizer,
                $entityClass,
                $requestType
            );
            if (!$entityClass) {
                continue;
            }
            $result[$entityType] = $searchAlias;
        }

        return $result;
    }

    private function createInvalidFilterValueKeyError(string $filterKey, string $detail = null): Error
    {
        return Error::createValidationError(Constraint::FILTER, $detail)
            ->setSource(ErrorSource::createByParameter($filterKey));
    }

    private function getInvalidEntityValidationMessage(string $entity, array $allowedEntities): string
    {
        return sprintf(
            'The "%s" is not known entity. Known entities: %s',
            $entity,
            implode(', ', $allowedEntities)
        );
    }
}
